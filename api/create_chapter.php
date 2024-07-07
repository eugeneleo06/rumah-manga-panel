<?php
ob_start();

require '../vendor/autoload.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Ramsey\Uuid\Uuid;

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

        $sql = "SELECT id FROM mangas WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();
        $manga_id = $stmt->fetchColumn();

        $editPath = '?q='.$secure_id;


        $chapters = $_POST['chapters'];

        $allowed = ['jpg', 'jpeg', 'png', 'webp'];
        $maxSize = 10 * 1024 * 1024; // 3MB

        // Cloudflare R2 configuration
        $bucketName = 'rumah-manga';

        // Instantiate the S3 client with your Cloudflare R2 credentials and endpoint
        $s3Client = new S3Client([
            'region' => 'auto',
            'version' => 'latest',
            'endpoint' => 'https://110919c691af57fd4283c3c05211252d.r2.cloudflarestorage.com/',
            'credentials' => [
                'key' => '020c964526eb3f64d899f9d5b6905d7a',
                'secret' => '0fd485ace28b70d417ac19f249f2cb2b0836c6051f854c02ed9e464de3e2b279',
            ],
        ]);

        $db->beginTransaction();
        foreach($chapters as $index=>$chapter) {
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
                }

                // Validate file size
                if ($fileSize > $maxSize) {
                    $_SESSION['error'] = "File size exceeds the maximum limit of 10MB";
                    $db->rollBack();
                    header('Location: ../upsert_chapter.php'.$editPath);
                }

                try {
                    // Upload to Cloudflare R2
                    $result = $s3Client->putObject([
                        'Bucket' => $bucketName,
                        'Key' => $manga_id.'/'. $newFileName,
                        'SourceFile' => $fileTmpName,
                        'ACL' => 'public-read',
                    ]);
    
                    $newURL[] = 'https://pub-2bfa6b528bf54fa9a840c5feca5a3a76.r2.dev/'.$manga_id.'/'.$newFileName;
                } catch (AwsException $e) {
                    $_SESSION['error'] = "Error uploading file : " . $e->getMessage();
                    $db->rollBack(); 
                    header('Location: ../upsert_chapter.php'.$editPath);
                }
            }
            $imgURL = json_encode($newURL);
            $uuid =  Uuid::uuid1()->toString();

            $sql = "INSERT INTO chapters (secure_id, manga_id, name, img_url) VALUES (:secure_id, :manga_id, :name, :img_url)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $title, PDO::PARAM_STR);
            $stmt->bindParam(':secure_id', $uuid, PDO::PARAM_STR);
            $stmt->bindParam(':img_url', $imgURL , PDO::PARAM_STR);
            $stmt->bindParam(':manga_id', $manga_id , PDO::PARAM_INT);
            $stmt->execute();

            $sql = "UPDATE mangas SET modified_date='".date('Y-m-d H:i:s')."' WHERE id = ".$manga_id;
            $stmt = $db->prepare($sql);
            $stmt->execute();
        }
        $db->commit();
        unset($_SESSION['error']);
        // echo 'success';
        // var_dump($_POST);
        // exit;
        header('Location: ../upsert_chapter.php?q='.$secure_id);
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}