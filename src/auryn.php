<?php declare(strict_types=1);

use Amp\Artax\Client;
use Calcinai\PHPi\Board;
use Calcinai\PHPi\Board\BoardInterface;
use Nessworthy\Button\Deployment\AsyncDeploymentStorage;
use Nessworthy\Button\Deployment\DeployHQAsyncDeploymentStorage;
use Nessworthy\Button\LED\Labeler;
use Nessworthy\Button\LED\MockRGB;
use Nessworthy\Button\LED\ProgressiveRGB;
use Nessworthy\Button\Repository\AsyncRepositoryStorage;
use Nessworthy\Button\Repository\DeployHQAsyncRepositoryStorage;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

/**
 * @param \Auryn\Injector $injector
 * @param array $config
 * @throws Exception
 * @throws \Auryn\ConfigException
 */
function auryn_setup_dependencies(\Auryn\Injector $injector, array $config) {

    $injector->share($injector);

    $injector->defineParam('config', $config);

    $injector->share(LoggerInterface::class);
    $injector->alias(LoggerInterface::class, Monolog\Logger::class);
    $injector->define(Monolog\Logger::class, [
        ':name' => 'Button',
        ':handlers' => [new \Monolog\Handler\StreamHandler(fopen('php://output', 'wb+'))]
    ]);

    $injector->share(Client::class);
    $injector->alias(Amp\Artax\Client::class, Amp\Artax\DefaultClient::class);

    $injector->share(LoopInterface::class);
    $injector->delegate(LoopInterface::class, [\Amp\ReactAdapter\ReactAdapter::class, 'get']);

    $injector->share(BoardInterface::class);
    $injector->share(Board::class);
    $injector->alias(BoardInterface::class, Board::class);
    $injector->delegate(Board::class, [\Calcinai\PHPi\Factory::class, 'create']);

    $injector->share(AsyncDeploymentStorage::class);
    $injector->alias(AsyncDeploymentStorage::class, \Nessworthy\Button\Deployment\MockAsyncDeploymentStorage::class);
    //$injector->alias(AsyncDeploymentStorage::class, \Nessworthy\Button\Deployment\DeployHQAsyncDeploymentStorage::class);
    $injector->define(DeployHQAsyncDeploymentStorage::class, [
        ':account' => $config['DEPLOY_ACCOUNT'],
        ':username' => $config['DEPLOY_USER'],
        ':apiKey' => $config['DEPLOY_KEY'],
        ':project' => $config['DEPLOY_PROJECT'],
        ':serverUuid' => $config['DEPLOY_SERVER_UUID']
    ]);

    $injector->share(AsyncRepositoryStorage::class);
    $injector->alias(AsyncRepositoryStorage::class, \Nessworthy\Button\Repository\MockAsyncRepository::class);
    //$injector->alias(AsyncRepositoryStorage::class, \Nessworthy\Button\Repository\DeployHQAsyncRepositoryStorage::class);
    $injector->define(DeployHQAsyncRepositoryStorage::class, [
        ':account' => $config['DEPLOY_ACCOUNT'],
        ':username' => $config['DEPLOY_USER'],
        ':apiKey' => $config['DEPLOY_KEY'],
        ':project' => $config['DEPLOY_PROJECT'],
        ':serverUuid' => $config['DEPLOY_SERVER_UUID']
    ]);

    $injector->alias(\Nessworthy\Button\LED\RGB::class, MockRGB::class);
    $injector->alias(\Nessworthy\Button\Progressor\ProgressivePart::class, ProgressiveRGB::class);

    $injector->share(\Nessworthy\Button\Progressor\Progressor::class);
    $injector->alias(\Nessworthy\Button\Progressor\Progressor::class, \Nessworthy\Button\Progressor\PartProgressor::class);

    $injector->share(Labeler::class);
    $injector->delegate(MockRGB::class, function(LoggerInterface $logger, Labeler $ledLabeler) {
        return new MockRGB($logger, $ledLabeler->createLabel());
    });

    $injector->delegate(
        \Nessworthy\Button\Progressor\PartProgressor::class,
        function(
            \Auryn\Injector $injector,
            Board $board,
            LoopInterface $loop
        ) use (
            $config
        ) {

        /*$progressorParts = [
            $injector->make(\Nessworthy\Button\Progressor\ProgressivePart::class),
            $injector->make(\Nessworthy\Button\Progressor\ProgressivePart::class),
            $injector->make(\Nessworthy\Button\Progressor\ProgressivePart::class),
        ];*/

        // TODO: Move to factory.
        $progressorParts = [
            new ProgressiveRGB(
                new \Nessworthy\Button\LED\GpioRGB(
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_1_RED'])),
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_1_GREEN'])),
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_1_BLUE']))
                ),
                $loop
            ),
            new ProgressiveRGB(
                new \Nessworthy\Button\LED\GpioRGB(
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_2_RED'])),
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_2_GREEN'])),
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_2_BLUE']))
                ),
                $loop
            ),
            new ProgressiveRGB(
                new \Nessworthy\Button\LED\GpioRGB(
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_3_RED'])),
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_3_GREEN'])),
                    new \Calcinai\PHPi\External\Generic\LED($board->getPin($config['RGB_LED_PIN_3_BLUE']))
                ),
                $loop
            ),
        ];

        $progressor = new \Nessworthy\Button\Progressor\PartProgressor(3, ...$progressorParts);
        return $progressor;
    });

    $injector->delegate(\Nessworthy\Button\Button\AmpButton::class, function(Board $board, array $config) {
        $pin = $board->getPin($config['BUTTON_OUTPUT_PIN']);
        $pin->setFunction(\Calcinai\PHPi\Pin\PinFunction::OUTPUT);
        $pin->high();
        $button = new \Calcinai\PHPi\External\Generic\Button($pin);
        return new \Nessworthy\Button\Button\AmpButton($button);
    });

    $injector->share(\Nessworthy\Button\LED\Simple::class);
    $injector->alias(\Nessworthy\Button\LED\Simple::class, \Nessworthy\Button\LED\GpioSimple::class);
    $injector->delegate(\Nessworthy\Button\LED\GpioSimple::class, function(Board $board, array $config) {
        $pin = $board->getPin($config['BUTTON_LED_PIN']);
        $led = new \Calcinai\PHPi\External\Generic\LED($pin);
        return new \Nessworthy\Button\LED\GpioSimple($led);
    });
}