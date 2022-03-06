<?php

declare(strict_types=1);

namespace Scratch\Commands;

use Mpdf\MpdfException;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class SortContentCommand extends Command
{
    /** @var OutputInterface */
    private $output;

    /** @var Filesystem */
    private $disk;

    /**
     * @throws FileNotFoundException
     * @throws MpdfException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->disk = new Filesystem();

        $currentPath = getcwd();

        collect($this->disk->files($currentPath . '/content'))->each(function ($file, $index) use ($currentPath) {
            $markdown = $this->disk->get(
                $file->getPathname()
            );

            $newName = sprintf(
                '%03d%s',
                (int)$index + 1,
                str_replace(['#', '##', '###'], '', explode("\n", $markdown)[0])
            );

            $this->disk->move(
                $file->getPathName(),
                $currentPath . '/content/' . Str::slug($newName) . '.md'
            );
        });

        return 0;
    }

    protected function configure(): void
    {
        $this
            ->setName('content:sort')
            ->setDescription('Sort the files in the content directory.');
    }
}
