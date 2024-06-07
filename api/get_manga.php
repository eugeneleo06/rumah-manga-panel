<?php 
ob_start();

session_start();
if (!isset($_SESSION["username"])) {
    header('Location: ../404.php');
    exit;
}

include('config/db.php');

if ($_SERVER["REQUEST_METHOD"] == "GET") {
    try{
        $sql = 'SELECT * FROM mangas';
        $stmt = $db->query($sql);
        
        $mangas = $stmt->fetchAll();

        $i = 0;
        foreach($mangas as $manga) {
            # GET GENRE
            $genreIds = json_decode($manga['genres_id']);
            $genreNames = array();
            foreach ($genreIds as $genreId) {
                $sql = "SELECT name FROM genres WHERE id = ".$genreId;
                $stmt = $db->query($sql);
                $genre = $stmt->fetchColumn();
                if ($genre) {
                    $genreNames[] = $genre;
                }
            }
            $genreNamesCombined = implode(", ", $genreNames);
            $mangas[$i]['genre'] = $genreNamesCombined;


            # GET CHAPTERS AMOUNT
            $sql = 'SELECT * FROM chapters WHERE manga_id='.$manga['id'];
            $stmt = $db->query($sql);
            $chapters = $stmt->fetchAll();
            $mangas[$i]['chapters'] = count($chapters);

            # GET AUTHORS
            $sql = 'SELECT name FROM authors WHERE id = '.$manga['author_id'];
            $stmt = $db->query($sql);
            $author = $stmt->fetchColumn();
            $mangas[$i]['author'] = $author;
            $i++;
        }

    } catch(PDOException $e){
        echo "Connection failed: " . $e->getMessage();
    }
}
?>