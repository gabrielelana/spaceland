<?php

namespace Spaceland\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class LocateClassesCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('locate:classes')
            ->setDescription('Locate all classes in current project')
            ->addArgument(
                'root',
                InputArgument::OPTIONAL,
                'Root directory of the project'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDirectory = $input->getArgument('root');
        $output->writeln('Hello');
    }
}
