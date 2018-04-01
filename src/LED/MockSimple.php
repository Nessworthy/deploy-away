<?php declare(strict_types=1);

namespace LED;

use Calcinai\PHPi\External\Generic\LED;
use Nessworthy\Button\LED\Simple;
use Psr\Log\LoggerInterface;

class MockSimple implements Simple
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var string
     */
    private $label;

    public function __construct(
        LoggerInterface $logger,
        string $label
    ) {
        $this->logger = $logger;
        $this->label = $label;
    }

    public function on()
    {
        $this->logger->debug(sprintf('[%s] ON', $this->label));
    }

    public function off()
    {
        $this->logger->debug(sprintf('[%s] OFF', $this->label));
    }

}