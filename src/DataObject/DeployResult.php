<?php
/**
 * Created by PhpStorm.
 * User: aleksandrs.niedre
 * Date: 29/01/2016
 * Time: 20:30
 */

namespace BuildQueue\DataObject;


class DeployResult
{
    private $deployResult;

    const SUCCESS = 1;
    const FAILURE = 0;

    /**
     * @return mixed
     */
    public function getDeployResult()
    {
        return $this->deployResult;
    }

    public function setDeployResult($result)
    {
        $this->deployResult = $result;
        return $this;
    }
}
