<?php
namespace Nessworthy\Button\Repository;

use Amp\Artax\Client;
use Amp\Artax\Request;
use function Room11\DOMUtils\domdocument_load_html;

class CodebaseAsyncRepositoryStorage implements AsyncRepositoryStorage
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
    private $repository;

    public function __construct(Client $httpClient, string $username, string $apiKey, string $project, string $repository)
    {
        $this->httpClient = $httpClient;
        $this->username = $username;
        $this->apiKey = $apiKey;
        $this->project = $project;
        $this->repository = $repository;
    }

    private function sendRequest(string $branchName)
    {
        $request = new Request(
            sprintf(
                'https://api3.codebasehq.com/%s/%s/branches',
                $this->project,
                $this->repository
            ),
            'GET'
        );

        $request = $request->withHeader('Accept', 'application/xml')
            ->withHeader('Content-type', 'application/xml')
            ->withHeader('Authorization', 'Basic ' . base64_encode($this->username . ':' . $this->apiKey));

        /** @var \Amp\Artax\Response $response */
        $response = yield $this->httpClient->request($request);

        $xml = domdocument_load_html(yield $response->getBody());
        $xpath = new \DOMXPath($xml);
        /** @var \DOMNodeList $result */
        $result = $xpath->evaluate(sprintf('//branch/name[text()="%s"]/following-sibling::revision', $branchName));
        return $result->item(0)->textContent;
    }

    public function getLastCommitReference(string $branchName)
    {
        return \Amp\call(function() use ($branchName) {
            return $this->sendRequest($branchName);
        });
    }
}