<?php
/**
 * Created by PhpStorm.
 * User: aleksandrs.niedre
 * Date: 29/01/2016
 * Time: 20:18
 */

namespace BuildQueue\DataObject;


class BuildResult
{
    private $buildId;

    private $result;

    const ABORTED = 0;
    const SUCCESS = 1;
    const FAILURE = 2;
    const UNKNOWN = 3;

    public function __construct($buildId, $buildResult = self::UNKNOWN)
    {
        $this->buildId = $buildId;
        $this->result = $this->getBuildStatus($buildResult);
    }

    public function getBuildStatus($result)
    {
        $resultMapping = [
            'ABORTED' => 0,
            'SUCCESS' => 1,
            'FAILURE' => 2
        ];

        return isset($resultMapping[$result]) ? $resultMapping[$result] : self::UNKNOWN;
    }

    /**
     * @return mixed
     */
    public function getBuildId()
    {
        return $this->buildId;
    }

    public function getResult()
    {
        return $this->getBuildStatus($this->result);
    }

    /**
     * @param int $buildResult
     *
     * @return BuildResult
     */
    public function setResult($buildResult)
    {
        $this->result = $buildResult;
        return $this;
    }
}
