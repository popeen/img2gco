<?php

abstract class Writer {
    private $fileHandle;

    protected string $travelRate = "";
    protected string $feedRate = "";

    public function __construct($filename) {
        $this->fileHandle = fopen($filename, "w");
    }

    public function close() {
        fclose($this->fileHandle);
    }

    protected function print(string $line) {
        fwrite($this->fileHandle, $line);
    }

    protected function println(string $line) {
        fwrite($this->fileHandle, $line . "\n");
    }

    public function comment(string $comment) {
        fwrite($this->fileHandle, ";" . $comment . "\n");
    }

    public function setTravelRate(string $rate) {
        $this->travelRate = $rate;
    }

    public function setFeedRate(string $rate) {
        $this->feedRate = $rate;
    }

    public abstract function header();

    public abstract function laserOn();
    public abstract function laserOff();
    public abstract function laserPower(int $power);

    public abstract function useFastMoves();
    public abstract function useLinearMoves();

    public abstract function moveTo(float $x, float $y);
    public abstract function moveToX(float $x);
}

class MultiWriter extends Writer {
    private $writers;

    public function __construct(...$children) {
        $this->writers = $children;
    }

    public function close() {
        foreach ($this->writers as $writer) {
            $writer->close();
        }
    }

    protected function print(string $line) {
        foreach ($this->writers as $writer) {
            $writer->print($line);
        }
    }

    protected function println(string $line) {
        foreach ($this->writers as $writer) {
            $writer->println($line);
        }
    }

    public function comment(string $comment) {
        foreach ($this->writers as $writer) {
            $writer->comment($comment);
        }
    }

    public function setTravelRate(string $rate) {
        foreach ($this->writers as $writer) {
            $writer->setTravelRate($rate);
        }
    }

    public function setFeedRate(string $rate) {
        foreach ($this->writers as $writer) {
            $writer->setFeedRate($rate);
        }
    }

    public function header() {
        foreach ($this->writers as $writer) {
            $writer->header($writer);
        }
    }

    public function laserOn() {
        foreach ($this->writers as $writer) {
            $writer->laserOn();
        }
    }

    public function laserOff() {
        foreach ($this->writers as $writer) {
            $writer->laserOff();
        }
    }

    public function laserPower(int $power) {
        foreach ($this->writers as $writer) {
            $writer->laserPower($power);
        }
    }

    public function useFastMoves() {
        foreach ($this->writers as $writer) {
            $writer->useFastMoves($writer);
        }
    }

    public function useLinearMoves() {
        foreach ($this->writers as $writer) {
            $writer->useLinearMoves($writer);
        }
    }

    public function moveTo(float $x, float $y) {
        foreach ($this->writers as $writer) {
            $writer->moveTo($x, $y);
        }
    }

    public function moveToX(float $x) {
        foreach ($this->writers as $writer) {
            $writer->moveToX($x);
        }
    }
}

class GrblWriter extends Writer {
    const MOVE_FAST = 1;
    const MOVE_LINEAR = 2;
    const MOVE_UNKNOWN = 3;
    private int $moveSpeed = self::MOVE_UNKNOWN;
    private int $power = 0;

    public function header() {
        $this->comment("GRBL flavour");
        $this->println("G21"); // Use metric units
        $this->println("G00 Z0"); // Home Z
    }

    public function laserOn() {
        $this->println("M3");
    }

    public function laserOff() {
        $this->println("M5");
    }

    public function laserPower(int $power) {
        $this->power = $power;
    }

    public function useFastMoves() {
        if ($this->moveSpeed == self::MOVE_FAST) {
            return; // No need to switch again
        }
        $this->println("G0 F" . $this->travelRate);
        $this->moveSpeed = self::MOVE_FAST;
    }

    public function useLinearMoves() {
        if ($this->moveSpeed == self::MOVE_LINEAR) {
            return; // No need to switch again
        }
        $this->println("G1 F" . $this->feedRate);
        $this->moveSpeed = self::MOVE_LINEAR;
    }

    public function moveTo(float $x, float $y) {
        $this->println("X" . round($x, 4) . " Y" . round($y, 4) . " S" . $this->power);
    }

    public function moveToX(float $x) {
        $this->println("X" . round($x, 4) . " S" . $this->power);
    }
}

