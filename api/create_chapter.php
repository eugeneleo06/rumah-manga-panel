<?php

require '../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Ramsey\Uuid\Uuid;

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: ../404.php');
}


$path = '../db.sqlite';

include('../config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $secure_id = htmlspecialchars($_POST['secure_id']);

        $sql = "SELECT id FROM mangas WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();
        $manga_id = $stmt->fetchColumn();

        $editPath = '?q='.$secure_id;


        $chapters = $_POST['chapters'];

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 3 * 1024 * 1024; // 3MB

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

        $db->beginTransaction();
        foreach($chapters as $index=>&$chapter) {
            $title = $chapter['title'];
            $images = $_FILES['chapters']['name'][$index]['file'];
            $newURL = [];
            foreach ($images as $imageIndex => $imageName) {
                $fileTmpName = $_FILES['chapters']['tmp_name'][$index]['file'][$imageIndex];
                $fileSize = $_FILES['chapters']['size'][$index]['file'][$imageIndex];
                $fileType = $_FILES['chapters']['type'][$index]['file'][$imageIndex];
                $fileExtension = pathinfo($_FILES['chapters']['name'][$index]['file'][$imageIndex], PATHINFO_EXTENSION);
                $newFileName = Uuid::uuid1()->toString() . '.' . $fileExtension;

                // Validate file extension
                if (!in_array(strtolower($fileExtension), $allowed)) {
                    $_SESSION['error'] = "Please upload a valid file type (jpg, jpeg, png, webp)";
                    $db->rollBack();
                    header('Location: ../upsert_chapter.php'.$editPath);
                    exit;
                }

                // Validate file size
                if ($fileSize > $maxSize) {
                    $_SESSION['error'] = "File size exceeds the maximum limit of 3MB";
                    $db->rollBack();
                    header('Location: ../upsert_chapter.php'.$editPath);
                    exit;
                }

                try {
                    // Upload to Cloudflare R2
                    $result = $s3Client->putObject([
                        'Bucket' => $bucketName,
                        'Key' => $manga_id.'/'. $newFileName,
                        'SourceFile' => $fileTmpName,
                        'ACL' => 'public-read',
                    ]);
    
                    $newURL[] = 'https://pub-4c611765f21e41988e62321652b5623f.r2.dev/'.$manga_id.'/'.$newFileName;
                } catch (AwsException $e) {
                    $_SESSION['error'] = "Error uploading file : " . $e->getMessage();
                    $db->rollBack();
                    header('Location: ../upsert_chapter.php'.$editPath);
                    exit;    
                }
            }
            $imgURL = json_encode($newURL);
            $uuid =  Uuid::uuid1()->toString();

            $sql = "INSERT INTO chapters (created_date, secure_id, manga_id, name, img_url) VALUES (:created_date, :secure_id, :manga_id, :name, :img_url)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':created_date', date("Y-m-d"), PDO::PARAM_STR);
            $stmt->bindParam(':name', $title, PDO::PARAM_STR);
            $stmt->bindParam(':secure_id', $uuid, PDO::PARAM_STR);
            $stmt->bindParam(':img_url', $imgURL , PDO::PARAM_STR);
            $stmt->bindParam(':manga_id', $manga_id , PDO::PARAM_INT);
            $stmt->execute();
        }
        $db->commit();
        unset($_SESSION['error']);
        header('Location: ../upsert_chapter.php?q='.$secure_id);
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}