<?php 
    $dsn = 'mysql:host=localhost;dbname=rumahman_rumah_manga';
    $username = 'root';
    $password = '';
    try {
        $db = new PDO($dsn, $username, $password);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }

