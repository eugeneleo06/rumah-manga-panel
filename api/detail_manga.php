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
        if(isset($secure_id)) {
            $sql = "SELECT * FROM mangas m WHERE secure_id='".$secure_id."' LIMIT 1";
            $stmt = $db->query($sql);
            $manga = $stmt->fetch(PDO::FETCH_ASSOC);

            $sql = "SELECT * FROM chapters c WHERE manga_id=".$manga['id']." ORDER BY id DESC";
            $stmt = $db->query($sql);
            $chapters = $stmt->fetchAll();

            foreach($chapters as $index=>$chapter) {
                $chapters[$index]['amount'] = count(json_decode($chapter['img_url']));
            }

            # DECODE GENRES ID
            $genreIds = json_decode($manga['genres_id']);
        }

        # GET ALL GENRES
        $sql = "SELECT * FROM genres";
        $stmt = $db->query($sql);
        $genres = $stmt->fetchAll(); 

        # GET ALL AUTHORS
        $sql = "SELECT * FROM authors";
        $stmt = $db->query($sql);
        $authors = $stmt->fetchAll();


    } catch(Exception $e){
        echo "Connection failed: " . $e->getMessage();
    }
}