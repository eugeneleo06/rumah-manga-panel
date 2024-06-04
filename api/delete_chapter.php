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

        $sql = "SELECT manga_id FROM chapters WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();
        $manga_id = $stmt->fetchColumn();

        $sql = "SELECT secure_id FROM mangas WHERE id = :manga_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':manga_id', $manga_id, PDO::PARAM_STR);
        $stmt->execute();
        $mangaSecureId = $stmt->fetchColumn();

        $sql = "DELETE FROM chapters WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();

        unset($_SESSION['error']);
        header('Location: ../upsert_chapter.php?q='.$mangaSecureId);
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}