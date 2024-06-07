<?php


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
        $name = $_POST["name"];

        if (isset($_POST["id"]) && $_POST['id'] != "") {
            $id = htmlspecialchars($_POST['id']);

            $sql = "SELECT * FROM genres WHERE name=:name AND id != :id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_STR);
            $stmt->execute();
            $exist = $stmt->fetchAll();
            if ($exist) {
                $_SESSION['error'] = "Name already exists";
                header('Location: ../upsert_genre.php?q='.$id);
                exit;
            }

            $sql = "UPDATE genres SET name=:name WHERE id=:id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $sql = "SELECT * FROM genres WHERE name=:name";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
            $exist = $stmt->fetchAll();
            if ($exist) {
                $_SESSION['error'] = "Name already exists";
                header('Location: ../upsert_genre.php?q='.$id);
                exit;
            }

            $sql = "INSERT INTO genres (name) VALUES (:name)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->execute();
        }

        unset($_SESSION['error']);
        header('Location: ../genre.php');
        exit;
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}