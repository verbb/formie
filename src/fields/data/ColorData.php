<?php
namespace verbb\formie\fields\data;

use verbb\formie\base\FieldValueInterface;

use craft\base\Serializable;

use yii\base\BaseObject;

class ColorData extends BaseObject implements FieldValueInterface, Serializable
{
    // Properties
    // =========================================================================

    private string $_hex;
    private array $_hsl;


    // Public Methods
    // =========================================================================

    public function __construct(string $hex, array $config = [])
    {
        $this->_hex = $hex;

        parent::__construct($config);
    }

    public function __toString(): string
    {
        return $this->_hex;
    }

    public function serialize(): mixed
    {
        return $this->_hex;
    }

    public function getHex(): string
    {
        return $this->_hex;
    }

    public function getRgb(): string
    {
        return "rgb({$this->getRed()},{$this->getGreen()},{$this->getBlue()})";
    }

    public function getHsl(): string
    {
        [$h, $s, $l] = $this->_hsl();
        return "hsl($h,$s%,$l%)";
    }

    public function getRed(): int
    {
        return hexdec(substr($this->_hex, 1, 2));
    }

    public function getR(): int
    {
        return $this->getRed();
    }

    public function getGreen(): int
    {
        return hexdec(substr($this->_hex, 3, 2));
    }

    public function getG(): int
    {
        return $this->getGreen();
    }

    public function getBlue(): int
    {
        return hexdec(substr($this->_hex, 5, 2));
    }

    public function getB(): int
    {
        return $this->getBlue();
    }

    public function getHue(): int
    {
        return $this->_hsl()[0];
    }

    public function getH(): int
    {
        return $this->getHue();
    }

    public function getSaturation(): int
    {
        return $this->_hsl()[1];
    }

    public function getS(): int
    {
        return $this->getSaturation();
    }

    public function getLightness(): int
    {
        return $this->_hsl()[2];
    }

    public function getL(): int
    {
        return $this->getLightness();
    }

    public function getLuma(): float
    {
        return (0.2126 * $this->getRed() + 0.7152 * $this->getGreen() + 0.0722 * $this->getBlue()) / 255;
    }


    // Private Methods
    // =========================================================================

    private function _hsl(): array
    {
        if (!isset($this->_hsl)) {
            // h/t https://gist.github.com/brandonheyer/5254516
            $rPct = $this->getRed() / 255;
            $gPct = $this->getGreen() / 255;
            $bPct = $this->getBlue() / 255;

            $maxRgb = max($rPct, $gPct, $bPct);
            $minRgb = min($rPct, $gPct, $bPct);

            $l = ($maxRgb + $minRgb) / 2;
            $d = $maxRgb - $minRgb;

            if ($d == 0) {
                $h = $s = 0; // achromatic
            } else {
                $s = $d / (1 - abs(2 * $l - 1));

                switch ($maxRgb) {
                    case $rPct:
                        $h = 60 * fmod((($gPct - $bPct) / $d), 6);
                        if ($bPct > $gPct) {
                            $h += 360;
                        }
                        break;

                    case $gPct:
                        $h = 60 * (($bPct - $rPct) / $d + 2);
                        break;

                    default:
                        $h = 60 * (($rPct - $gPct) / $d + 4);
                        break;
                }
            }

            $this->_hsl = [round($h), round($s * 100), round($l * 100)];
        }

        return $this->_hsl;
    }
}
