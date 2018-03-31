<?php
namespace Nessworthy\Button\Repository;

class MockAsyncRepository implements AsyncRepositoryStorage
{
    public function getLastCommitReference(string $branchName)
    {
        return new \Amp\Success(sha1(random_bytes(32)));
    }
}