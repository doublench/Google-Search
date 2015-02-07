<?php
set_time_limit(0);

$pdo = new PDO('mysql:host=localhost;dbname=amazon', 'root', '');
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
$pdo->query('SET NAMES utf8');

$query = $pdo->prepare("UPDATE page SET pr = 1 WHERE id = ?");
for ($i=1; $i <= 337; $i++ ) { 
     $query->execute(array($i));
}

$pdo = null;