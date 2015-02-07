<?php
set_time_limit(0);

$pdo = new PDO('mysql:host=localhost;dbname=amazon', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->query('SET NAMES utf8');

$count = 1;
$query = $pdo->prepare("SELECT tid, pid FROM term_page ORDER BY tid LIMIT ?, 1");
$query->execute(array($count));
$row = $query->fetch(PDO::FETCH_ASSOC);
while ($row !== false) {
    $term_id = $row['tid']; // Айди текущего слова
    $page_id = $row['pid']; // Айди документа слова

    $query = $pdo->prepare("SELECT * FROM term_page WHERE pid = ?");
    $query->execute(array($page_id));
    $word_count = $query->rowCount(); // Кол-во слов в документе

    $query = $pdo->prepare("SELECT * FROM term_page WHERE tid = ? AND pid = ?");
    $query->execute(array($term_id, $page_id));
    $this_word_count = $query->rowCount(); // Кол-во данного слова в документе
    
    $tf = $this_word_count/$word_count;

    $pdo->beginTransaction();
    $query = $pdo->prepare("UPDATE term_page SET tf = ? WHERE tid = ?");
    $query->execute(array($tf, $term_id));
    $pdo->commit();
    
    ++$count;
    $query = $pdo->prepare("SELECT tid, pid FROM term_page ORDER BY tid LIMIT ?, 1");
    $query->execute(array($count));
    $row = $query->fetch(PDO::FETCH_ASSOC);
}

$pdo = null;