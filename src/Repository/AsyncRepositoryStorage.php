<?php
namespace Nessworthy\Button\Repository;

interface AsyncRepositoryStorage
{
    public function getLastCommitReference(string $branchName);
}