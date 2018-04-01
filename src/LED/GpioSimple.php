<?php declare(strict_types=1);

namespace Nessworthy\Button\LED;

use Calcinai\PHPi\External\Generic\LED;
use Nessworthy\Button\LED\Simple;

class GpioSimple implements Simple
{
    /**
     * @var LED
     */
    private $led;

    public function __construct(
      LED $led
    ) {
        $this->led = $led;
    }

    public function on()
    {
        $this->led->on();
    }

    public function off()
    {
        $this->led->off();
    }

}