<?php

require_once 'jacksnaps.php';

$js = Jacksnaps::instance();

// Data for new record
$image = $js->fetch_image();
$caption = $js->fetch_jacksnap();
//$caption = "I renounced balls + priest and retired soon thereafter...tell my Roomba to forget about OJ's cell and prep for DJ Tanner spying";
echo $image . "\n";
echo $caption . "\n";
$js->post_caption($caption, $image);

?>
