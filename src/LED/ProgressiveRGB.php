<?php

namespace Nessworthy\Button\LED;

use Nessworthy\Button\Progressor\ProgressivePart;
use React\EventLoop\LoopInterface;
use React\EventLoop\Timer\TimerInterface;

class ProgressiveRGB implements ProgressivePart
{
    /**
     * @var LoopInterface
     */
    private $loop;
    /**
     * @var RGB
     */
    private $led;

    /**
     * @var TimerInterface
     */
    private $timer;

    public function __construct(
        RGB $led,
        LoopInterface $loop
    ) {
        $this->loop = $loop;
        $this->led = $led;
    }

    public function setLoading()
    {
        $led = $this->led;
        $switch = false;
        $led->setYellow();

        $this->setTimer(
            $this->loop->addPeriodicTimer(0.4, function () use ($led, &$switch) {
                if ($switch) {
                    $led->setYellow();
                } else {
                    $led->setNone();
                }
                $switch = !$switch;
            })
        );
    }

    public function setSuccess()
    {
        $this->clearTimer();
        $this->led->setGreen();
    }

    public function setError()
    {
        $this->clearTimer();
        $this->led->setRed();
    }

    public function reset()
    {
        $this->clearTimer();
        $this->led->setNone();
    }

    private function setTimer(TimerInterface $timer)
    {
        $this->clearTimer();
        $this->timer = $timer;
    }

    private function clearTimer()
    {
        if ($this->timer) {
            $this->timer->cancel();
        }
    }

    public function setComplete()
    {
        $this->setSuccess();
        $led = $this->led;
        $this->loop->addTimer(5, function() use ($led) {
            $led->setNone();
        });
    }
}