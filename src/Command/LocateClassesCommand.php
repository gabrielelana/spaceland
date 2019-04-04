<?php

namespace Spaceland\Command;

use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PhpParser\Error;
use Spaceland\NodeVisitor\ClassCatcher;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
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
            ->addOption(
                'cache-file',
                null,
                InputOption::VALUE_REQUIRED,
                'File where to cache previous results',
                null
            )
            ->addArgument(
                'root',
                InputArgument::OPTIONAL,
                'Root directory of the project'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $rootDirectory = $this->rootDirectory($input, $output);
        $cacheFile = $this->cacheFile($input, $output, $rootDirectory);
        $cache = $this->loadCache($cacheFile);

        $finder = new Finder();
        $finder->files()->name('*.php')->in($rootDirectory);
        foreach ($finder as $file) {
            $fileRelativePath = $file->getRelativePath() . '/' . $file->getFilename();
            $fileAbsolutePath = $file->getRealPath();
            if ($fileAbsolutePath) {
                $classesFound = [];
                $fileLastModifiedAt = lstat($fileAbsolutePath)['mtime'];
                if (isset($cache[$fileRelativePath]) && ($fileLastModifiedAt <= $cache[$fileRelativePath][0])) {
                    $classesFound = $cache[$fileRelativePath][1];
                } else {
                    $classesFound = $this->locateClassesIn($fileAbsolutePath);
                    $cache[$fileRelativePath] = [$fileLastModifiedAt, $classesFound];
                }
                foreach ($classesFound as $classFound) {
                    $output->writeln($classFound);
                }
            }
        }
        $this->storeCache($cacheFile, $cache);
    }

    private function locateClassesIn(string $filePath): array
    {
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
        $fileContent = file_get_contents($filePath);
        if (!$fileContent) {
            return [];
        }
        try {
            $ast = $parser->parse($fileContent);
            if (!$ast) {
                return [];
            }
            $nameResolver = new NameResolver;
            $classCatcher = new ClassCatcher();
            $nodeTraverser = new NodeTraverser;
            $nodeTraverser->addVisitor($nameResolver);
            $nodeTraverser->addVisitor($classCatcher);
            $nodeTraverser->traverse($ast);
            return $classCatcher->definedClasses();
        } catch(Error $e) {
            return [];
        }
    }

    private function loadCache(string $cacheFile)
    {
        $cacheContent = file_get_contents($cacheFile);
        if (!$cacheContent) {
            return [];
        }
        $cache = [];
        foreach (explode(PHP_EOL, $cacheContent) as $line) {
            [$file, $time, $classes] = explode(':', $line);
            if (!empty($classes)) {
                $classes = explode(',', $classes);
            }
            $cache[$file] = [$time, $classes];
        }
        return $cache;
    }

    private function storeCache(string $cacheFile, array $cache)
    {
        $cacheLines = [];
        foreach ($cache as $file => $cached) {
            $cacheLines[] = implode(':', [$file, $cached[0], implode(',', $cached[1])]);
        }
        file_put_contents($cacheFile, implode(PHP_EOL, $cacheLines));
    }

    private function cacheFile(InputInterface $input, OutputInterface $output, string $rootDirectory)
    {
        $io = new SymfonyStyle($input, $output);

        $cacheFile = $input->getOption('cache-file');
        $cacheFile = is_array($cacheFile) ? $cacheFile[0] : $cacheFile;
        if (!$cacheFile || is_bool($cacheFile)) {
            $io->error('Missing cache file argument from command line');
            exit(1);
        }
        if (file_exists($cacheFile) && is_file($cacheFile) && is_writable($cacheFile)) {
            return $cacheFile;
        }
        if (!file_exists($cacheFile) && is_writable(dirname($cacheFile))) {
            return $cacheFile;
        }
        $io->error(sprintf('Unable to create cache file %s', $cacheFile));
        exit(1);
    }

    /**
     * Returns the project root directory
     *
     * @return string
     */
    private function rootDirectory(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $rootDirectory = $input->getArgument('root');
        $rootDirectory = is_array($rootDirectory) ? $rootDirectory[0] : $rootDirectory;
        $rootDirectory = $this->locateRootDirectory($rootDirectory);
        if (!$rootDirectory || !is_dir($rootDirectory)) {
            $io->error(sprintf('%s is not a root of a project', $rootDirectory));
            exit(1);
        }
        return $rootDirectory;
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
