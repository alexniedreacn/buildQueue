<?php

namespace BuildQueue\DataObject;

class BuildCache
{
    private $queueLocation;

    private $jobId;

    private $filename;

    /**
     * @var BuildResult
     */
    private $buildResult;

    const LOCK_DIR = '.lock/';

    private function __construct()
    {
    }

    /**
     * @return mixed
     */
    public function getQueueLocation()
    {
        return $this->queueLocation;
    }

    /**
     * @param mixed $queueLocation
     *
     * @return BuildCache
     */
    public function setQueueLocation($queueLocation)
    {
        $this->queueLocation = $queueLocation;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getJobId()
    {
        return $this->jobId;
    }

    /**
     * @param mixed $jobId
     *
     * @return BuildCache
     */
    public function setJobId($jobId)
    {
        $this->jobId = $jobId;
        return $this;
    }

    /**
     * @return BuildResult
     */
    public function getBuildResult()
    {
        return $this->buildResult;
    }

    /**
     * @param BuildResult $buildResult
     *
     * @return BuildCache
     */
    public function setBuildResult($buildResult)
    {
        $this->buildResult = $buildResult;
        return $this;
    }

    public function save()
    {
        file_put_contents($this->filename, serialize($this));
    }

    public static function load($repo, $branch)
    {
        $filename = self::getFilename($repo, $branch);

        if (!is_dir(self::LOCK_DIR)) {
            mkdir(self::LOCK_DIR);
        }

        if (file_exists($filename)) {
            return unserialize(file_get_contents($filename));
        }

        $class = new self;
        $class->filename = $filename;
        return $class;
    }

    public static function clear($repo, $branch)
    {
        $filename = self::getFilename($repo, $branch);

        if (file_exists($filename)) {
            unlink($filename);
        }
    }

    /**
     * @param $repo
     * @param $branch
     * @return string
     */
    private static function getFilename($repo, $branch)
    {
        return self::LOCK_DIR . md5($repo . $branch) . '.lock';
    }
}
