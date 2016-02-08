<?php

namespace BuildQueue\Services;

use BuildQueue\DataObject\DeployResult;
use Symfony\Component\Console\Output\OutputInterface;

class DeployService
{
    /**
     * @var array
     */
    private $config;

    /**
     * DeployService constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @param string          $repo
     * @param int             $buildId
     * @param OutputInterface $output
     * @return $this
     */
    public function deploy($repo, $buildId, OutputInterface $output)
    {
        $repoMapping = $this->config['repositories'];

        if (!isset($repoMapping[$repo])) {
            $output->writeln("<error>No build key found for repo: $repo</error>");
            return (new DeployResult())->setDeployResult(DeployResult::FAILURE);
        }

        `cd enrich && git pull`;

        $mappedName = $repoMapping[$repo];
        $content = file_get_contents($this->config['deploy']['file']);
        $content = preg_replace('#^('.$mappedName.'\s?=\s?)\d+$#mi', '${1}' . $buildId, $content);
        file_put_contents($this->config['deploy']['file'], $content);

        chdir('enrich');
        $branchName = "autodeploy-for-{$repo}-$buildId-by-{$this->config['api']['username']}";

        if (isset($this->config['deploy']['proMode']) && $this->config['deploy']['proMode'] === true) {
            `git add *`;
            `git commit -m "Autodeploying #{$buildId} in {$repo}"`;
            $outputBuffer = `git push`;
            $output->writeln("<comment>Build #{$buildId}: Deploy in progress</comment>");
        } else {
            `git checkout -b $branchName`;
            `git add *`;
            `git commit -m "Autodeploying #{$buildId} in {$repo}"`;
            `git push -u origin $branchName`;
            `git push`;
            `git checkout master && git branch -D $branchName`;

            $output->writeln("<comment>Create a Pull Request: https://github.com/{$this->config['deploy']['repo']}/compare/$branchName?expand=1</comment>");
            $output->writeln("<info>Please delete the branch once the PR is closed!</info>");
            $output->writeln("<info>Run: git push origin --delete $branchName</info>");
        }
        chdir('../');

        return (new DeployResult())->setDeployResult(DeployResult::SUCCESS);
    }
}
