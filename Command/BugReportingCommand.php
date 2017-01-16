<?php
namespace Xrow\BugReportingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class BugReportingCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('bugreporting:create')
            ->setDescription('Generate a BugReporting ZIP file')
            ->setDefinition(
                new InputDefinition(array(
                    new InputOption(
                        'dest',
                        'd',
                        InputOption::VALUE_REQUIRED,
                        'New ZIP file destination',
                        './'
                        )
                ))
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $createIn = $input->getOption('dest');

        if (!$createIn) {
            $text = 'ZIP file created in default directory';
        } else {
            $text = 'ZIP file created in: '.$createIn;
        }

        $utils = $this->getUtilsContainer()->runCommand($createIn);

        $output->writeln($text);
    }

    /**
     * @return UserRepositoryInterface
     */
    protected function getUtilsContainer()
    {
        return $this->getContainer()->get('xrow.bug_reporting_utils');
    }
}