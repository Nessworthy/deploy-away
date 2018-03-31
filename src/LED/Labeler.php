<?php declare(strict_types=1);

namespace Nessworthy\Button\LED;

class Labeler
{
    private $index = 0;
    public function createLabel(): string
    {
        return 'LED' . $this->index++;
    }
}