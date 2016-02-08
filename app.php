<?php

require 'vendor/autoload.php';

use BuildQueue\Command\BuildCommand;
use BuildQueue\Command\DeployCommand;
use BuildQueue\Services\DeployService;
use BuildQueue\Services\Jenkins;
use Symfony\Component\Console\Application;
use Guzzle\Http\Client as GuzzleClient;
use Symfony\Component\Yaml\Yaml;

$config = Yaml::parse(file_get_contents('src/Resources/config.yml'));

$console = new Application();

$jenkins = new Jenkins(
    new GuzzleClient(),
    $config['api']['build_url'],
    $config['api']['username'],
    $config['api']['passkey']
);

$deployService = new DeployService($config);

$deployCommand = new DeployCommand();
$deployCommand->setJenkins($jenkins)
    ->setDeployService($deployService)
    ->setConfig($config);

$buildCommand = new BuildCommand();
$buildCommand->setJenkins($jenkins)
    ->setDeployService($deployService)
    ->setConfig($config);

$console->add($buildCommand);
$console->add($deployCommand);

$console->run();