<?php 
ob_start();

if (!isset($_SESSION["username"])) {
    header('Location: 404.php');
    exit;
}

include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($secure_id)) {
        try{
            $id = $secure_id;
            $sql = "SELECT * FROM genres WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            $genre = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e){
            echo "Connection failed: " . $e->getMessage();
        }
    }
}