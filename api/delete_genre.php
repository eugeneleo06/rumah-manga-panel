<?php
ob_start();

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: ../404.php');
    exit;
}


$path = '../db.sqlite';

include('../config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        $id = htmlspecialchars($_GET['q']);


        $sql = "SELECT * FROM mangas WHERE genres_id LIKE ?";
        $stmt = $db->prepare($sql);
        $stmt->execute(array('%"'.$id.'"%'));
        $mangas = $stmt->fetchAll();

        if ($mangas) {
            $_SESSION['error'] = "Please delete mangas with this genre first";
            header('Location: ../genre.php');
            exit;
        }

        $sql = "DELETE FROM genres WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_STR);
        $stmt->execute();

        unset($_SESSION['error']);
        header('Location: ../genre.php');
        exit;
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}