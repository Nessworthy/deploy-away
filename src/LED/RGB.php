<?php

namespace Nessworthy\Button\LED;

interface RGB
{
    public function setRed();

    public function setBlue();

    public function setGreen();

    public function setPurple();

    public function setYellow();

    public function setCyan();

    public function setWhite();

    public function setNone();
}