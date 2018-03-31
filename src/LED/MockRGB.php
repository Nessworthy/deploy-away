<?php


namespace Nessworthy\Button\LED;
use Psr\Log\LoggerInterface;

/**
 * Class StreamRGB
 * Useful for testing RGBs by writing commands to a given stream.
 * @package Nessworthy\Button\LED
 */
class MockRGB implements RGB
{
    private $logger;
    /**
     * @var string
     */
    private $label;

    public function __construct(LoggerInterface $logger, string $label)
    {
        $this->logger = $logger;
        $this->label = $label;
    }

    public function setRed()
    {
        $this->log('Red');
    }

    public function setBlue()
    {
        $this->log('Blue');
    }

    public function setGreen()
    {
        $this->log('Green');
    }

    public function setPurple()
    {
        $this->log('Purple');
    }

    public function setYellow()
    {
        $this->log('Yellow');
    }

    public function setCyan()
    {
        $this->log('Cyan');
    }

    public function setWhite()
    {
        $this->log('White');
    }

    public function setNone()
    {
        $this->log('None');
    }

    private function log(string $string)
    {
        $this->logger->debug(sprintf('[%s]: %s', $this->label, $string));
    }
}