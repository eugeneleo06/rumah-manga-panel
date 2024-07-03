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
            'endpoint' => 'https://110919c691af57fd4283c3c05211252d.r2.cloudflarestorage.com/',
            'credentials' => [
                'key' => '020c964526eb3f64d899f9d5b6905d7a',
                'secret' => '0fd485ace28b70d417ac19f249f2cb2b0836c6051f854c02ed9e464de3e2b279
',
            ],
        ]);

        $name = htmlspecialchars($_POST['name']);
        $url = htmlspecialchars($_POST['url']);

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
                $newURL = 'https://pub-2bfa6b528bf54fa9a840c5feca5a3a76.r2.dev/'.$secure_id.'/'.$newFileName;
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
                $sql = "UPDATE ads set name = :name, img_url = :img_url, url = :url WHERE secure_id = :secure_id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':name', $name, PDO::PARAM_STR);
                $stmt->bindParam(':img_url', $ads['img_url'], PDO::PARAM_STR);
                $stmt->bindParam(':url', $url, PDO::PARAM_STR);
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