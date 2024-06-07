<?php

require '../vendor/autoload.php';

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
        $name = $_POST["name"];

        if (isset($_POST["secure_id"]) && $_POST['secure_id'] != "") {
            $id = htmlspecialchars($_POST['secure_id']);

            $sql = "UPDATE authors SET name=:name WHERE secure_id=:secure_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $newSecureId = Uuid::uuid1()->toString();
            $sql = "INSERT INTO authors (name,secure_id) VALUES (:name,:secure_id)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':name', $name, PDO::PARAM_STR);
            $stmt->bindParam(':secure_id', $newSecureId , PDO::PARAM_STR);
            $stmt->execute();
        }

        unset($_SESSION['error']);
        header('Location: ../author.php');
        exit;
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}