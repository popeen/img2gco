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

class GrblWriter extends Writer {
    const MOVE_FAST = 1;
    const MOVE_LINEAR = 2;
    const MOVE_UNKNOWN = 3;
    private int $moveSpeed = self::MOVE_UNKNOWN;

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
        $this->println("S$power");
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
        $this->println("X" . round($x, 4) . " Y" . round($y, 4));
    }

    public function moveToX(float $x) {
        $this->println("X" . round($x, 4));
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
        $x *= -1;
        $this->print("M$x $y ");

        $this->xMin = min($x, $this->xMin);
        $this->xMax = max($x, $this->xMax);
        $this->yMin = min($y, $this->yMin);
        $this->yMax = max($y, $this->yMax);
    }

    public function moveToX(float $x) {
        $x *= -1;
        $this->print("H$x ");
        $this->xMin = min($x, $this->xMin);
        $this->xMax = max($x, $this->xMax);
    }

    public function close() {
        parent::close();
        $pathData = file_get_contents($this->tempFilename);
        unlink($this->tempFilename);

        $width = ($this->xMax - $this->xMin) + 2;
        $height = ($this->yMax - $this->yMin) + 2;
        $fullSvg = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>'
                . '<svg width="' . $width . '" height="' . $height . '" '
                . 'viewBox="' . ($this->xMin - 1) . ' ' . ($this->yMin - 1) . ' ' . $width . ' ' . $height . '" '
                . 'version="1.1" id="svg8" xmlns="http://www.w3.org/2000/svg">'
                . '<path fill="transparent" stroke="black" d="' . $pathData . '"/>'
                . '<circle cx="0" cy="0" r="0.5" fill="red"/>'
                . "</svg>";
        file_put_contents($this->outputFilename, $fullSvg);
    }

}
