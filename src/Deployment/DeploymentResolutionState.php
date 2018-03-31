<?php
namespace Nessworthy\Button\Deployment;

use Amp\Deferred;

class DeploymentResolutionState
{
    /**
     * @var Deferred
     */
    private $onPrepared;
    /**
     * @var Deferred
     */
    private $onBuilt;
    /**
     * @var Deferred
     */
    private $onTransferred;
    /**
     * @var Deferred
     */
    private $onFinished;

    private $onPreparedResolved = false;
    private $onBuiltResolved = false;
    private $onTransferredResolved = false;
    private $onFinishedResolved = false;

    public function __construct(
        Deferred $onPrepared,
        Deferred $onBuilt,
        Deferred $onTransferred,
        Deferred $onFinished
    ) {

        $this->onPrepared = $onPrepared;
        $this->onBuilt = $onBuilt;
        $this->onTransferred = $onTransferred;
        $this->onFinished = $onFinished;
    }

    public function resolveOnPrepared()
    {
        if (!$this->onPreparedResolved) {
            $this->onPreparedResolved = true;
            $this->onPrepared->resolve();
        }
    }

    public function resolveOnBuilt()
    {
        if (!$this->onBuiltResolved) {
            $this->onBuiltResolved = true;
            $this->onBuilt->resolve();
        }
    }

    public function resolveOnTransferred()
    {
        if (!$this->onTransferredResolved) {
            $this->onTransferredResolved = true;
            $this->onTransferred->resolve();
        }
    }

    public function resolveOnFinished()
    {
        if (!$this->onFinishedResolved) {
            $this->onFinishedResolved = true;
            $this->onFinished->resolve();
        }
    }

    public function errorOnPrepared($error)
    {
        if (!$this->onPreparedResolved) {
            $this->onPreparedResolved = true;
            $this->onPrepared->fail(new DeploymentFailedException($error));
        }
    }

    public function errorOnBuilt($error)
    {
        if (!$this->onBuiltResolved) {
            $this->onBuiltResolved = true;
            $this->onBuilt->fail(new DeploymentFailedException($error));
        }
    }

    public function errorOnTransferred($error)
    {
        if (!$this->onTransferredResolved) {
            $this->onTransferredResolved = true;
            $this->onTransferred->fail(new DeploymentFailedException($error));
        }
    }

    public function errorOnFinished($error)
    {
        if (!$this->onFinishedResolved) {
            $this->onFinishedResolved = true;
            $this->onFinished->fail(new DeploymentFailedException($error));
        }
    }
}