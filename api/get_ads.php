<?php 
ob_start();

if (!isset($_SESSION["username"])) {
    header('Location: 404.php');
    exit;
}

include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try{
        $sql = 'SELECT * FROM ads'; // 4 = footer;
        $stmt = $db->query($sql);
        
        $ads = $stmt->fetchAll();
    } catch(PDOException $e){
        echo "Connection failed: " . $e->getMessage();
    }
}
?>