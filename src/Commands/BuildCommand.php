<?php

declare(strict_types=1);

namespace Scratch\Commands;

use Mpdf\Mpdf;
use SplFileInfo;
use Scratch\Scratch;
use Mpdf\MpdfException;
use Mpdf\Config\FontVariables;
use Mpdf\Config\ConfigVariables;
use League\CommonMark\Environment;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use League\CommonMark\Block\Element\FencedCode;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Spatie\CommonMarkHighlighter\FencedCodeRenderer;
use League\CommonMark\Extension\Table\TableExtension;
use Symfony\Component\Console\Output\OutputInterface;
use League\CommonMark\GithubFlavoredMarkdownConverter;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class BuildCommand extends Command
{
    /** @var string|string[]|null */
    public $themeName;

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
        $this->output = $output;
        $this->themeName = $input->getArgument('theme');

        $currentPath = getcwd();
        $config = require $currentPath . '/Scratch.php';

        $this->ensureExportDirectoryExists(
            $currentPath = getcwd()
        );

        $theme = $this->getTheme($currentPath, $this->themeName);

        $this->buildPdf(
            $this->buildHtml($currentPath . '/content', $config),
            $config,
            $currentPath,
            $theme
        );

        $this->output->writeln('');
        $this->output->writeln('<info>Book Built Successfully!</info>');

        return 0;
    }

    protected function ensureExportDirectoryExists(string $currentPath): void
    {
        $this->output->writeln('<fg=yellow>==></> Preparing Export Directory ...');

        if (! $this->disk->isDirectory($currentPath . '/export')) {
            $this->disk->makeDirectory(
                $currentPath . '/export',
                0755,
                true
            );
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function getTheme(string $currentPath, string $themeName): string
    {
        return $this->disk->get($currentPath . "/assets/theme-$themeName.html");
    }

    /**
     * @throws FileNotFoundException
     * @throws MpdfException
     */
    protected function buildPdf(string $html, array $config, string $currentPath, string $theme): void
    {
        $defaultConfig = (new ConfigVariables())->getDefaults();
        $fontDirs = $defaultConfig['fontDir'];

        $defaultFontConfig = (new FontVariables())->getDefaults();
        $fontData = $defaultFontConfig['fontdata'];

        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => $config['document']['format'] ?? [210, 297],
            'margin_left' => $config['document']['margin_left'] ?? 27,
            'margin_right' => $config['document']['margin_right'] ?? 27,
            'margin_bottom' => $config['document']['margin_bottom'] ?? 14,
            'margin_top' => $config['document']['margin_top'] ?? 14,
            'fontDir' => array_merge($fontDirs, [getcwd() . '/assets/fonts']),
            'fontdata' => $this->fonts($config, $fontData),
        ]);

        $pdf->SetTitle(Scratch::title());
        $pdf->SetAuthor(Scratch::author());
        $pdf->SetCreator(Scratch::author());

        $pdf->setAutoTopMargin = 'pad';

        $pdf->setAutoBottomMargin = 'pad';

        $tocLevels = $config['toc_levels'];

        $pdf->h2toc = $tocLevels;
        $pdf->h2bookmarks = $tocLevels;

        $pdf->SetMargins(400, 100, 12);

        if ($this->disk->isFile($currentPath . '/assets/cover.jpg')) {
            $this->output->writeln('<fg=yellow>==></> Adding Book Cover ...');

            $coverPosition = $config['cover']['position'] ?? 'position: absolute; left:0; right: 0; top: -.2; bottom: 0;';
            $coverDimensions = $config['cover']['dimensions'] ?? 'width: 210mm; height: 297mm; margin: 0;';

            $pdf->WriteHTML(
                <<<HTML
<div style="{$coverPosition}">
    <img src="assets/cover.jpg" style="{$coverDimensions}"/>
</div>
HTML
            );

            $pdf->AddPage();
        } elseif ($this->disk->isFile($currentPath . '/assets/cover.html')) {
            $this->output->writeln('<fg=yellow>==></> Adding Book Cover ...');

            $cover = $this->disk->get($currentPath . '/assets/cover.html');

            $pdf->WriteHTML($cover);

            $pdf->AddPage();
        } else {
            $this->output->writeln('<fg=red>==></> No assets/cover.jpg File Found. Skipping ...');
        }

        $pdf->SetHTMLFooter('<div id="footer" style="text-align: center">{PAGENO}</div>');

        $this->output->writeln('<fg=yellow>==></> Building PDF ...');

        $pdf->WriteHTML(
            $theme . $html
        );

        $this->output->writeln('<fg=yellow>==></> Writing PDF To Disk ...');

        $this->output->writeln('');
        $this->output->writeln('✨✨ ' . $pdf->page . ' PDF pages ✨✨');

        $pdf->Output(
            $currentPath . '/export/' . Scratch::outputFileName() . '-' . $this->themeName . '.pdf'
        );
    }

    protected function fonts(array $config, array $fontData): array
    {
        return $fontData + collect($config['fonts'])->mapWithKeys(function ($file, $name) {
            return [
                    $name => [
                        'R' => $file,
                    ],
                ];
        })->toArray();
    }

    protected function buildHtml(string $path, array $config): string
    {
        $this->output->writeln('<fg=yellow>==></> Parsing Markdown ...');

        $environment = Environment::createCommonMarkEnvironment();
        $environment->addExtension(new TableExtension());

        $environment->addBlockRenderer(FencedCode::class, new FencedCodeRenderer([
            'html', 'php', 'js', 'bash', 'json',
        ]));

        if (is_callable($config['configure_commonmark'])) {
            call_user_func($config['configure_commonmark'], $environment);
        }

        $converter = new GithubFlavoredMarkdownConverter([], $environment);

        return collect($this->disk->files($path))
            ->map(function (SplFileInfo $file, $i) use ($converter) {
                if ($file->getExtension() !== 'md') {
                    return '';
                }

                $markdown = $this->disk->get(
                    $file->getPathname()
                );

                return $this->prepareForPdf(
                    $converter->convertToHtml($markdown),
                    $i + 1
                );
            })
            ->implode(' ');
    }

    /**
     * @return string|string[]
     */
    private function prepareForPdf(string $html, $file)
    {
        $commands = [
            '[break]' => '<div style="page-break-after: always;"></div>',
        ];

        if ($file > 1) {
            $html = str_replace('<h1>', '[break]<h1>', $html);
        }

        $html = str_replace('<h2>', '[break]<h2>', $html);
        $html = str_replace("<blockquote>\n<p>{notice}", "<blockquote class='notice'><p><strong>Notice:</strong>", $html);
        $html = str_replace("<blockquote>\n<p>{warning}", "<blockquote class='warning'><p><strong>Warning:</strong>", $html);
        $html = str_replace("<blockquote>\n<p>{quote}", "<blockquote class='quote'><p>", $html);

        $html = str_replace(array_keys($commands), array_values($commands), $html);

        return $html;
    }

    protected function configure(): void
    {
        $this
            ->setName('build')
            ->addArgument('theme', InputArgument::OPTIONAL, 'The name of the theme', 'light')
            ->setDescription('Generate the book.');
    }
}
