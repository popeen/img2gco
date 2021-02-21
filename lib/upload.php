<?php

if (isset($_FILES['image']['name'])) {
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
}
