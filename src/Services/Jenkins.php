<?php

namespace BuildQueue\Services;


use BuildQueue\DataObject\BuildCache;
use BuildQueue\DataObject\BuildResult;
use Guzzle\Http\Client;
use Guzzle\Http\Exception\ClientErrorResponseException;
use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Http\Message\Header;
use Guzzle\Http\Message\Response;

class Jenkins
{
    /**
     * @var string
     */
    private $buildUrl;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $passkey;

    /**
     * @var Client
     */
    private $httpClient;

    /**
     * Jenkins constructor.
     *
     * @param Client $httpClient
     * @param string $buildUrl
     * @param string $username
     * @param string $passkey
     */
    public function __construct($httpClient, $buildUrl, $username, $passkey)
    {
        $this->httpClient = $httpClient;
        $this->buildUrl = $buildUrl;
        $this->username = $username;
        $this->passkey = $passkey;
    }

    /**
     * @param string $repo
     * @param string $branch
     * @return BuildResult
     */
    public function buildRepo($repo, $branch)
    {
        //Create a build
        $buildId = $this->createBuild($repo, $branch);
        $buildCache = BuildCache::load($repo, $branch);

        if ($buildCache->getBuildResult() instanceof BuildResult) {
            $buildResult = $buildCache->getBuildResult();
            if ($buildResult->getResult() != BuildResult::UNKNOWN) {
                return $buildCache->getBuildResult();
            }
        } else {
            $buildResult = new BuildResult($buildId);
        }

        do {
            $buildResponse = $this->doGetRequest($this->buildUrl . "/job/$repo/$buildId/api/json");
            $buildResponse = json_decode($buildResponse->getBody(true));

            $buildId = $buildResponse->number;
            $resultInResponse = $buildResponse->result;

            $buildResult->setResult($resultInResponse);

            $buildCache->setBuildResult($buildResult)->save();

            sleep(3);
        } while ($resultInResponse == null);

        $buildResult->setResult($resultInResponse);
        $buildCache->setBuildResult($buildResult)->save();

        return $buildResult;
    }

    /**
     * @return Header
     */
    protected function getCrumbHeader()
    {
        $response = $this->doGetRequest($this->buildUrl . '/crumbIssuer/api/json');
        $crumb = json_decode((string)$response->getBody(), true)['crumb'];
        return new Header('.crumb', $crumb);
    }

    /**
     * Get the build id for the queued build
     *
     * @param string $repo
     * @param string $branch
     * @return int
     */
    protected function createBuild($repo, $branch)
    {
        $buildCache = BuildCache::load($repo, $branch);

        $queueLocation = $buildCache->getQueueLocation();

        if ($queueLocation) {
            dump("Loading from previous queue " . $queueLocation); #TODO: Switch to using native Verbosity param
        } else {
            $response = $this->getQueueLocation($repo, $branch);
            $queueLocation = (string) $response->getHeader('Location');
            $buildCache->setQueueLocation($queueLocation)
                ->save();
        }

        if ($buildCache->getJobId()) {
            dump("Last job found: " . $buildCache->getJobId()); #TODO: Switch to using native Verbosity param
            return $buildCache->getJobId();
        }

        do {
            //Get the queue URL for the build
            $buildUrl = $queueLocation . 'api/json';
            $buildInfoResponse = $this->doGetRequest($buildUrl);

            $buildInfoResponse = json_decode($buildInfoResponse->getBody(true));

            $jsonWhy = $buildInfoResponse->why;
            $executable = isset($buildInfoResponse->executable) ? $buildInfoResponse->executable : null;

            $sleepFor = 5;
            if (isset($buildInfoResponse->buildableStartMilliseconds)) {
                $sleepFor = ceil((($buildInfoResponse->buildableStartMilliseconds/1000) - time()) / 100);
            }

            dump("Sleeping for $sleepFor seconds because: " . $jsonWhy); #TODO: Switch to using native Verbosity param
            sleep($sleepFor);
        } while ($jsonWhy != null && $executable == null);

        $jobId = $buildInfoResponse->executable->number;

        $buildCache->setJobId($jobId)
            ->save();

        return $jobId;
    }

    /**
     * @param string $repo
     * @param string $branch
     * @return Response
     */
    protected function getQueueLocation($repo, $branch)
    {
        $header = $this->getCrumbHeader();
        $post = new EntityEnclosingRequest('POST', $this->buildUrl, [$header]);
        $post->setAuth($this->username, $this->passkey)
            ->setPath('/job/' . $repo . '/buildWithParameters/api/xml')
            ->setPostField('BUILD_TYPE', $branch);

        try {
            return $this->httpClient->send($post);
        } catch (ClientErrorResponseException $e) {
            $response = $e->getResponse();
            switch ($response->getStatusCode()) {
                case 404:
                    throw new \InvalidArgumentException("Repository '$repo' not found");;
                default:
                    throw new $e;
            }
        }
    }

    /**
     * @param string $url
     * @return Response
     */
    private function doGetRequest($url)
    {
        return $this->httpClient->get($url)
            ->setAuth($this->username, $this->passkey)
            ->send();
    }
}
