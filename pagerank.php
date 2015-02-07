<?php
set_time_limit(0);
define('DAMPING_FACTOR', 0.85);
define('EPSILON', 0.001);

/**
 * Подключаемся к бд
 */
$pdo = new PDO('mysql:host=localhost;dbname=amazon', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->query('SET NAMES utf8');

$pr = array();
$check = true;
$iterations = 0;
do {
    /**
     * Основной цикл
     * Бегаем по всем ссылками и присваиваем для каждой свой PR
     */
    $count = 1;
    $query = $pdo->prepare("SELECT id, pr FROM page ORDER BY id LIMIT ?, 1");
    $query->execute(array($count));
    $row = $query->fetch(PDO::FETCH_ASSOC);
    while ($row !== false) {
        //echo "***ID(A): " . $row['id'] . " PR(A): " . $row['pr'] . "<br>"; // ID(A) && PR(A)
        $current_id = $row['id'];
        $current_pr_static = $row['pr'];
        $current_pr = (1 - DAMPING_FACTOR);
        $current_sum = 0;
        /**
         * Второстепенный цикл
         * Бегаем по всем ссылкам указывающих на текущую страницу
         */
        $query = $pdo->prepare("SELECT page1 FROM link WHERE page2 = ?");
        $query->execute(array($current_id));
        while ($row = $query->fetch(PDO::FETCH_ASSOC)) {
            //echo "ID(C_i): " . $row['page1'] . " ";
            $current_id_in_while = $row['page1'];

            // Узнаем вес ссылающийся страницы PR(C_i)
            $query1 = $pdo->prepare("SELECT pr FROM page WHERE id = ?");
            $query1->execute(array($current_id_in_while));
            $row1 = $query1->fetch(PDO::FETCH_ASSOC);
            //echo "PR(C_i): " . $row1['pr'] . " ";

            // Узнаем кол-во ссылок с ссылающийся страницы C(C_i)
            $query2 = $pdo->prepare("SELECT COUNT(*) FROM link WHERE page1 = ?");
            $query2->execute(array($current_id_in_while));
            $row2 = $query2->fetch(PDO::FETCH_ASSOC);
            //echo "C(C_i): " . $row2['COUNT(*)'];
            //echo "<br>";

            $current_sum += $row1['pr'] / $row2['COUNT(*)'];
        }
        $current_sum *= DAMPING_FACTOR;
        $current_pr += $current_sum;
        $pr[] = $current_pr;
        //echo $current_pr . "<br>";

        // Пишем PR(A) в бд
        // $pdo->beginTransaction();
        // $query = $pdo->prepare("UPDATE page SET pr = ? WHERE id = ?");
        // $query->execute(array($current_pr, $current_id));
        // $pdo->commit();

        // echo "<br>";
        // echo "<br>";
        //var_dump(abs($current_pr_static - $current_pr));
        if ($iterations != 0) {
            if (abs($current_pr_static - $current_pr) > EPSILON) {
                $check = false;
            }
        } elseif ($iterations == 0) {
            $check = false;
        }

        ++$count;
        $query = $pdo->prepare("SELECT id, pr FROM page ORDER BY id LIMIT ?, 1");
        $query->execute(array($count));
        $row = $query->fetch(PDO::FETCH_ASSOC);
    }

    $query = $pdo->prepare("UPDATE page SET pr = ? WHERE id = ?");
    foreach ($pr as $key => $value) {
        $query->execute(array($value, $key + 2));
    }
    $pr = array();

    if ($check === true) {
        break;
    } else {
        $check = true;
    }
    echo $iterations;
    echo "<br>";
    ++$iterations;
} while (true);

$pdo = null; // Закрывем содинение с бд