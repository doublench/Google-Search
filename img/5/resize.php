<?php
$pieces_count = 1023; //Число файлов-тайлов
$pieces_count_on_row = 32; //Число файлов-тайлов, уложившихся в длину изображения, округленное в большую сторону.
for($i=0; $i <= $pieces_count; $i++){
  $filename = (int)($i / $pieces_count_on_row);
  $filename .= 'x' . $i % $pieces_count_on_row;
  rename("{$i}.png","{$filename}.png");
}