<?php

function cleanupOldUploads() {
    $handle = opendir('uploads');
    while (false !== ($entry = readdir($handle))) {
        if ($entry == "." || $entry == "..") {
            continue;
        }
        $parts = explode("-", $entry);
        $date = $parts[0];
        if ($date < (new DateTime())->getTimestamp() - 5 * 3600) {
            // Remove items older than 5 hours
            unlink("uploads/$entry");
        }
    }
    closedir($handle);
}

if (isset($_FILES['image']['name'])) {
    cleanupOldUploads();

    switch ($_FILES['image']['type']) {
        case "image/gif":
            $img = imagecreatefromgif($_FILES['image']['tmp_name']);
            break;
        case "image/jpeg": // Both regular and progressive jpegs
        case "image/pjpeg":
            $img = imagecreatefromjpeg($_FILES['image']['tmp_name']);
            break;
        case "image/png":
            $img = imagecreatefrompng($_FILES['image']['tmp_name']);
            break;
        default:
            exit("Unknown image type");
            break;
    }
    $name = (new DateTime())->getTimestamp() . "-" . md5($_FILES['image']['name']);
    $filename = "uploads/$name";
    @mkdir("uploads");

    list($w, $h) = getimagesize($_FILES['image']['tmp_name']);
    $tmp = imagecreatetruecolor($w, $h);
    imagefilledrectangle($tmp, 0, 0, $w, $h, imagecolorallocate($tmp, 255, 255, 255));
    imagecopy($tmp, $img, 0, 0, 0, 0, $w, $h);
    imagefilter($tmp, IMG_FILTER_GRAYSCALE);
    imagepng($tmp, "$filename.png");

    $_SESSION["filename"] = $filename;
} else if (@$_GET["do"] == "clearImage") {
    @unlink($_SESSION["filename"] . "." . $_SESSION["ext"]);
    @unlink($_SESSION["filename"] . ".ngc");
    @unlink($_SESSION["filename"] . ".svg");
    @unlink($_SESSION["filename"] . ".duration");
    unset($_SESSION["filename"]);
} else if (@$_GET["do"] == "invalidate") {
    @unlink($_SESSION["filename"] . ".ngc");
    @unlink($_SESSION["filename"] . ".svg");
    @unlink($_SESSION["filename"] . ".duration");
} else if (@$_GET["do"] == "rotate") {
    $img = imagecreatefrompng($_SESSION["filename"] . ".png");
    $img = imagerotate($img, -90, imagecolorallocate($img, 255, 255, 255));
    imagepng($img, $_SESSION["filename"] . ".png");
    @unlink($_SESSION["filename"] . ".ngc");
    @unlink($_SESSION["filename"] . ".svg");
    @unlink($_SESSION["filename"] . ".duration");
    header("Location: index.php"); // Don't rotate again when pressing F5
    exit();
}
