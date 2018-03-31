<?php

namespace Nessworthy\Button\Deployment;

use Amp\Deferred;
use Amp\Delayed;
use Amp\Failure;
use Amp\Loop;
use Amp\Success;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

class MockAsyncDeploymentStorage implements  AsyncDeploymentStorage
{
    private $mockDeploys = [];
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLastDeployedRef()
    {
        return new Success(sha1(random_bytes(32)));
    }

    public function createDeployment(string $fromRevision, string $toRevision)
    {
        $uuid = Uuid::uuid5(Uuid::NAMESPACE_DNS, 'button.dev')->toString();

        $currentTime = microtime(true) * 10000;

        $data = [
            'id' => $uuid,
            'preparing' => $currentTime += random_int(5000, 15000),
            'building' => $currentTime += random_int(5000, 20000),
            'transferring' => $currentTime += random_int(10000, 50000),
            'finishing' => $currentTime + random_int(5000, 15000),
        ];

        $this->mockDeploys[$uuid] = $data;

        $this->logger->debug('Mock Deployment UUID ' . $uuid . ' created!');

        return new \Amp\Success($uuid);
    }

    public function viewDeploymentStatus(string $deploymentUuid)
    {
        $self = $this;
        return \Amp\call(function() use ($self, $deploymentUuid) {
            yield new Delayed(random_int(100, 500)); // Emulate network & processing.

            $currentTime = microtime(true) * 10000;

            if (!isset($self->mockDeploys[$deploymentUuid])) {
                return new Failure(new \RuntimeException('Invalid deployment UUID.'));
            }

            $deployment = $self->mockDeploys[$deploymentUuid];

            return new DeploymentStatus(
                $deployment['finishing'] < $currentTime ? DeploymentStatus::STATUS_COMPLETE : DeploymentStatus::STATUS_PENDING,
                $deployment['preparing'] < $currentTime ? DeploymentStatus::STATUS_COMPLETE : DeploymentStatus::STATUS_PENDING,
                $deployment['building'] < $currentTime ? DeploymentStatus::STATUS_COMPLETE : DeploymentStatus::STATUS_PENDING,
                $deployment['transferring'] < $currentTime ? DeploymentStatus::STATUS_COMPLETE : DeploymentStatus::STATUS_PENDING,
                $deployment['finishing'] < $currentTime ? DeploymentStatus::STATUS_COMPLETE : DeploymentStatus::STATUS_PENDING
            );

        });
    }

    public function watchDeployment(string $deploymentUuid): DeployWatcher
    {
        $onPrepare = new Deferred();
        $onBuilt = new Deferred();
        $onTransferred = new Deferred();
        $onFinished = new Deferred();

        $deploymentState = new DeploymentResolutionState(
            $onPrepare,
            $onBuilt,
            $onTransferred,
            $onFinished
        );

        $self = $this;

        Loop::defer(function() use ($self, $deploymentUuid, $deploymentState) {

            $completed = false;
            while ($completed === false) {

                /** @var DeploymentStatus $status */
                $status = yield $self->viewDeploymentStatus($deploymentUuid);

                $this->logger->debug('Mock watch poll update.');

                if ($status->getPreparingStatus() === DeploymentStatus::STATUS_COMPLETE) {
                    $deploymentState->resolveOnPrepared();
                }
                if ($status->getBuildingStatus() === DeploymentStatus::STATUS_COMPLETE) {
                    $deploymentState->resolveOnBuilt();
                }
                if ($status->getTransferringStatus() === DeploymentStatus::STATUS_COMPLETE) {
                    $deploymentState->resolveOnTransferred();
                }
                if ($status->getFinishingStatus() === DeploymentStatus::STATUS_COMPLETE) {
                    $deploymentState->resolveOnFinished();
                }

                $completed = $status->isCompleted();
                if (!$completed) {
                    yield new Delayed(500);
                }

            }
        });

        $this->logger->debug('Creating Watcher for UUID ' . $deploymentUuid);

        return new DeployWatcher(
            $onPrepare->promise(),
            $onBuilt->promise(),
            $onTransferred->promise(),
            $onFinished->promise()
        );
    }
}