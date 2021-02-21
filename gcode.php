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
$name = $now->getTimestamp();           // Unix Timestamp -- Since PHP 5.3;
$ext = "jpg";
if (isset($_FILES['image']['name']))
{
    $saveto = "$name.$ext";
    //print ";$saveto\n";
    //move_uploaded_file($_FILES['image']['tmp_name'], $saveto);
    $typeok = TRUE;
    switch($_FILES['image']['type'])
    {
        //case "image/gif": $src = imagecreatefromgif($saveto); break;
        //case "image/jpeg": // Both regular and progressive jpegs
        //case "image/pjpeg": $src = imagecreatefromjpeg($saveto); break;
        //case "image/png": $src = imagecreatefrompng($saveto); break;

        case "image/gif": $src = imagecreatefromgif($_FILES['image']['tmp_name']); break;
        case "image/jpeg": // Both regular and progressive jpegs
        case "image/pjpeg": $src = imagecreatefromjpeg($_FILES['image']['tmp_name']); break;
        case "image/png": $src = imagecreatefrompng($_FILES['image']['tmp_name']); break;
        default: $typeok = FALSE; break;
    }
    if ($typeok)
    {
       //print ";image OK\n";
       //list($w, $h) = getimagesize($saveto);
       list($w, $h) = getimagesize($_FILES['image']['tmp_name']);

    }
    else
      exit("Unknown image type");
}
else
   exit("No image");

if(!isset($_POST['sizeY']) || $_POST['sizeY'] == 0)
   {
   exit("No image height defined");
   }

//header('Content-Type: text/plain; charset=utf-8');

$goBackwards = 0; // stores the direction the head is traveling though the image
$laserMax=$_POST['LaserMax'];//$laserMax=65; //out of 255
$laserMin=$_POST['LaserMin']; //$laserMin=20; //out of 255
$laserOff=$_POST['LaserOff'];//$laserOff=13; //out of 255
$whiteLevel=$_POST['whiteLevel'];

$feedRate = $_POST['feedRate'];//$feedRate = 800; //in mm/sec
$travelRate = $_POST['travelRate'];//$travelRate = 3000;

$overScan = $_POST['overScan'];//$overScan = 3;

$offsetY=$_POST['offsetY'];//$offsetY=10;
$sizeY=$_POST['sizeY'];//$sizeY=40;
$scanGap=$_POST['scanGap'];//$scanGap=.1;

$offsetX=$_POST['offsetX'];//$offsetX=5;
$sizeX=$sizeY*$w/$h; //SET A HEIGHT AND CALC WIDTH (this should be customizable)
$resX=$_POST['resX'];;//$resX=.1;

//Create a resampled image with exactly the data needed, 1px in to 1px out
$pixelsX = round($sizeX/$resX);
$pixelsY = round($sizeY/$scanGap);

$tmp = imagecreatetruecolor($pixelsX, $pixelsY);
imagecopyresampled($tmp, $src, 0, 0, 0, 0, $pixelsX, $pixelsY, $w, $h);
Image::flip($tmp);
imagefilter($tmp,IMG_FILTER_GRAYSCALE);

if($_POST['preview'] == 1)
   {
   header('Content-Type: image/jpeg'); //do this to display following image
   imagejpeg($tmp); //show image
   imagedestroy($tmp);
   imagedestroy($src);
   exit(); //exit if above
   }

header("Content-Disposition: attachment; filename=".$_FILES['image']['name'].".gcode");

$cmdRate = round(($feedRate / $resX) * 2 / 60);

if ($_POST["code"] == "reprap") {
    $writer = new ReprapWriter();
} else {
    $writer = new GrblWriter();
}

$writer->comment("Created using Nebarnix's IMG2GCO program Ver 1.0");
$writer->comment("http://nebarnix.com 2015");
$writer->comment("");
$writer->comment("Size in pixels X=$pixelsX, Y=$pixelsY");
$writer->comment("Size in mm X=" . round($sizeX, 2) . ", Y=" . round($sizeY, 2));
$writer->comment("");
$writer->comment("Speed is $feedRate mm/min, $resX mm/pix => $cmdRate lines/sec");
$writer->comment("Power is $laserMin to $laserMax (". round($laserMin/255*100, 1) ."%-". round($laserMax/255*100, 1) ."%)");

// Start with the actual gcode generation

$writer->header();

$writer->laserOn();
$writer->laserPower($laserOff);
$writer->setFeedRate($feedRate);
$writer->setTravelRate($travelRate);
$writer->useFastMoves();
$writer->moveTo($offsetX, $offsetY);

