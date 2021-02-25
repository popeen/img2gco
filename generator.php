<?php
session_start();
include("lib/writer.php");

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

$sourceImagePath = $_SESSION["filename"] . "." . $_SESSION["ext"];
list($w, $h) = getimagesize($sourceImagePath);

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

switch ($_SESSION["ext"]) {
    case "gif":
        $src = imagecreatefromgif($sourceImagePath);
        break;
    case "jpg":
        $src = imagecreatefromjpeg($sourceImagePath);
        break;
    case "png":
        $src = imagecreatefrompng($sourceImagePath);
        break;
    default:
        exit("Unknown image type");
        break;
}

$tmp = imagecreatetruecolor($pixelsX, $pixelsY);
$white = imagecolorallocate($tmp, 255, 255, 255);
imagefilledrectangle($tmp, 0, 0, $w, $h, $white); // Interpret transparency as white
imagecopyresampled($tmp, $src, 0, 0, 0, 0, $pixelsX, $pixelsY, $w, $h);
imagefilter($tmp,IMG_FILTER_GRAYSCALE);

$filename = $_SESSION["filename"] . ".gcode";
if ($_POST["code"] == "svg") {
    $filename = $_SESSION["filename"] . ".svg";
}

if ($_POST["code"] == "reprap") {
    $writer = new ReprapWriter($filename);
} else if ($_POST["code"] == "grbl") {
    $writer = new GrblWriter($filename);
} else if ($_POST["code"] == "svg") {
    $writer = new SvgWriter($filename);
} else {
    exit("Unknown code flavour");
}

$writer->comment("Created using Nebarnix's IMG2GCO program Ver 1.0");
$writer->comment("http://nebarnix.com 2015");
$writer->comment("");
$writer->comment("Size in pixels X=$pixelsX, Y=$pixelsY");

$cmdRate = round(($_POST['feedRate'] / $resX) * 2 / 60);
$writer->comment("Size in mm X=" . round($sizeX, 2) . ", Y=" . round($sizeY, 2));
$writer->comment("Speed is " . $_POST['feedRate'] . " mm/min, $resX mm/pix => $cmdRate lines/sec");
$writer->comment("Power is $laserMin to $laserMax (". round($laserMin/255*100, 1) ."%-". round($laserMax/255*100, 1) ."%)");
$writer->comment("");

// Start with the actual gcode generation

$writer->setFeedRate($_POST['feedRate']);
$writer->setTravelRate($_POST['travelRate']);

$writer->header();
$writer->laserOn();
$writer->laserPower($laserOff);
$writer->useFastMoves();
$writer->moveTo($offsetX, $offsetY);

$lastProgressUpdate = 0;
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

    while (($direction == BACKWARDS && $pixel >= $offsetX && $pixelIndex > $firstX)
            || ($direction == FORWARDS && $pixel < ($sizeX + $offsetX) && $pixelIndex < $lastX)) {
        $rgb = imagecolorat($tmp, $pixelIndex, $lineIndex);
        $value = ($rgb >> 16) & 0xFF;
        $laserPower = round(map($value, 255, 0, $laserMin, $laserMax), 0);

        if (($direction == FORWARDS && $pixelIndex == $firstX)
                || ($direction == BACKWARDS && $pixelIndex == $lastX)) {
            $writer->useLinearMoves();
            $writer->laserPower($laserOff);
            $writer->moveTo($pixel - $direction * $overScan, -$line);
            $writer->moveTo($pixel, -$line);
        } else {
            // Skip similar pixels to reduce file size
            while (($direction == BACKWARDS && $pixel + $direction * $resX >= $offsetX && $pixelIndex > $firstX + 1)
                || ($direction == FORWARDS && $pixel + $direction * $resX < ($sizeX + $offsetX)  && $pixelIndex < $lastX - 1)) {
                $rgb = imagecolorat($tmp, $pixelIndex, $lineIndex);
                $value = ($rgb >> 16) & 0xFF;
                $nextPixelLaserPower = round(map($value, 255, 0, $laserMin, $laserMax), 0);
                if (abs($nextPixelLaserPower - $laserPower) >= 3) {
                    break; // Pixels are too different, should generate a move command now.
                }
                $pixelIndex += $direction;
                $pixel += $direction * $resX;
            }

            $writer->laserPower($laserPower);
            $writer->moveToX($pixel);
        }

        $pixelIndex += $direction;
        $pixel += $direction * $resX;
    }

    if ($firstX > 0 && $lastX > 0) {
        $writer->laserPower($laserOff);
    }
    $lineIndex++;
    $direction = -$direction;

    // Show progress
    if ($lastProgressUpdate + 0.2 < microtime(true)) {
        echo round($lineIndex / $pixelsY * 100, 0) . "%\n";
        ob_flush();
        flush();
        $lastProgressUpdate = microtime(true);
    }
}

imagedestroy($tmp);

$writer->laserPower($laserOff);
$writer->laserOff();
$writer->useFastMoves();
$writer->moveTo(0, 0);

$writer->close();
