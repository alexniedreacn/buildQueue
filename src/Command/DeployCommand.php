<?php

namespace BuildQueue\Command;

use BuildQueue\Services\DeployService;
use BuildQueue\Services\Jenkins;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class DeployCommand extends Command
{

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
     * @param mixed $jenkins
     *
     * @return DeployCommand
     */
    public function setJenkins($jenkins)
    {
        $this->jenkins = $jenkins;
        return $this;
    }

    /**
     * @param DeployService $deployService
     *
     * @return DeployCommand
     */
    public function setDeployService($deployService)
    {
        $this->deployService = $deployService;
        return $this;
    }

    /**
     * @param mixed $config
     *
     * @return DeployCommand
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
        $this->setName('deploy')
            ->addArgument('repo', InputArgument::OPTIONAL, 'Repository name')
            ->addArgument('id', InputArgument::OPTIONAL, 'Build Id')
            ->setDescription('Deploy only a specific build');
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

        $buildId = $input->getArgument('id');
        if (!$buildId) {
            while (!$buildId = $this->getBuildId($input, $output));
        }

        $this->deployService->deploy($repo, $buildId, $output);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    private function getRepoName(InputInterface $input, OutputInterface $output)
    {
        $question = new Question('<question>Please enter the repository name to deploy:</question> ');
        $repos = array_keys($this->config['repositories']);
        $question->setAutocompleterValues($repos);

        $helper = new QuestionHelper();
        return $helper->ask($input, $output, $question);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return string
     */
    private function getBuildId(InputInterface $input, OutputInterface $output)
    {
        $question = new Question('<question>Please provide the build number:</question> ');
        $helper = new QuestionHelper();
        return $helper->ask($input, $output, $question);
    }
}
