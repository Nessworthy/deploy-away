<?php declare(strict_types=1);

namespace Nessworthy\Button;

use Amp\Delayed;
use Nessworthy\Button\Deployment\DeployManager;
use Nessworthy\Button\Progressor\Progressor;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class LoopRunner
{
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var Progressor
     */
    private $progressIndicator;
    /**
     * @var DeployManager
     */
    private $deployManager;

    public function __construct(
        LoggerInterface $logger,
        LoopInterface $reactLoop,
        Progressor $progressIndicator,
        DeployManager $deployManager
    ) {
        $this->logger = $logger;
        $this->progressIndicator = $progressIndicator;
        $this->deployManager = $deployManager;
    }

    public function run()
    {
        $this->logger->debug('Loop started!');

        try {

            // Set up button.

            yield new Delayed(500);

            // Enable watching for button changes.

            yield $this->deployManager->deploy();

        } catch (\Throwable $e) {
            $this->progressIndicator->errorAtCurrent();
            $this->logger->error('Error caught - ' . \get_class($e) . ': ' . $e->getMessage());
            die();
        }
    }
}