<?php

namespace BuildQueue\Command;

use BuildQueue\DataObject\BuildResult;
use BuildQueue\DataObject\DeployResult;
use BuildQueue\Services\DeployService;
use BuildQueue\Services\Jenkins;
use Guzzle\Http\Message\Header;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use BuildQueue\DataObject\BuildCache;

class BuildCommand extends Command
{
    const TEST_DEPLOY_FILE = './enrich/environment_definition/test1/environment_definition.def';

    /**
     * @var Jenkins
     */
    private $jenkins;

    /**
     * @var DeployService
     */
    private $deployService;

    /**
     * @var array
     */
    private $config;

    /**
     * @param Jenkins $jenkins
     *
     * @return BuildCommand
     */
    public function setJenkins(Jenkins $jenkins)
    {
        $this->jenkins = $jenkins;
        return $this;
    }

    /**
     * @param DeployService $deployService
     *
     * @return BuildCommand
     */
    public function setDeployService(DeployService $deployService)
    {
        $this->deployService = $deployService;
        return $this;
    }

    /**
     * @param array $config
     *
     * @return BuildCommand
     */
    public function setConfig($config)
    {
        $this->config = $config;
        return $this;
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName('build')
            ->addArgument('repo', InputArgument::REQUIRED, 'Repository name')
            ->addArgument('branch', InputArgument::OPTIONAL, 'Branch to build')
            ->setDescription('Set the name of the repo to build');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $repo = $input->getArgument('repo');
        if (!$repo) {
            while (!$repo = $this->getRepoName($input, $output));
        }

        $branch = $input->getArgument('branch');
        if (!$branch) {
            while (!$branch = $this->getBuildType($input, $output));
        }

        $this->firstRun($output);

        $buildResult = $this->jenkins->buildRepo($repo, $branch);

        $deployResult = (new DeployResult());
        $buildSuccess = $buildResult->getResult() == BuildResult::SUCCESS;

        if ($buildSuccess) {
            $deployResult = $this->deployService->deploy($repo, $buildResult->getBuildId(), $output);
        } else {
            $output->writeln("<error>Build failed. Skipping deployment.</error>");
        }

        if (!$buildSuccess) {
            BuildCache::clear($repo, $branch);
        }

        $deploySuccess = $deployResult->getDeployResult() == DeployResult::SUCCESS;
        if ($buildSuccess && $deploySuccess) {
            BuildCache::clear($repo, $branch);
            return 1;
        }

            return 0;
        }

    private function firstRun(OutputInterface $output)
    {
        if (!is_dir('./enrich')) {
            $output->writeln("<info>First launch detected. Cloning the deployment repo.</info>");
            $repo = $this->config['deploy']['repo'];
            `git clone git@github.com:$repo.git ./enrich --progress 2>&1`;
            $output->writeln("<info>Done</info>");
        }
    }

    private function getBuildType($input, $output)
    {
        $question = new Question('<question>Please enter the build type:</question> ');
        $question->setAutocompleterValues(['test', 'master', 'integration']);

        $helper = new QuestionHelper();
        return $helper->ask($input, $output, $question);
    }

    private function getRepoName($input, $output)
    {
        $question = new Question('<question>Please provide the repo name:</question> ');
        $helper = new QuestionHelper();
        return $helper->ask($input, $output, $question);
    }
}
