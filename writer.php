<?php

abstract class Writer {
    private string $generated = "";
    protected string $travelRate = "";
    protected string $feedRate = "";

    public function getGeneratedCode() {
        return $this->generated;
    }

    protected function println(string $line) {
        $this->generated .= $line . "\n";
    }

    public function comment(string $comment) {
        $this->generated .= ";" . $comment . "\n";
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
