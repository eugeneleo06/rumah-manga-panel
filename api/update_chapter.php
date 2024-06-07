<?php

require '../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Ramsey\Uuid\Uuid;


ob_start();

session_start();
if (!isset($_SESSION["username"])) {
    header('Location: ../404.php');
}


$path = '../db.sqlite';

include('../config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $secure_id = htmlspecialchars($_POST['secure_id']);
        $title = htmlspecialchars($_POST['chapter_title']);

        $sql = "SELECT manga_id FROM chapters WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();
        $manga_id = $stmt->fetchColumn();

        $sql = "SELECT secure_id FROM mangas WHERE id = :manga_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':manga_id', $manga_id, PDO::PARAM_STR);
        $stmt->execute();
        $mangaSecureId = $stmt->fetchColumn();

        $editPath = '?q='.$mangaSecureId;

        $chapterImages = $_FILES['chapter_image'];

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 3 * 1024 * 1024; // 3MB

        $newURL = [];

        // Cloudflare R2 configuration
        $bucketName = 'rumah-manga';

        // Instantiate the S3 client with your Cloudflare R2 credentials and endpoint
        $s3Client = new S3Client([
            'region' => 'auto',
            'version' => 'latest',
            'endpoint' => 'https://30bec3ffe57679ccf1b6241164b1035a.r2.cloudflarestorage.com',
            'credentials' => [
                'key' => '48c8f95fb86c743a509cf22d02fcf265',
                'secret' => 'b8e0b179cf9e1586609c670422a93c17461cb84656d970690a885c196e0781b7',
            ],
        ]);

        // Process uploaded files
        for ($i = 0; $i < count($chapterImages['name']); $i++) {
            $fileTmpName = $chapterImages['tmp_name'][$i];
            $fileSize = $chapterImages['size'][$i];
            $fileType = $chapterImages['type'][$i];
            $fileExtension = pathinfo($chapterImages['name'][$i], PATHINFO_EXTENSION);
            $newFileName = Uuid::uuid1()->toString() . '.' . $fileExtension;

            // Validate file extension
            if (!in_array(strtolower($fileExtension), $allowed)) {
                $_SESSION['error'] = "Please upload a valid file type (jpg, jpeg, png, webp)";
                header('Location: ../upsert_chapter.php'.$editPath);
                exit;
            }

            // Validate file size
            if ($fileSize > $maxSize) {
                $_SESSION['error'] = "File size exceeds the maximum limit of 3MB";
                header('Location: ../upsert_chapter.php'.$editPath);
                exit;
            }

            try {
                // Upload to Cloudflare R2
                $result = $s3Client->putObject([
                    'Bucket' => $bucketName,
                    'Key' => $mangaSecureId.'/'. $newFileName,
                    'SourceFile' => $fileTmpName,
                    'ACL' => 'public-read',
                ]);
                // MAGIC CODE
                $newURL[] = 'https://pub-4c611765f21e41988e62321652b5623f.r2.dev/'.$mangaSecureId.'/'.$newFileName;
            } catch (AwsException $e) {
                $_SESSION['error'] = "Error uploading file : " . $e->getMessage();
                header('Location: ../upsert_chapter.php'.$editPath);
                exit;                  
            }
        }

        $imgURL = json_encode($newURL);

        $sql = "UPDATE chapters SET name = :name , img_url = :img_url WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':name', $title, PDO::PARAM_STR);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->bindParam(':img_url', $imgURL , PDO::PARAM_STR);
        $stmt->execute();

        unset($_SESSION['error']);
        header('Location: ../upsert_chapter.php?q='.$mangaSecureId);
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}