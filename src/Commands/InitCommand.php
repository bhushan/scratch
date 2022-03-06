<?php

declare(strict_types=1);

namespace Scratch\Commands;

use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class InitCommand extends Command
{
    /** @var OutputInterface */
    private $output;

    /** @var Filesystem */
    private $disk;

    /**
     * @throws FileNotFoundException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->disk = new Filesystem();
        $this->output = $output;

        $currentPath = getcwd();

        if ($this->disk->isDirectory($currentPath . '/assets')) {
            $this->output->writeln('');
            $this->output->writeln('<info>Project already initialised!</info>');

            return 0;
        }

        $this->disk->makeDirectory(
            $currentPath . '/assets'
        );

        $this->disk->makeDirectory(
            $currentPath . '/assets/fonts'
        );

        $this->disk->makeDirectory(
            $currentPath . '/content'
        );

        $this->disk->copyDirectory(
            __DIR__ . '/../../stubs/content',
            $currentPath . '/content'
        );

        $this->disk->put(
            $currentPath . '/scratch.php',
            $this->disk->get(__DIR__ . '/../../stubs/scratch.php')
        );

        $this->disk->put(
            $currentPath . '/assets/cover.jpg',
            $this->disk->get(__DIR__ . '/../../stubs/assets/cover.jpg')
        );

        $this->disk->put(
            $currentPath . '/assets/theme-dark.html',
            $this->disk->get(__DIR__ . '/../../stubs/assets/theme-dark.html')
        );

        $this->disk->put(
            $currentPath . '/assets/theme-light.html',
            $this->disk->get(__DIR__ . '/../../stubs/assets/theme-light.html')
        );

        $this->output->writeln('');
        $this->output->writeln('<info>Done!</info>');

        return 0;
    }

    protected function configure(): void
    {
        $this
            ->setName('init')
            ->setDescription('Initialize a new project in the current directory.');
    }
}
