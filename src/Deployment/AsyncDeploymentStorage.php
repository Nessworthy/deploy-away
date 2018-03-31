<?php

namespace Nessworthy\Button\Deployment;

interface AsyncDeploymentStorage
{
    public function getLastDeployedRef();

    public function createDeployment(string $fromRevision, string $toRevision);

    public function viewDeploymentStatus(string $deploymentUuid);

    public function watchDeployment(string $deploymentUuid): DeployWatcher;
}