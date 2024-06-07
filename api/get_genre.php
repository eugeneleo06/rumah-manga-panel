<?php 
ob_start();

session_start();
if (!isset($_SESSION["username"])) {
    header('Location: 404.php');
}

include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try{
        $sql = 'SELECT * FROM genres';
        $stmt = $db->query($sql);
        
        $genres = $stmt->fetchAll();
    } catch(PDOException $e){
        echo "Connection failed: " . $e->getMessage();
    }
}
?>