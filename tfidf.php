<?php
set_time_limit(0);

$pdo = new PDO('mysql:host=localhost;dbname=amazon', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->query('SET NAMES utf8');

$count = 1;
$query = $pdo->prepare("SELECT tid, pid, tf FROM term_page ORDER BY tid LIMIT ?, 1");
$query->execute(array($count));
$row = $query->fetch(PDO::FETCH_ASSOC);
while ($row !== false) {
    $term_id = $row['tid']; // Айди текущего слова
    $page_id = $row['pid']; // Айди документа слова
    $term_tf = $row['tf'];

    $query = $pdo->prepare("SELECT idf FROM term WHERE id = ?");
    $query->execute(array($term_id));
    $row = $query->fetch(PDO::FETCH_ASSOC); 
    $idf = $row['idf'];

    $tfidf = $term_tf * $idf;

    $pdo->beginTransaction();
    $query = $pdo->prepare("UPDATE term_page SET tfidf = ? WHERE tid = ?");
    $query->execute(array($tfidf, $term_id));
    $pdo->commit();
    
    ++$count;
    $query = $pdo->prepare("SELECT tid, pid, tf FROM term_page ORDER BY tid LIMIT ?, 1");
    $query->execute(array($count));
    $row = $query->fetch(PDO::FETCH_ASSOC);
}

$pdo = null;