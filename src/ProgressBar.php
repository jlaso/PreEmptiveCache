<?php

namespace JLaso\ToolsLib;

class ProgressBar
{
    /** @var  int */
    protected $value;
    /** @var  int */
    protected $oldValue;
    /** @var  int */
    protected $maxValue;
    /** @var  int */
    protected $size;
    /** @var  int */
    protected $percEmptySize;
    /** @var  int */
    protected $slice;

    const MASC_PERCENT = "%3d%% ";

    /**
     * ProgressBar constructor.
     */
    public function __construct($maxValue, $size = 50)
    {
        $this->maxValue = $maxValue;
        $this->value = 0;
        $this->oldValue = 0;
        $this->size = $size;
        $percEmpty = sprintf(self::MASC_PERCENT, 0);
        $this->percEmptySize = strlen($percEmpty);
        $this->slice = intval($maxValue/$size);

        print ($percEmpty . str_repeat('·', $this->size) . str_repeat(chr(8), $this->percEmptySize + $this->size));
    }

    /**
     * @param $value
     */
    public function updateValue($value)
    {
        $this->value = intval(ceil(100 * $value / $this->maxValue));
        if ($this->value != $this->oldValue) {
            $this->draw();
            $this->oldValue = $this->value;
        }
    }

    protected function draw()
    {
        $mask = array('*','o','-',"\\",'|','/');
        $this->slice;
        $pperc = intval($this->value * $this->size / 100);
        print (
            sprintf(self::MASC_PERCENT, $this->value) .
            str_repeat("*", $pperc) . $mask[($this->value % $this->slice) % 6] .
            str_repeat(chr(8), $this->percEmptySize + $pperc + 1)
        );
    }

}