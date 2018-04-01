<?php declare(strict_types=1);

namespace Nessworthy\Button\Deployment;

use Amp\Artax\Client;
use Amp\Failure;
use Amp\Success;
use Calcinai\PHPi\Board\BoardInterface;
use Nessworthy\Button\Progressor\Progressor;
use Nessworthy\Button\Repository\AsyncRepositoryStorage;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;

class DeployManager {
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var AsyncRepositoryStorage
     */
    private $repositoryStorage;
    /**
     * @var AsyncDeploymentStorage
     */
    private $deploymentStorage;
    /**
     * @var Progressor
     */
    private $progressIndicator;

    public function __construct(
        LoggerInterface $logger,
        Client $httpClient,
        BoardInterface $pinBoard,
        LoopInterface $reactLoop,
        AsyncRepositoryStorage $repositoryStorage,
        AsyncDeploymentStorage $deploymentStorage,
        Progressor $progressIndicator
    ) {
        $this->logger = $logger;
        $this->repositoryStorage = $repositoryStorage;
        $this->deploymentStorage = $deploymentStorage;
        $this->progressIndicator = $progressIndicator;
    }

    public function deploy(string $branch)
    {
        $self = $this;
        return \Amp\call(function() use ($self, $branch) {
            return $self->handleDeploy($branch);
        });
    }

    private function handleDeploy(string $branch)
    {
        $this->logger->notice('Deployment Process Started!');

        $this->progressIndicator->setProgress(1);

        $this->logger->debug('Fetching last deployed ref...');

        $lastDeployedRef = yield $this->deploymentStorage->getLastDeployedRef();

        $this->logger->info('Last Deployed Commit Ref: ' . $lastDeployedRef);
        $this->logger->debug('Fetching branch tip ref...');

        $lastCommitReference = yield $this->repositoryStorage->getLastCommitReference($branch);

        $this->logger->info('Last Commit in Branch Ref: ' . $lastCommitReference);
        $this->logger->debug('Creating deployment...');

        $deploymentId = yield $this->deploymentStorage->createDeployment($lastDeployedRef, $lastCommitReference);

        $this->logger->info('Created Deployment ID: ' . $deploymentId);

        $this->progressIndicator->setProgress(2);

        $this->logger->debug('Creating deploy watcher...');

        $watcher = $this->deploymentStorage->watchDeployment($deploymentId);

        $this->logger->info('Watching deployment for progress...');

        // TODO: Timeout on deploy?
        try {
            yield $watcher->prepared();
            $this->logger->notice('(1/4) Deployment prepared!');

            yield $watcher->built();
            $this->logger->notice('(2/4) Deployment built!');
            $this->progressIndicator->setProgress(3);

            yield $watcher->transferred();
            $this->logger->notice('(3/4) Deployment transferred!');

            yield $watcher->finished();
            $this->logger->notice('(4/4) Deployment completed!');

            $this->progressIndicator->complete();
            return new Success();
        } catch (DeploymentFailedException $exception) {
            $this->logger->error('Deployment failed: ' . $exception->getMessage());
            $this->progressIndicator->errorAtCurrent();
            return new Failure($exception->getMessage());
        }
    }
}
