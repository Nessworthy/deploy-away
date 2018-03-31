<?php
namespace Nessworthy\Button\Progressor;

interface Progressor
{
    public function setProgress(int $progress);

    public function errorAtCurrent();

    public function complete();
}