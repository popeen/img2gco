<?php

function imagecreatefromfile($filename) {
    if (!file_exists($filename)) {
        throw new InvalidArgumentException('File "' . $filename . '" not found.');
    }
    switch (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
        case 'jpeg':
        case 'jpg':
            return imagecreatefromjpeg($filename);
        case 'png':
            return imagecreatefrompng($filename);
        case 'gif':
            return imagecreatefromgif($filename);
        default:
            throw new InvalidArgumentException('File "' . $filename . '" is not valid jpg, png or gif image.');
            break;
    }
}
