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

        
        $sql = "SELECT id FROM authors WHERE secure_id=:secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();
        $authorId = $stmt->fetchColumn();


        $sql = "SELECT * FROM mangas WHERE author_id = :author_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':author_id', $authorId, PDO::PARAM_INT);
        $stmt->execute();
        $mangas = $stmt->fetchAll();

        if ($mangas) {
            $_SESSION['error'] = "Please delete mangas with this author first";
            header('Location: ../author.php');
            exit;
        }

        $sql = "DELETE FROM authors WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $authorId, PDO::PARAM_STR);
        $stmt->execute();

        unset($_SESSION['error']);
        header('Location: ../author.php');
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}