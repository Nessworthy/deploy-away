<?php
namespace Nessworthy\Button\Deployment;

use Amp\Promise;

class DeployWatcher
{
    /**
     * @var Promise
     */
    private $onPrepared;
    /**
     * @var Promise
     */
    private $onBuilt;
    /**
     * @var Promise
     */
    private $onTransferred;
    /**
     * @var Promise
     */
    private $onFinished;

    public function __construct(
        Promise $onPrepared,
        Promise $onBuilt,
        Promise $onTransferred,
        Promise $onFinished
    ) {
        $this->onPrepared = $onPrepared;
        $this->onBuilt = $onBuilt;
        $this->onTransferred = $onTransferred;
        $this->onFinished = $onFinished;
    }

    public function onPrepared(callable $callback) {
        $this->onPrepared->onResolve($callback);
    }

    public function onBuilt(callable $callback) {
        $this->onBuilt->onResolve($callback);
    }

    public function onTransferred(callable $callback) {
        $this->onTransferred->onResolve($callback);
    }

    public function onFinished(callable $callback) {
        $this->onFinished->onResolve($callback);
    }

    public function prepared(): Promise {
        return $this->onPrepared;
    }

    public function built(): Promise {
        return $this->onBuilt;
    }

    public function transferred(): Promise {
        return $this->onTransferred;
    }

    public function finished(): Promise {
        return $this->onFinished;
    }
}