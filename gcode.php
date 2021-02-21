<?php
include("writer.php");
include("image.php");

function map($value, $fromLow, $fromHigh, $toLow, $toHigh) {
    $fromRange = $fromHigh - $fromLow;
    $toRange = $toHigh - $toLow;
    $scaleFactor = $toRange / $fromRange;

    // Re-zero the value within the from range
    $tmpValue = $value - $fromLow;
    // Rescale the value to the to range
    $tmpValue *= $scaleFactor;
    // Re-zero back to the to range
    return $tmpValue + $toLow;
}

set_time_limit(300);

//error_reporting(E_ALL);
//ini_set('display_errors', 1);

$now = new DateTime();
$name = $now->getTimestamp();
$ext = "jpg";
if (isset($_FILES['image']['name'])) {
    $saveto = "$name.$ext";
    switch($_FILES['image']['type']) {
        case "image/gif":
            $src = imagecreatefromgif($_FILES['image']['tmp_name']);
            break;
        case "image/jpeg": // Both regular and progressive jpegs
        case "image/pjpeg":
            $src = imagecreatefromjpeg($_FILES['image']['tmp_name']);
            break;
        case "image/png":
            $src = imagecreatefrompng($_FILES['image']['tmp_name']);
            break;
        default:
            exit("Unknown image type");
            break;
    }
    list($w, $h) = getimagesize($_FILES['image']['tmp_name']);
} else {
   exit("No image");
}

if(!isset($_POST['sizeY']) || $_POST['sizeY'] == 0) {
    exit("No image height defined");
}

$laserMax=$_POST['LaserMax'];
$laserMin=$_POST['LaserMin'];
$laserOff=$_POST['LaserOff'];
$whiteLevel=$_POST['whiteLevel'];
$overScan = $_POST['overScan'];
$offsetY=$_POST['offsetY'];
$sizeY=$_POST['sizeY'];
$scanGap=$_POST['scanGap'];
$offsetX=$_POST['offsetX'];
$sizeX=$sizeY*$w/$h;
$resX=$_POST['resX'];

//Create a resampled image with exactly the data needed, 1px in to 1px out
$pixelsX = round($sizeX/$resX);
$pixelsY = round($sizeY/$scanGap);

$tmp = imagecreatetruecolor($pixelsX, $pixelsY);
imagecopyresampled($tmp, $src, 0, 0, 0, 0, $pixelsX, $pixelsY, $w, $h);
Image::flip($tmp);
imagefilter($tmp,IMG_FILTER_GRAYSCALE);

if ($_POST['preview'] == 1) {
    header('Content-Type: image/jpeg'); //do this to display following image
    imagejpeg($tmp); //show image
    imagedestroy($tmp);
    imagedestroy($src);
    exit(); //exit if above
}

header("Content-Disposition: attachment; filename=".$_FILES['image']['name'].".gcode");


if ($_POST["code"] == "reprap") {
    $writer = new ReprapWriter();
} else {
    $writer = new GrblWriter();
}

$writer->comment("Created using Nebarnix's IMG2GCO program Ver 1.0");
$writer->comment("http://nebarnix.com 2015");
$writer->comment("");
$writer->comment("Size in pixels X=$pixelsX, Y=$pixelsY");

$cmdRate = round(($_POST['feedRate'] / $resX) * 2 / 60);
$writer->comment("Size in mm X=" . round($sizeX, 2) . ", Y=" . round($sizeY, 2));
$writer->comment("");
$writer->comment("Speed is " . $_POST['feedRate'] . " mm/min, $resX mm/pix => $cmdRate lines/sec");
$writer->comment("Power is $laserMin to $laserMax (". round($laserMin/255*100, 1) ."%-". round($laserMax/255*100, 1) ."%)");

// Start with the actual gcode generation

$writer->setFeedRate($_POST['feedRate']);
$writer->setTravelRate($_POST['travelRate']);

$writer->header();
$writer->laserOn();
$writer->laserPower($laserOff);
$writer->useFastMoves();
$writer->moveTo($offsetX, $offsetY);

$lineIndex = 0;
define("BACKWARDS", -1);
define("FORWARDS", 1);
$direction = 1; // Backwards: -1, forwards: 1
// Run through the image in gcode coordinates
for ($line = $offsetY; $line < ($sizeY + $offsetY) && $lineIndex < $pixelsY; $line += $scanGap) {
    if ($direction == FORWARDS) {
        $pixelIndex = 0;
    } else {
        $pixelIndex = $pixelsX - 1;
    }
    $firstX = 0; // reset the first find
    $lastX = 0; // reset the last find

    // Find first non-white pixel
    while ($pixelIndex >= 0 && $pixelIndex < $pixelsX) {
        $rgb = imagecolorat($tmp, $pixelIndex, $lineIndex);
        $value = ($rgb >> 16) & 0xFF; // create 8bit value from 24bit value - Image is already greyscale
        if ($value < $whiteLevel) { //Nonwhite image parts
            if ($direction == FORWARDS) {
                if ($firstX == 0) {
                    $firstX = $pixelIndex;
                }
                $lastX = $pixelIndex; // Save last known non-white pixel continuously
            } else if ($direction == BACKWARDS) {
                if ($lastX == 0) {
                    $lastX = $pixelIndex;
                }
                $firstX = $pixelIndex; // Save last known non-white pixel continuously
            }
        }
        $pixelIndex += $direction;
    }

    // Generate gcode

    if ($direction == FORWARDS) {
        $pixelIndex = $firstX;
        $pixel = $offsetX + $firstX * $resX;
    } else {
        $pixelIndex = $lastX;
        $pixel = $offsetX + $lastX * $resX;
    }

    while (($direction == BACKWARDS && $pixel >= $offsetX)
            || ($direction == FORWARDS && $pixel < ($sizeX + $offsetX))) {
        if ($direction == FORWARDS && $pixelIndex == $lastX) {
            $direction = BACKWARDS;
            break;
        } else if($direction == BACKWARDS && $pixelIndex == $firstX) {
            $direction = FORWARDS;
            break;
        }

        $rgb = imagecolorat($tmp, $pixelIndex, $lineIndex);
        $value = ($rgb >> 16) & 0xFF;
        $laserPower = round(map($value, 255, 0, $laserMin, $laserMax), 0);

        if (($direction == FORWARDS && $pixelIndex == $firstX)
                || ($direction == BACKWARDS && $pixelIndex == $lastX)) {
            $writer->useLinearMoves();
            $writer->moveTo($pixel - $direction * $overScan, $line);
            $writer->moveTo($pixel, $line);
        } else {
            if ($laserPower <= $laserOff) {
                // Quickly skip over sections without laser power
                $writer->useFastMoves();
            } else {
                $writer->useLinearMoves();
            }
            $writer->moveToX($pixel);
            $writer->laserPower($laserPower);
        }

        $pixelIndex += $direction;
        $pixel += $direction * $resX;
    }

    if ($firstX > 0 && $lastX > 0) {
        $writer->laserPower($laserOff);
    }
    $lineIndex++;
}

imagedestroy($tmp);

$writer->laserPower($laserOff);
$writer->laserOff();
$writer->useFastMoves();
$writer->moveTo(0, 0);

echo $writer->getGeneratedCode();
