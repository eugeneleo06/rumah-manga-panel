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

        $manga_title = htmlspecialchars($_POST['title']);
        $author = htmlspecialchars($_POST['author']);
        $genres = $_POST['genres'];
        $genres = json_encode($genres);
        $status = htmlspecialchars($_POST['status']);
        $synopsis = htmlspecialchars($_POST['synopsis']);
        $secureId = htmlspecialchars($_POST['secure_id']);

        # COVER IMAGE
        // $coverImg = $_POST['cover_image'];

        # CHAPTERS
        $chapters = $_POST['chapters'];

        foreach ($chapters as $index => $chapter) {
            $title = htmlspecialchars($chapter['title']);
            if (isset($_FILES['chapters']['name'][$index]['file']) && !empty($_FILES['chapters']['name'][$index]['file'])) {
                $fileCount = count($_FILES['chapters']['name'][$index]['file']);

                for ($i = 0; $i < $fileCount; $i++) {
                    $fileName = Uuid::uuid1()->toString();
                    $fileTmpName = $_FILES['chapters']['tmp_name'][$index]['file'][$i];
                    $fileSize = $_FILES['chapters']['size'][$index]['file'][$i];
                    $fileError = $_FILES['chapters']['error'][$index]['file'][$i];
                    $fileType = $_FILES['chapters']['type'][$index]['file'][$i];

                    $formats = explode('/', $fileType)[1];

                    if ($fileError === UPLOAD_ERR_OK) {
                        $uploadDir = 'uploads/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0777, true);
                        }
                        $uploadFilePath = $uploadDir . basename($fileName);

                        if (move_uploaded_file($fileTmpName, $uploadFilePath)) {
                            echo "File uploaded: " . htmlspecialchars($fileName) . "<br>";

                            // Upload file to Cloudflare R2
                            try {
                                $result = $s3Client->putObject([
                                    'Bucket' => $bucketName,
                                    'Key' => $secureId.'/'.$fileName.'.'.$formats,
                                    'SourceFile' => $uploadFilePath,
                                    'ACL' => 'public-read',
                                ]);

                                echo "File uploaded to R2: " . htmlspecialchars($result['ObjectURL']) . "<br>";
                            } catch (AwsException $e) {
                                echo "Error uploading to R2: " . $e->getMessage() . "<br>";
                            }
                        } else {
                            echo "Error moving file: " . htmlspecialchars($fileName) . "<br>";
                        }
                    } else {
                        echo "Error uploading file: " . htmlspecialchars($fileName) . "<br>";
                    }
                }
            } else {
                echo "No files uploaded for this chapter.<br>";
            }

            echo "<hr>";
        }


    } catch(Exception $e){
        echo "Connection failed: " . $e->getMessage();
    }
} 
?>