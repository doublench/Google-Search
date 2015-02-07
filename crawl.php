<?php
set_time_limit(0);
require 'simple_html_dom.php';
require 'stemmer.php';

/**
 * Подключаемся к бд
 */
$pdo = new PDO('mysql:host=localhost;dbname=amazon', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->query('SET NAMES utf8');

/**
 * Пишем ссылку в бд с котрой начнем
 */
//$pdo->beginTransaction();
$query = $pdo->prepare("INSERT INTO page (url, title) VALUES ('wiki/SimCity.html', 'index page')");
$query->execute();
//$pdo->commit();

$count = 0;
$query = $pdo->prepare("SELECT url FROM page ORDER BY id LIMIT ?, 1");
$query->execute([$count]);
$row = $query->fetch(PDO::FETCH_ASSOC);
while ($row !== false) { // Работаем в цикле пока не пройдем все ссылки
    /**
     * Получаем структуры страницы с которой работаем
     */
    echo $row['url'] . "<br>";
    flush();
    $current_url = $row['url'];
    $html = @file_get_html($current_url);
    if ($html === false) {
        ++$count;
        $query = $pdo->prepare("SELECT url FROM page ORDER BY id LIMIT ?, 1");
        $query->execute([$count]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        continue;
    }

    /**
     * Получаем все ссылки
     */
    $pages = array();
    foreach ($html->find('a') as $value) {
        $pages[] = array('wiki/' . $value->href, $value->title); // Пишем ссылки и тайтл в массив
    }


    /**
     * Получаем текст
     */
    $text = $html->plaintext;
    $text = preg_replace("/[^a-z]+/msi", ' ', $text); // Оставляем только символы лат.алфав.
    $words = preg_split("/[[:space:],]+/", $text); // Делим строку на слова
    $words = array_diff($words, array("")); // Удаляем пустые элементы массива

    /**
     * Пишем найденное в бд
     */
    /**
     * Пишем ссылки
     */
    //$pdo->beginTransaction();
    $query = $pdo->prepare("INSERT IGNORE page (url, title) VALUES (?, ?)");
    foreach ($pages as $value) {
        $query->execute(array($value[0], $value[1]));
    }
    //$pdo->commit();

    /**
     * Пишем слова
     */
    //$pdo->beginTransaction();
    $query = $pdo->prepare("INSERT IGNORE term (word) VALUES (?)");
    foreach ($words as $value) {
        $query->execute(array(PorterStemmer::Stem($value)));
    }
    //$pdo->commit();

    /**
     * Заполняем term_page
     */
    $query = $pdo->prepare("SELECT id FROM page WHERE url = ?"); // Получаем id текущей ссылки
    $query->execute([$current_url]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
    $pid = $row['id'];


    $query = $pdo->prepare("SELECT id FROM term WHERE word = ?"); // Получаем id всех слов страницы
    $words_id = array();
    foreach ($words as $value) {
        $query->execute([$value]);
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $words_id[] = $row['id'];
    }

    //$pdo->beginTransaction();
    $query = $pdo->prepare("INSERT INTO term_page (tid, pid) VALUES (?, ?)"); // Пишем в term_page
    foreach ($words_id as $value) {
        $query->execute(array($value, $pid));
    }
    //$pdo->commit();

    /**
     * Заполянем links
     */
    $query = $pdo->prepare("SELECT id FROM page WHERE url = ?"); // Получаем id всех сcылок страницы
    $pages_id = array();
    foreach ($pages as $value) {
        $query->execute(array($value[0]));
        $row = $query->fetch(PDO::FETCH_ASSOC);
        $pages_id[] = $row['id'];
    }

    //$pdo->beginTransaction();
    $query = $pdo->prepare("INSERT INTO link (page1, page2) VALUES (?, ?)"); // Пишем в link
    foreach ($pages_id as $value) {
        $query->execute(array($pid, $value));
    }
    //$pdo->commit();

    ++$count;
    $query = $pdo->prepare("SELECT url FROM page ORDER BY id LIMIT ?, 1");
    $query->execute([$count]);
    $row = $query->fetch(PDO::FETCH_ASSOC);
}
$pdo = null; // Закрывем содинение с бд