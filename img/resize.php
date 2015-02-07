<?php
$pieces_count = ; // Число файлов-тайлов. Отсчет с нуля
$pieces_count_on_row = ; // Число файлов-тайлов, уложившихся в длину изображения. Отсчет с нуля
for ($i = 0; $i <= $pieces_count; $i++) {
    $filename = (int)($i / $pieces_count_on_row);
    $filename.= 'x' . $i % $pieces_count_on_row;
    rename("{$i}.png", "{$filename}.png");
}
