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

        $manga_title = htmlspecialchars($_POST['title']);
        $author = htmlspecialchars($_POST['author']);
        $genres = $_POST['genres'];
        $genres = json_encode($genres);
        $status = htmlspecialchars($_POST['status']);
        $synopsis = htmlspecialchars($_POST['synopsis']);
        $adsURL = htmlspecialchars($_POST['ads_url']);
        $newURLAds = "";

        $isEdit = false;

        if(isset($_POST['secure_id']) && $_POST['secure_id'] != "") {
            $isEdit = true;
        }

        if($isEdit) {
            $secure_id = htmlspecialchars($_POST['secure_id']);
            $sql = "SELECT * FROM mangas m WHERE title='".$manga_title."' AND  secure_id <> '".$secure_id."' LIMIT 1";
            $stmt = $db->query($sql);
            $manga = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($manga) { //if title duplicate
                $_SESSION['error'] = "Title already exists.";
                $editPath = "?q=". $secure_id;
                header('Location: ../upsert_manga.php'.$editPath);
                exit;
            }
        } else {
            $secure_id = Uuid::uuid1()->toString();
            $sql = "SELECT * FROM mangas m WHERE title='".$manga_title."' LIMIT 1";
            $stmt = $db->query($sql);
            $manga = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($manga) { //if title duplicate
                $_SESSION['error'] = "Title already exists.";
                header('Location: ../upsert_manga.php');
                exit;
            }
        }

        $editPath = "";
        if ($isEdit){
            $editPath = "?q=". $secure_id;
        }

        if (isset($_FILES['headline_image']) && $_FILES['headline_image']['error'] == 0) {
            $file = $_FILES['headline_image'];
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
                header('Location: ../upsert_manga.php'.$editPath);
                exit;
            }

             // Validate file size
            if ($fileSize > $maxSize) {
                $_SESSION['error'] = "File size exceeds the maximum limit of 3MB";
                header('Location: ../upsert_manga.php'.$editPath);
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
                $newURLHeadline = 'https://pub-2bfa6b528bf54fa9a840c5feca5a3a76.r2.dev/'.$secure_id.'/'.$newFileName;
            } catch (AwsException $e) {
                $_SESSION['error'] = "Error uploading file : " . $e->getMessage();
                header('Location: ../upsert_manga.php'.$editPath);
                exit;            
            }
        }

        if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] == 0) {
            $file = $_FILES['cover_image'];
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
                header('Location: ../upsert_manga.php'.$editPath);
                exit;
            }

             // Validate file size
            if ($fileSize > $maxSize) {
                $_SESSION['error'] = "File size exceeds the maximum limit of 3MB";
                header('Location: ../upsert_manga.php'.$editPath);
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
                header('Location: ../upsert_manga.php'.$editPath);
                exit;            
            }
        }

        if (isset($_FILES['ads_image']) && $_FILES['ads_image']['error'] == 0) {
            $file = $_FILES['ads_image'];
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
                header('Location: ../upsert_manga.php'.$editPath);
                exit;
            }

             // Validate file size
            if ($fileSize > $maxSize) {
                $_SESSION['error'] = "File size exceeds the maximum limit of 3MB";
                header('Location: ../upsert_manga.php'.$editPath);
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
                $newURLAds = 'https://pub-2bfa6b528bf54fa9a840c5feca5a3a76.r2.dev/'.$secure_id.'/'.$newFileName;
            } catch (AwsException $e) {
                $_SESSION['error'] = "Error uploading file : " . $e->getMessage();
                header('Location: ../upsert_manga.php'.$editPath);
                exit;            
            }
        }

        if ($isEdit){
            $sql = "SELECT * FROM mangas m WHERE secure_id ='".$secure_id."' LIMIT 1";
            $stmt = $db->query($sql);
            $manga = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($manga) {
                if(isset($newURL)){
                    $manga['cover_img'] = $newURL;
                }
                if(isset($newURLHeadline)) {
                    $manga['headline_img'] = $newURLHeadline;
                }
                if(isset($newURLAds)) {
                    $manga['ads_img'] = $newURLAds;
                }
                $sql = "UPDATE mangas set title = :title,  author_id = :author_id, genres_id = :genre_id, status = :status, synopsis = :synopsis, cover_img = :cover_img, headline_img = :headline_img, ads_img = :ads_img, ads_url = :ads_url WHERE secure_id = :secure_id";
                $stmt = $db->prepare($sql);
                $stmt->bindParam(':title', $manga_title, PDO::PARAM_STR);
                $stmt->bindParam(':author_id', $author, PDO::PARAM_STR);
                $stmt->bindParam(':genre_id', $genres, PDO::PARAM_STR);
                $stmt->bindParam(':status', $status, PDO::PARAM_STR);
                $stmt->bindParam(':synopsis', $synopsis, PDO::PARAM_STR);
                $stmt->bindParam(':cover_img', $manga['cover_img'], PDO::PARAM_STR);
                $stmt->bindParam(':headline_img', $manga['headline_img'], PDO::PARAM_STR);
                $stmt->bindParam(':ads_img', $manga['ads_img'], PDO::PARAM_STR);
                $stmt->bindParam(':ads_url', $adsURL, PDO::PARAM_STR);
                $stmt->bindParam(':secure_id',$secure_id, PDO::PARAM_STR);
                $stmt->execute();

                // Check the number of affected rows
                if ($stmt->rowCount() > 0) {
                    unset($_SESSION['error']);
                    header('Location: ../manga.php');
                    exit;
                } else {
                    $_SESSION['error'] = "Internal server error";
                    header('Location: ../upsert_manga.php'.$editPath);
                    exit;
                }

            } else{
                $_SESSION['error'] = "Internal server error";
                header('Location: ../upsert_manga.php'.$editPath);
                exit;
            }
        } else {
                try {
                    $sql2 = "INSERT INTO mangas (title, secure_id, author_id, genres_id, status, synopsis, cover_img, headline_img, ads_img, ads_url) VALUES (:title, :secure_id, :author_id, :genres_id, :status, :synopsis, :cover_img, :headline_img, :ads_img, :ads_url)";
                    $stmt2 = $db->prepare($sql2);
                    
                    $stmt2->bindParam(':title', $manga_title, PDO::PARAM_STR);
                    $stmt2->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
                    $stmt2->bindParam(':author_id', $author, PDO::PARAM_STR);
                    $stmt2->bindParam(':genres_id', $genres, PDO::PARAM_STR);
                    $stmt2->bindParam(':status', $status, PDO::PARAM_STR);
                    $stmt2->bindParam(':synopsis', $synopsis, PDO::PARAM_STR);
                    $stmt2->bindParam(':cover_img', $newURL, PDO::PARAM_STR);
                    $stmt2->bindParam(':headline_img', $newURLHeadline, PDO::PARAM_STR);
                    $stmt2->bindParam(':ads_img', $newURLAds, PDO::PARAM_STR);
                    $stmt2->bindParam(':ads_url', $adsURL, PDO::PARAM_STR);
                    // Execute the statement
                    $stmt2->execute();
                    unset($_SESSION['error']);
                    header('Location: ../manga.php');
                    exit;
                } catch (PDOException $e) {
                    echo $e->getMessage();
                    exit;
                }
        }

    } catch(Exception $e){
        echo "Connection failed: " . $e->getMessage();
    }
} 
?>