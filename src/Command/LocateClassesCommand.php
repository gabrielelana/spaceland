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
        $io = new SymfonyStyle($input, $output);

        $rootDirectory = $input->getArgument('root');
        $rootDirectory = is_array($rootDirectory) ? $rootDirectory[0] : $rootDirectory;
        $rootDirectory = $this->locateRootDirectory($rootDirectory);
        if (!$rootDirectory || !is_dir($rootDirectory)) {
            $io->error(sprintf('%s is not a root of a project', $rootDirectory));
            exit(1);
        }

        $finder = new Finder();
        $finder->files()->name('*.php')->in($rootDirectory);
        foreach ($finder as $file) {
            if ($filePath = $file->getRealPath()) {
                $output->writeln($filePath);
            }
        }
    }

    /**
     * Locate the root directory of the project
     *
     * @return string | null
     */
    private function locateRootDirectory(?string $startingFrom = null)
    {
        $startingFrom = $startingFrom ?? getenv('PWD') ?: '.';
        if (!$startingFrom) {
            return null;
        }
        if (is_file($startingFrom . '/composer.json')) {
            if ($startingFrom = realpath($startingFrom)) {
                return $startingFrom;
            }
            return null;
        }
        return $this->locateRootDirectory(dirname($startingFrom));
    }
}
