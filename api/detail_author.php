<?php 

if (!isset($_SESSION["username"])) {
    header('Location: 404.php');
}

include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    if (isset($secure_id)) {
        try{
            $sql = "SELECT * FROM authors WHERE secure_id = :secure_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
            $stmt->execute();
            $author = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch(Exception $e){
            echo "Connection failed: " . $e->getMessage();
        }
    }
}