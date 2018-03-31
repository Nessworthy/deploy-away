<?php
namespace Nessworthy\Button\Progressor;

interface ProgressivePart
{
    public function setLoading();
    public function setSuccess();
    public function setComplete();
    public function setError();
    public function reset();
}