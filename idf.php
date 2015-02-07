<?php
set_time_limit(0);

$pdo = new PDO('mysql:host=localhost;dbname=amazon', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->query('SET NAMES utf8');

$query = $pdo->query("SELECT COUNT(*) FROM page");
$row = $query->fetch(PDO::FETCH_ASSOC);
$doc_quantity = $row['COUNT(*)'] - 1; // Кол-во документов
//var_dump($doc_quantity);

$count = 0;
$query = $pdo->prepare("SELECT id, word FROM term ORDER BY id LIMIT ?, 1");
$query->execute(array($count));
$row = $query->fetch(PDO::FETCH_ASSOC);
while ($row !== false) {
    $term_id = $row['id']; // Айди текущего слова
    $query = $pdo->prepare("SELECT DISTINCT * FROM term_page WHERE tid = ?");
    $query->execute(array($term_id));
    $term_count = $query->rowCount(); // Кол-во документов всречающих данное слово
    //echo $term_id . " " . $term_count . "<br>";
    if($term_count == 0) {
        $term_count = 1;
    }
    $idf = log10($doc_quantity/$term_count); // ИДФ
    
    $pdo->beginTransaction();
    $query = $pdo->prepare("UPDATE term SET idf = ? WHERE id = ?");
    $query->execute(array($idf, $term_id));
    $pdo->commit();
    
    ++$count;
    $query = $pdo->prepare("SELECT id, word FROM term ORDER BY id LIMIT ?, 1");
    $query->execute(array($count));
    $row = $query->fetch(PDO::FETCH_ASSOC);
}

$pdo = null;