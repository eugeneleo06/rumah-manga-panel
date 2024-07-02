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
        $username = $_POST["username"];
        $password = md5(md5($_POST["password"]));

        if (isset($_POST["secure_id"]) && $_POST['secure_id'] != "") {
            $secure_id = htmlspecialchars($_POST['secure_id']);

            $sql = "SELECT * FROM admins WHERE username=:username AND secure_id != :secure_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
            $stmt->execute();
            $exist = $stmt->fetchAll();
            if ($exist) {
                $_SESSION['error'] = "Username already exists";
                header('Location: ../upsert_admin.php?q='.$secure_id);
                exit;
            }

            $sql = "UPDATE admins SET username=:username, password=:password WHERE secure_id=:secure_id";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
            $stmt->execute();
        } else {
            $sql = "SELECT * FROM admins WHERE username=:username";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $exist = $stmt->fetchAll();
            if ($exist) {
                $_SESSION['error'] = "Username already exists";
                header('Location: ../upsert_admin.php?q='.$secure_id);
                exit;
            }

            $secure_id = Uuid::uuid1()->toString();

            $sql = "INSERT INTO admins (username,password,secure_id) VALUES (:username,:password,:secure_id)";
            $stmt = $db->prepare($sql);
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->bindParam(':password', $password, PDO::PARAM_STR);
            $stmt->bindParam(':secure_id', $secure_id, PDO::PARAM_STR);
            $stmt->execute();
        }

        unset($_SESSION['error']);
        header('Location: ../admin.php');
        exit;
    } catch (PDOException $e) { 
        echo $e->getMessage();
        exit;
    }

}