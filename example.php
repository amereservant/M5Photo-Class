<?php
require './m5photo.class.php';

// Source Filename
$name = 'IMG_0552';
$ext  = '.jpg';

// Istantiate M5photo class
$photo = new M5photo();

// Set B&W Filter
$photo->setBWFilter(true, -5);

// Create resized/B&W image
$photo->resizeImage('./source/'. $name . $ext, './output/'. $name . $ext, 640, 640);

// Unset B&W Filter
$photo->setBWFilter(false);

// Create a resized color image
$photo->resizeImage('./source/'. $name . $ext, './output/'. $name .'_color'. $ext, 640, 640);
highlight_file('./m5photo.class.php');