class ReprapWriter extends GrblWriter {
    public function header() {
        $this->comment("Reprap flavour");
        $this->println("G21"); // Use metric units
        $this->println("G00 Z0"); // Home Z
    }

    public function laserOn() {
        $this->println("M106");
    }

    public function laserOff() {
        $this->println("M107");
    }
}

class SvgWriter extends Writer {
    private int $xMin = 1000;
    private int $yMin = 1000;
    private int $xMax = -1000;
    private int $yMax = -1000;
    private string $outputFilename;
    private string $tempFilename;

    public function __construct($filename) {
        // Writing to a file is faster than appending to a string
        // but we need to modify the beginning of the file for this svg preview.
        // So, write to a temp file first.
        $this->outputFilename = $filename;
        $this->tempFilename = tempnam(sys_get_temp_dir(), 'gcode-svg');
        parent::__construct($this->tempFilename);
    }

    public function comment(string $comment) {
        // Ignore
    }

    public function header() {
        $this->moveTo(0, 0);
    }

    public function laserOn() {
    }

    public function laserOff() {
    }

    public function laserPower(int $power) {
    }

    public function useFastMoves() {
    }

    public function useLinearMoves() {
    }

    public function moveTo(float $x, float $y) {
        $y *= -1;
        $this->print("M$x $y ");

        $this->xMin = min($x, $this->xMin);
        $this->xMax = max($x, $this->xMax);
        $this->yMin = min($y, $this->yMin);
        $this->yMax = max($y, $this->yMax);
    }

    public function moveToX(float $x) {
        $this->print("H$x ");
        $this->xMin = min($x, $this->xMin);
        $this->xMax = max($x, $this->xMax);
    }

    public function close() {
        parent::close();
        $pathData = file_get_contents($this->tempFilename);
        unlink($this->tempFilename);

        $originRadius = max(($this->xMax - $this->xMin), ($this->yMax - $this->yMin)) * 0.05;
        $this->xMin = min(-$originRadius, $this->xMin);
        $this->xMax = max($originRadius, $this->xMax);
        $this->yMin = min(-$originRadius, $this->yMin);
        $this->yMax = max($originRadius, $this->yMax);

        $width = ($this->xMax - $this->xMin) + 2;
        $height = ($this->yMax - $this->yMin) + 2;

        $fullSvg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'
                . '<svg width="' . $width . '" height="' . $height . '" '
                . 'viewBox="' . ($this->xMin - 1) . ' ' . ($this->yMin - 1) . ' ' . $width . ' ' . $height . '" '
                . 'version="1.1" id="svg8" xmlns="http://www.w3.org/2000/svg">'
                . '<path fill="transparent" stroke="black" d="' . $pathData . '"/>'
                . '<path fill="transparent" stroke="#cc3300" d="M-' . $originRadius . ' 0 H' . $originRadius . '" stroke-width="' . ($originRadius / 4) . '" />'
                . '<path fill="transparent" stroke="#cc3300" d="M0 -' . $originRadius . ' V' . $originRadius . '" stroke-width="' . ($originRadius / 4) . '" />'
                . "</svg>";
        file_put_contents($this->outputFilename, $fullSvg);
    }
}


class DurationEstimateWriter extends Writer {
    private float $durationMinutes = 0;
    private float $currentFeed = 1;
    private float $lastX = 0;
    private float $lastY = 0;

    public function comment(string $comment) {
        // Ignore
    }

    public function header() {
    }

    public function laserOn() {
    }

    public function laserOff() {
    }

    public function laserPower(int $power) {
    }

    public function useFastMoves() {
        $this->currentFeed = $this->travelRate;
    }

    public function useLinearMoves() {
        $this->currentFeed = $this->feedRate;
    }

    public function moveTo(float $x, float $y) {
        $dX = abs($x - $this->lastX);
        $dY = abs($y - $this->lastY);
        $distance = sqrt($dX * $dX + $dY * $dY);
        echo "$distance\n";
        $this->durationMinutes += $distance / $this->currentFeed;
        $this->lastX = $x;
        $this->lastY = $y;
    }

    public function moveToX(float $x) {
        $this->moveTo($x, $this->lastY);
    }

    public function close() {
        $this->print(round(60 * $this->durationMinutes));
        parent::close();
    }
}
