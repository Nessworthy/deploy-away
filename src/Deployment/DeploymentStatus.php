<?php
namespace Nessworthy\Button\Deployment;

class DeploymentStatus
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETE = 'completed';
    const STATUS_RUNNING = 'running';
    const STATUS_FAILED = 'failed';
    /**
     * @var string
     */
    private $preparing;
    /**
     * @var string
     */
    private $building;
    /**
     * @var string
     */
    private $transferring;
    /**
     * @var string
     */
    private $finishing;
    /**
     * @var string
     */
    private $status;

    public function __construct(
        string $status,
        string $preparing,
        string $building,
        string $transferring,
        string $finishing
    ) {

        $this->preparing = $preparing;
        $this->building = $building;
        $this->transferring = $transferring;
        $this->finishing = $finishing;
        $this->status = $status;
    }

    /**
     * @return string
     */
    public function getPreparingStatus(): string
    {
        return $this->preparing;
    }

    /**
     * @return string
     */
    public function getBuildingStatus(): string
    {
        return $this->building;
    }

    /**
     * @return string
     */
    public function getTransferringStatus(): string
    {
        return $this->transferring;
    }

    /**
     * @return string
     */
    public function getFinishingStatus(): string
    {
        return $this->finishing;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETE;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }
}