<?php
namespace Nessworthy\Button\Progressor;

class PartProgressor implements Progressor
{
    /**
     * @var int
     */
    private $progressTotal;

    /**
     * @var ProgressivePart[]
     */
    private $parts;

    /**
     * @var int
     */
    private $totalParts;

    /**
     * @var float|int
     */
    private $progressIncrement;
    private $currentProgress = 0;

    public function __construct(int $progressTotal, ProgressivePart ...$progressiveParts)
    {
        $this->progressTotal = $progressTotal;
        $this->parts = $progressiveParts;
        $this->totalParts = \count($progressiveParts);
        $this->progressIncrement = $this->totalParts === 0 ? 0 : (1 / $this->totalParts);
    }

    public function setProgress(int $progress)
    {
        $this->currentProgress = $progress;
        $actualProgress = $this->getProgress($progress);
        $partsToProgress = $this->getNumberPartsToAffect($actualProgress);
        $this->progressUpTo((int) $partsToProgress);
    }

    public function errorAtCurrent()
    {
        $actualProgress = $this->getProgress($this->currentProgress);
        $partsToProgress = $this->getNumberPartsToAffect($actualProgress);
        $this->setErrorAt((int) $partsToProgress);
    }

    public function complete()
    {
        foreach($this->parts as $part) {
            $part->setComplete();
        }
    }

    /**
     * @param int $progress
     * @return float|int
     */
    private function getProgress(int $progress)
    {
        if ($progress > $this->progressTotal) {
            return 1;
        }
        if ($progress === 0) {
            return 0;
        }
        return $progress / $this->progressTotal;
    }

    private function getNumberPartsToAffect($actualProgress)
    {
        if ($actualProgress === 1) {
            return $this->totalParts;
        }

        if ($actualProgress === 0) {
            return 0;
        }

        return floor($actualProgress / $this->progressIncrement);
    }

    private function progressUpTo(int $partsToProgress)
    {
        $part = 1;
        while ($part <= $this->totalParts) {
            switch ($part <=> $partsToProgress) {
                case -1:
                    $this->parts[$part - 1]->setSuccess();
                    break;
                case 0:
                    $this->parts[$part - 1]->setLoading();
                    break;
                case 1:
                    $this->parts[$part - 1]->reset();
                    break;
            }

            $part++;
        }
    }

    private function setErrorAt(int $partIndex)
    {
        $part = 1;
        while ($part <= $this->totalParts) {
            switch ($part <=> $partIndex) {
                case -1:
                    $this->parts[$part - 1]->setSuccess();
                    break;
                case 0:
                    $this->parts[$part - 1]->setError();
                    break;
                case 1:
                    $this->parts[$part - 1]->reset();
                    break;
            }

            $part++;
        }
    }
}