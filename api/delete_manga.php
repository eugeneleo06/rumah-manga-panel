<?php

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: ../404.php');
}


$path = '../db.sqlite';

include('../config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        $secure_id = htmlspecialchars($_GET['q']);

        $sql = "DELETE FROM mangas WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
    
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();
        unset($_SESSION['error']);
        header('Location: ../manga.php');
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}