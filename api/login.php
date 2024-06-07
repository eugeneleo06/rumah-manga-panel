<?php
ob_start();

session_start();
$path = '../db.sqlite';
include('../config/db.php');
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try{
        if (isset($_POST['username']) && isset($_POST['password'])) {
            $usn = htmlspecialchars($_POST['username']);
            $pass = htmlspecialchars($_POST['password']);
        
            $selectData = "SELECT * FROM admins WHERE username='".$usn."' AND password='".md5($pass)."' LIMIT 1";
            $stmt = $db->query($selectData);
            $users = $stmt->fetch(PDO::FETCH_ASSOC);

            if($users){
                $_SESSION['username'] = $users['username'];
                unset($_SESSION['error']);
                header('Location: ../index.php');
            } else {
                $_SESSION['error'] = "Username atau password salah";
                header('Location: ../login.php');
            }
        }
    } catch(Exception $e){
        echo "Connection failed: " . $e->getMessage();
    }
}


?>