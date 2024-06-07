<?php 
ob_start();

session_start();
if (!isset($_SESSION["username"])) {
    header('Location: 404.php');
    exit;
}

include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try{
        $sql = 'SELECT * FROM genres';
        $stmt = $db->query($sql);
        
        $genres = $stmt->fetchAll();

        $noGenre = count($genres);

        $sql = 'SELECT * FROM authors';
        $stmt = $db->query($sql);
        
        $authors = $stmt->fetchAll();

        $noAuthor = count($authors);

        $sql = 'SELECT * FROM mangas';
        $stmt = $db->query($sql);
        
        $mangas = $stmt->fetchAll();

        $noManga = count($mangas);

        $sql = 'SELECT * FROM chapters';
        $stmt = $db->query($sql);
        
        $chapters = $stmt->fetchAll();

        $noChapter = count($chapters);

    } catch(PDOException $e){
        echo "Connection failed: " . $e->getMessage();
    }
}
?>