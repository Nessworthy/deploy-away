<?php
namespace Nessworthy\Button\Repository;

use Amp\Artax\Client;
use Amp\Artax\Request;

class DeployHQAsyncRepositoryStorage implements AsyncRepositoryStorage
{
    /**
     * @var Client
     */
    private $httpClient;
    /**
     * @var string
     */
    private $username;
    /**
     * @var string
     */
    private $apiKey;
    /**
     * @var string
     */
    private $project;
    /**
     * @var string
     */
    private $account;

    public function __construct(
        Client $httpClient,
        string $account,
        string $username,
        string $apiKey,
        string $project
    ) {

        $this->httpClient = $httpClient;
        $this->account = $account;
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->project = $project;
    }

    private function sendRequest(string $branchName)
    {
        $request = new Request(
            sprintf(
                'https://%s.deployhq.com/projects/%s/repository/latest_revision?branch=%s',
                $this->account,
                $this->project,
                urlencode($branchName)
            ),
            'GET'
        );

        $request = $request->withHeader('Accept', 'application/json')
            ->withHeader('Content-type', 'application/json')
            ->withHeader('Authorization', 'Basic ' . base64_encode($this->username . ':' . $this->apiKey));

        /** @var \Amp\Artax\Response $response */
        $response = yield $this->httpClient->request($request);

        $json = json_decode(yield $response->getBody(), true);

        return $json['ref'];
    }

    public function getLastCommitReference(string $branchName)
    {
        return \Amp\call(function() use ($branchName) {
            return $this->sendRequest($branchName);
        });
    }
}