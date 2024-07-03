<?php

require '../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Ramsey\Uuid\Uuid;


ob_start();

session_start();
if (!isset($_SESSION["username"])) {
    header('Location: ../404.php');
    exit;
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
            'endpoint' => 'https://110919c691af57fd4283c3c05211252d.r2.cloudflarestorage.com',
            'credentials' => [
                'key' => '020c964526eb3f64d899f9d5b6905d7a',
                'secret' => '0fd485ace28b70d417ac19f249f2cb2b0836c6051f854c02ed9e464de3e2b279',
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
                $newURL[] = 'https://pub-2bfa6b528bf54fa9a840c5feca5a3a76.r2.dev/'.$mangaSecureId.'/'.$newFileName;
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