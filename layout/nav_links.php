<?php
// nav_links.php
$nav_links = [
    ['url' => 'index.php', 'text' => 'Dashboard'],
    ['url' => 'manga.php', 'text' => 'Manga'],
    ['url'=> 'chapter.php', 'text' => 'Chapter'],
    ['url'=> 'genre.php', 'text' => 'Genre'],
    ['url'=> 'author.php', 'text' => 'Author'],
    ['url'=> 'ads.php', 'text' => 'Ads'],
];

if (isset($_SESSION['username']) && $_SESSION['username'] == 'master') {
    $nav_links[] = ['url' => 'admin.php', 'text' => 'Admin'];
    // Add more links as needed
}

?>
