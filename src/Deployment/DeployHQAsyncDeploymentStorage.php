<?php declare(strict_types=1);

namespace Nessworthy\Button\Deployment;

use Amp\Artax\Client;
use Amp\Artax\Request;
use Amp\Deferred;
use Amp\Delayed;
use Amp\Loop;

class DeployHQAsyncDeploymentStorage implements AsyncDeploymentStorage
{
    /**
     * @var Client
     */
    private $httpClient;
    /**
     * @var string
     */
    private $account;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var string
     */
    private $project;
    /**
     * @var string
     */
    private $serverUuid;

    public function __construct(
        Client $httpClient,
        string $account,
        string $username,
        string $apiKey,
        string $project,
        string $serverUuid
    ) {
        $this->httpClient = $httpClient;
        $this->account = $account;
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->project = $project;
        $this->serverUuid = $serverUuid;
    }

    public function getLastDeployedRef()
    {
        return \Amp\call(function() {
            $request = new Request(
                sprintf(
                    'https://%s.deployhq.com/projects/%s/deployments?page=1&to=%s',
                    $this->account,
                    $this->project,
                    urlencode($this->serverUuid)
                ),
                'GET'
            );

            $request = $request->withHeader('Accept', 'application/json')
                ->withHeader('Content-type', 'application/json')
                ->withHeader('Authorization', 'Basic ' . base64_encode($this->username . ':' . $this->apiKey));

            /** @var \Amp\Artax\Response $response */
            $response = yield $this->httpClient->request($request);

            $data = json_decode(yield $response->getBody(), true);

            foreach ($data['records'] as $record) {
                if (!empty($record['end_revision']['ref'])) {
                    return $record['end_revision']['ref'];
                }
            }
            throw new \Exception('Could not determine last revision reference.');
        });
    }

    public function viewDeploymentStatus(string $deploymentUuid)
    {
        return \Amp\call(function() use ($deploymentUuid) {

            $request = new Request(
                sprintf(
                    'https://%s.deployhq.com/projects/%s/deployments/%s',
                    $this->account,
                    $this->project,
                    $deploymentUuid
                ),
                'GET'
            );

            $request = $request
                ->withHeader('Accept', 'application/json')
                ->withHeader('Content-type', 'application/json')
                ->withHeader('Authorization', 'Basic ' . base64_encode($this->username . ':' . $this->apiKey));

            /** @var \Amp\Artax\Response $response */
            $response = yield $this->httpClient->request($request);
            $data = json_decode(yield $response->getBody(), true);

            $progress = [
                'preparing' => 4,
                'building' => 4,
                'transferring' => 4,
                'finishing' => 4,
            ];
            $progressStages = [
                'failed' => 1,
                'running' => 2,
                'pending' => 3,
                'completed' => 4,
            ];
            foreach ($data['steps'] as $step) {
                if (!isset($progressStages[$data['status']])) {
                    echo 'UNKNOWN STATUS: ' . $data['status'] . "\n";
                    continue;
                }
                $numericProgress = $progressStages[$step['status']];
                $progress[$step['stage']] = $numericProgress < $progress[$step['stage']]
                    ? $numericProgress
                    : $progress[$step['stage']];
            }
            $numericKeysToStatus = array_flip($progressStages);

            $status = $data['status'];

            // DeployHQ marks deploys as completed before sending notifications.
            // This program wants to make sure all steps are done before making it as complete, so suppress it.
            if ($status === 'completed' && min($progress) !== 4) {
                $status = 'pending';
            }

            return new DeploymentStatus(
                $status,
                $numericKeysToStatus[$progress['preparing']],
                $numericKeysToStatus[$progress['building']],
                $numericKeysToStatus[$progress['transferring']],
                $numericKeysToStatus[$progress['finishing']]
            );
        });
    }

    public function createDeployment(string $fromRevision, string $toRevision)
    {
        return \Amp\call(function() use ($fromRevision, $toRevision) {
            $request = new Request(
                sprintf(
                    'https://%s.deployhq.com/projects/%s/deployments',
                    $this->account,
                    $this->project
                ),
                'POST'
            );

            $payload = json_encode([
                'deployment' => [
                    'parent_identifier' => $this->serverUuid,
                    'start_revision' => $fromRevision,
                    'end_revision' => $toRevision,
                    'mode' => 'queue',
                    'copy_config_files' => 0,
                    'email_notify' => 0,
                    'run_build_commands' => 0,
                ]
            ]);

            $request = $request
                ->withHeader('Accept', 'application/json')
                ->withHeader('Content-type', 'application/json')
                ->withHeader('Authorization', 'Basic ' . base64_encode($this->username . ':' . $this->apiKey))
                ->withBody($payload);

            /** @var \Amp\Artax\Response $response */
            $response = yield $this->httpClient->request($request);

            $data = json_decode(yield $response->getBody(), true);
            return $data['identifier'];
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

        Loop::defer(function() use ($deploymentUuid, $deploymentState) {

            $completed = false;
            while ($completed === false) {
                /** @var DeploymentStatus $status */
                $status = yield $this->viewDeploymentStatus($deploymentUuid);

                if ($status->getPreparingStatus() === DeploymentStatus::STATUS_COMPLETE) {
                    $deploymentState->resolveOnPrepared();
                }
                if ($status->getPreparingStatus() === DeploymentStatus::STATUS_FAILED) {
                    $deploymentState->errorOnPrepared('Deployment failed while preparing.');
                }
                if ($status->getBuildingStatus() === DeploymentStatus::STATUS_COMPLETE) {
                    $deploymentState->resolveOnBuilt();
                }
                if ($status->getBuildingStatus() === DeploymentStatus::STATUS_FAILED) {
                    $deploymentState->errorOnBuilt('Deployment failed while building.');
                }
                if ($status->getTransferringStatus() === DeploymentStatus::STATUS_COMPLETE) {
                    $deploymentState->resolveOnTransferred();
                }
                if ($status->getTransferringStatus() === DeploymentStatus::STATUS_FAILED) {
                    $deploymentState->errorOnTransferred('Deployment failed while transferring.');
                }
                if ($status->getFinishingStatus() === DeploymentStatus::STATUS_COMPLETE) {
                    $deploymentState->resolveOnFinished();
                }
                if ($status->getFinishingStatus() === DeploymentStatus::STATUS_FAILED) {
                    $deploymentState->errorOnFinished('Deployment failed while finishing.');
                }

                $completed = $status->isCompleted() || $status->isFailed();
                if (!$completed) {
                    yield new Delayed(500);
                }
            }
        });

        return new DeployWatcher(
            $onPrepare->promise(),
            $onBuilt->promise(),
            $onTransferred->promise(),
            $onFinished->promise()
        );
    }
}