<?php declare(strict_types=1);

namespace Nessworthy\Button\Button;

use Amp\Deferred;
use Calcinai\PHPi\External\Generic\Button;

class AmpButton
{
    /**
     * @var Button
     */
    private $button;
    /**
     * @var Deferred
     */
    private $deferred;

    public function __construct(
        Button $button
    ) {
        $this->button = $button;
    }

    public function waitForPress()
    {
        if(!$this->deferred) {
            $this->bind();
        }

        return $this->deferred->promise();
    }

    private function bind()
    {
        $self = $this;

        $this->deferred = new Deferred();

        $this->button->on('release', function() use ($self) {
            $currentDeferred = $self->deferred;
            $self->deferred = new Deferred();
            $currentDeferred->resolve();
        });
    }
}