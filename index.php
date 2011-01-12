<?php

require_once 'jacksnaps.php';

$js = Jacksnaps::instance();

// Data for new record
$image = $js->fetch_image();
$caption = $js->fetch_jacksnap();
echo $image;
echo $caption;
$js->post_caption($caption, $image);
?>
