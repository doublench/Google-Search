<?php
header("Content-type: text/html; charset=utf-8");
require 'stemmer.php';

/**
 * Проверяем введенные данные
 */
if (!empty($_POST['search'])) {
    $search = $_POST['search'];
    $search = addslashes($search);
    $search = htmlspecialchars($search);
    $search = stripslashes($search);
    $words  = preg_split("/[[:space:],]+/", $search);  // Делим на слова
} else {
    die("<h3>Начните вводить запрос...</h3>");
}

/**
 * Подключаемся к бд
 */
$pdo = new PDO('mysql:host=localhost;dbname=amazon', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->query('SET NAMES utf8');

/**
 * Основной запрос
 */
$query = $pdo->prepare(" SELECT DISTINCT page.id, page.url, page.title, page.pr, term_page.tfidf
                         FROM page, term_page, term
                         WHERE term.word = ?
                         AND term.word  NOT IN (SELECT stop_word.word FROM stop_word WHERE stop_word.word = ?)
                         AND term_page.tid = term.id
                         AND page.id = term_page.pid
                         ORDER BY page.pr DESC" );
foreach ($words as $value) {
    $query->execute(array(PorterStemmer::Stem($value), PorterStemmer::Stem($value)));

    /**
     * Парсим полученные данные
     */
    ?>
    <style>
        .error-notice {
            margin: 5px 5px; /* Making sure to keep some distance from all side */
        }

        .oaerror {
            width: 90%; /* Configure it fit in your design  */
            margin: 0 auto; /* Centering Stuff */
            background-color: #FFFFFF; /* Default background */
            padding: 20px;
            border: 1px solid #eee;
            border-left-width: 5px;
            border-radius: 3px;
            margin: 0 auto;
            font-family: 'Open Sans', sans-serif;
            font-size: 16px;
        }

        .danger {
            border-left-color: #d9534f; /* Left side border color */
            background-color: rgba(217, 83, 79, 0.1); /* Same color as the left border with reduced alpha to 0.1 */
        }

        .danger strong {
            color: #d9534f;
        }

        .warning {
            border-left-color: #f0ad4e;
            background-color: rgba(240, 173, 78, 0.1);
        }

        .warning strong {
            color: #f0ad4e;
        }

        .info {
            border-left-color: #5bc0de;
            background-color: rgba(91, 192, 222, 0.1);
        }

        .info strong {
            color: #5bc0de;
        }

        .success {
            border-left-color: #2b542c;
            background-color: rgba(43, 84, 44, 0.1);
        }

        .success strong {
            color: #2b542c;
        }
    </style>
    <?php
    $success = 0;
    while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
        $success = 1;
        ?>
        <div class="row">
            <div class="col-md-100 col-md-offset-0">
                    <br><br>
                    <div class="error-notice">
                        <div class="oaerror danger">
                            <strong>Title</strong> - <?= $row['title'] ?>
                        </div>
                        <div class="oaerror warning">
                            <strong>Url</strong> - <a href="<?= $row['url'] ?>"><?= $row['url'] ?></a>
                        </div>
                        <div class="oaerror info">
                            <strong>Page Rank</strong> - <?= $row['pr'] ?>
                        </div>
                        <div class="oaerror success">
                            <strong>TF-IDF: <?= $row['tfidf'] ?> &amp; HITS</strong> - <?= "0" ?>
                        </div>
                    </div>
            </div>
        </div>
    <?php
    }
    if ($row === false && $success == 0) {
        echo "<h3>Нет результатов...</h3>";
    }
} ?>
