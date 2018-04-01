<?php declare(strict_types=1);

namespace Nessworthy\Button;

use Nessworthy\Button\Button\AmpButton;
use Nessworthy\Button\Deployment\DeployManager;
use Nessworthy\Button\LED\Simple;
use Nessworthy\Button\Progressor\Progressor;
use Psr\Log\LoggerInterface;

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
    /**
     * @var AmpButton
     */
    private $ampButton;
    /**
     * @var array
     */
    private $config;
    /**
     * @var Simple
     */
    private $buttonLed;

    public function __construct(
        LoggerInterface $logger,
        Progressor $progressIndicator,
        DeployManager $deployManager,
        AmpButton $ampButton,
        Simple $buttonLed,
        array $config
    ) {
        $this->logger = $logger;
        $this->progressIndicator = $progressIndicator;
        $this->deployManager = $deployManager;
        $this->ampButton = $ampButton;
        $this->config = $config;
        $this->buttonLed = $buttonLed;
    }

    public function run()
    {
        $this->logger->debug('Loop started!');

        try {
            $deployManager = $this->deployManager;
            $ampButton = $this->ampButton;

            while(true) {

                $this->buttonLed->on();

                yield $ampButton->waitForPress();

                $this->buttonLed->off();

                $this->logger->debug('Button Pressed!');

                yield $deployManager->deploy($this->config['DEPLOY_BRANCH']);
            }

        } catch (\Throwable $e) {
            $this->progressIndicator->errorAtCurrent();
            $this->logger->error('Error caught - ' . \get_class($e) . ': ' . $e->getMessage());
            die();
        }
    }
}