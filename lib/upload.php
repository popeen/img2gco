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
            $ext = "gif";
            break;
        case "image/jpeg": // Both regular and progressive jpegs
        case "image/pjpeg":
            $ext = "jpg";
            break;
        case "image/png":
            $ext = "png";
            break;
        default:
            exit("Unknown image type");
            break;
    }
    $name = (new DateTime())->getTimestamp() . "-" . md5($_FILES['image']['name']);
    $filename = "uploads/$name";
    @mkdir("uploads");
    $success = move_uploaded_file($_FILES['image']['tmp_name'], "$filename.$ext");
    if ($success) {
        $_SESSION["filename"] = $filename;
        $_SESSION["ext"] = $ext;
    }
} else if (@$_GET["do"] == "clearImage") {
    @unlink($_SESSION["filename"] . "." . $_SESSION["ext"]);
    @unlink($_SESSION["filename"] . ".gcode");
    @unlink($_SESSION["filename"] . ".svg");
    unset($_SESSION["filename"]);
    unset($_SESSION["ext"]);
} else if (@$_GET["do"] == "invalidate") {
    @unlink($_SESSION["filename"] . ".gcode");
    @unlink($_SESSION["filename"] . ".svg");
} else if (@$_GET["do"] == "rotate") {
    $img = imagecreatefromfile($_SESSION["filename"] . "." . $_SESSION["ext"]);
    $img = imagerotate($img, -90, imagecolorallocatealpha($img, 255, 255, 255, 0));
    $_SESSION["ext"] = "png";
    imagepng($img, $_SESSION["filename"] . "." . $_SESSION["ext"]);
    header("Location: index.php"); // Don't rotate again when pressing F5
    exit();
}
