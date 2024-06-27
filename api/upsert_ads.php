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

        $name = htmlspecialchars($_POST['name']);

        $isEdit = false;

        if(isset($_POST['secure_id']) && $_POST['secure_id'] != "") {
            $isEdit = true;
        }

        if($isEdit) {
            $secure_id = htmlspecialchars($_POST['secure_id']);
            $sql = "SELECT * FROM ads a WHERE name='".$name."' AND  secure_id <> '".$secure_id."' LIMIT 1";
            $stmt = $db->query($sql);
            $ads = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($ads) { //if title duplicate
                $_SESSION['error'] = "Name already exists.";
                $editPath = "?q=". $secure_id;
                header('Location: ../upsert_ads.php'.$editPath);
                exit;
            }
        } else {
            $secure_id = htmlspecialchars($_POST['secure_id']);
            $sql = "SELECT * FROM ads a WHERE name='".$name."' LIMIT 1";
            $stmt = $db->query($sql);
            $ads = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($ads) { //if title duplicate
                $_SESSION['error'] = "Name already exists.";
                header('Location: ../upsert_ads.php');
                exit;
            }
        }

        $editPath = "";
        if ($isEdit){
            $editPath = "?q=". $secure_id;
        }

        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $file = $_FILES['image'];
            $allowed = ['jpg', 'jpeg', 'png', 'webp'];
            $maxSize = 3 * 1024 * 1024; // 3MB

            $fileName = $file['name'];
            $fileSize = $file['size'];
            $fileTmpPath = $file['tmp_name'];
            $fileType = $file['type'];
            $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);

             // Validate file extension
            if (!in_array(strtolower($fileExtension), $allowed)) {
                $_SESSION['error'] = "Please upload a valid file type (jpg, jpeg, png, webp)";
                header('Location: ../upsert_ads.php'.$editPath);
                exit;
            }

             // Validate file size
            if ($fileSize > $maxSize) {
                $_SESSION['error'] = "File size exceeds the maximum limit of 3MB";
                header('Location: ../upsert_ads.php'.$editPath);
                exit;
            }

            $newFileName = Uuid::uuid1()->toString() . '.' . $fileExtension;

            try {
                $result = $s3Client->putObject([
                    'Bucket' => $bucketName,
                    'Key' => $secure_id.'/'. $newFileName,
                    'SourceFile' => $fileTmpPath,
                    'ACL' => 'public-read',
                ]);
                $newURL = 'https://pub-4c611765f21e41988e62321652b5623f.r2.dev/'.$secure_id.'/'.$newFileName;
            } catch (AwsException $e) {
                $_SESSION['error'] = "Error uploading file : " . $e->getMessage();
                header('Location: ../upsert_ads.php'.$editPath);
                exit;            
            }
        }

        if ($isEdit){
            $sql = "SELECT * FROM ads a WHERE secure_id ='".$secure_id."' LIMIT 1";
            $stmt = $db->query($sql);
            $ads = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($ads) {
                if(isset($newURL)){
                    $ads['img_url'] = $newURL;
                }
                $sql = "UPDATE ads set name = :name, img_url = :img_url WHERE secure_id = :secure_id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                $stmt->bindParam(':img_url', $ads['img_url'], PDO::PARAM_STR);
                $stmt->bindParam(':secure_id',$secure_id, PDO::PARAM_STR);

                // var_dump($stmt);exit;
                $stmt->execute();

                // Check the number of affected rows
                if ($stmt->rowCount() > 0) {
                    unset($_SESSION['error']);
                    header('Location: ../ads.php');
                    exit;
                } else {
                    $_SESSION['error'] = "Internal server error";
                    header('Location: ../upsert_ads.php'.$editPath);
                    exit;
                }

            } else{
                $_SESSION['error'] = "Internal server error";
                header('Location: ../upsert_ads.php'.$editPath);
                exit;
            }
        }

    } catch(Exception $e){
        echo "Connection failed: " . $e->getMessage();
    }
} 
?>