$lineIndex = 0;
// Run through the image in gcode coordinates
for($line = $offsetY; $line < ($sizeY + $offsetY) && $lineIndex < $pixelsY; $line += $scanGap)
{
   if ($goBackwards == 0)
   {
	   //analyze the row and find first and last nonwhite pixels
	   $pixelIndex = 0; // start at 0
	   $firstX = 0; // reset the first find
	   $lastX = 0; // reset the last find

       // Run through all pixels in forward direction and check brightness and store first and last location per line by on the fly updating
	   for($pixelIndex = 0; $pixelIndex < $pixelsX; $pixelIndex++)
	   {
	      // check the temp image at pixel and line index
		  $rgb = imagecolorat($tmp, $pixelIndex, $lineIndex);
		  $value = ($rgb >> 16) & 0xFF; // create 8bit value from 24bit value - Image is already greyscale

          // Check if the current pixel is brighter than the theshold
		  if($value < $whiteLevel) //Nonwhite image parts
		  {
		     // if yes, and the first pixel was not set yet, store it now
			 if($firstX == 0)
				$firstX = $pixelIndex;
             // Continuesly update the last pixel until the line has ended.
			 $lastX = $pixelIndex; // Save last known non-white pixel
		  }
	   }

	   $pixelIndex = $firstX; // Reset pixel index to the first found pixel while traveling forwards
   }
   else if ($goBackwards == 1)
   {
	   //analyze the row and find first and last nonwhite pixels
	   $pixelIndex = $pixelsX; // Reset the pixel index to the right most last pixel index
	   $firstX = 0; // Reset the first found pixel
	   $lastX = 0; // Reset the last found pixel
	   // Remark: This should be reversed when traveling backwards!

	   for($pixelIndex = $pixelsX - 1; $pixelIndex > 0; $pixelIndex--)
	   {
		  $rgb = imagecolorat($tmp, $pixelIndex, $lineIndex);
		  $value = ($rgb >> 16) & 0xFF; // create 8bit value from 24bit value - Image is already greyscale

		  if($value < $whiteLevel) //Nonwhite image parts
		  {
			 if($lastX == 0)
				$lastX = $pixelIndex;

			 $firstX = $pixelIndex; // Save last known non-white pixel
		  }
	   }

	   $pixelIndex = $lastX; // Reset pixel index back to the first found pixel while traveling backwards
   }

   if ($goBackwards==0)
   {
	   for($pixel = $offsetX + $firstX * $resX; $pixel < ($sizeX + $offsetX); $pixel += $resX)
	   {
		  if($pixelIndex == $lastX)
		  {
			 $goBackwards = 1;
			 break;
		  }

		  if($pixelIndex == $firstX)
		  {
                         $writer->useLinearMoves();
                         $writer->moveTo(round($pixel - $overScan, 4), round($line, 4));
                         $writer->moveTo(round($pixel, 4), round($line, 4));
		  }
		  else
                         $writer->moveToX(round ($pixel, 4));

		  $rgb = imagecolorat($tmp, $pixelIndex, $lineIndex);
		  $value = ($rgb >> 16) & 0xFF;
		  $value = round(map($value, 255, 0, $laserMin, $laserMax), 0);
		  if($pixelIndex != $firstX)
                      $writer->laserPower($value);
		  $pixelIndex++;
	   }
   }
   else
   {
	   for($pixel = $offsetX + $lastX * $resX; $pixel >= $offsetX; ($pixel -= $resX))
	   {
		  if($pixelIndex == $firstX)
		  {
			 $goBackwards = 0;
			 break;
		  }

		  if($pixelIndex == $lastX)
		  {
                      $writer->useLinearMoves();
                      $writer->moveTo(round($pixel + $overScan, 4), round($line, 4));
                      $writer->moveTo(round($pixel, 4), round($line, 4));
		  }
		  else
                      $writer->moveToX(round($pixel, 4));

		  $rgb = imagecolorat($tmp, $pixelIndex, $lineIndex);
		  $value = ($rgb >> 16) & 0xFF;
		  $value = round(map($value, 255, 0, $laserMin, $laserMax), 0);
		  if($pixelIndex != $lastX)
                        $writer->laserPower($value);
		  $pixelIndex--;
	   }
   }
   if ($firstX > 0 && $lastX > 0)
       $writer->laserPower($laserOff);
   $lineIndex++;
}
$lineIndex--;

imagedestroy($tmp);

$writer->laserPower($laserOff);
$writer->laserOff();
$writer->useFastMoves();
$writer->moveTo(0, 0);

echo $writer->getGeneratedCode();
