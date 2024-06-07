<?php
ob_start();

session_start();

if (!isset($_SESSION["username"])) {
    header('Location: ../404.php');
}


$path = '../db.sqlite';

include('../config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try {
        $secure_id = htmlspecialchars($_GET['q']);

        $db->beginTransaction();
        $sql = "SELECT id FROM mangas WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();
        $mangaId = $stmt->fetchColumn();

        $sql = "DELETE FROM chapters WHERE manga_id = :manga_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':manga_id', $mangaId, PDO::PARAM_STR);
        $stmt->execute();

        $sql = "DELETE FROM mangas WHERE secure_id = :secure_id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
        $stmt->execute();
        $db->commit();

        unset($_SESSION['error']);
        header('Location: ../manga.php');
    } catch (PDOException $e) { 
        $db->rollBack();
        echo $e->getMessage();
        exit;
    }

}