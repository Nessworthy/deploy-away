<?php

namespace Nessworthy\Button\LED;

use Calcinai\PHPi\External\Generic\LED;

class GpioRGB implements RGB
{
    /**
     * @var LED
     */
    private $red;
    /**
     * @var LED
     */
    private $green;
    /**
     * @var LED
     */
    private $blue;

    public function __construct(LED $red, LED $green, LED $blue)
    {
        $this->red = $red;
        $this->green = $green;
        $this->blue = $blue;
    }

    public function setRed()
    {
        $this->green->off();
        $this->blue->off();
        $this->red->on();
    }

    public function setBlue()
    {
        $this->green->off();
        $this->blue->on();
        $this->red->off();
    }

    public function setGreen()
    {
        $this->green->on();
        $this->blue->off();
        $this->red->off();
    }

    public function setPurple()
    {
        $this->green->off();
        $this->blue->on();
        $this->red->on();
    }

    public function setYellow()
    {
        $this->green->on();
        $this->blue->off();
        $this->red->on();
    }

    public function setCyan()
    {
        $this->green->on();
        $this->blue->on();
        $this->red->off();
    }

    public function setWhite()
    {
        $this->green->on();
        $this->blue->on();
        $this->red->on();
    }

    public function setNone()
    {
        $this->green->off();
        $this->blue->off();
        $this->red->off();
    }
}