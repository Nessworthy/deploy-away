<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../src/auryn.php';

$dotEnv = new Dotenv\Dotenv(__DIR__ . '/..');
$dotEnv->load();

$injector = new Auryn\Injector();
auryn_setup_dependencies($injector, $_ENV);

/** @var \Nessworthy\Button\LoopRunner $loopRunner */
$loopRunner = $injector->make(\Nessworthy\Button\LoopRunner::class);

\Amp\Loop::run([$loopRunner, 'run']);