<?php

require 'vendor/autoload.php';

use BuildQueue\Command\BuildCommand;
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

$buildCommand = new BuildCommand();
$buildCommand->setJenkins($jenkins)
    ->setConfig($config);

$console->add($buildCommand);
$console->run();