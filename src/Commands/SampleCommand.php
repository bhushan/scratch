<?php

declare(strict_types=1);

namespace Scratch\Commands;

use Mpdf\Mpdf;
use Scratch\Scratch;
use Mpdf\MpdfException;
use Symfony\Component\Console\Command\Command;
use setasign\Fpdi\PdfParser\PdfParserException;
use setasign\Fpdi\PdfParser\Type\PdfTypeException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use setasign\Fpdi\PdfParser\CrossReference\CrossReferenceException;

class SampleCommand extends Command
{
    /** @var OutputInterface */
    private $output;

    /**
     * @throws MpdfException
     * @throws CrossReferenceException
     * @throws PdfParserException
     * @throws PdfTypeException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $currentPath = getcwd();

        $config = require $currentPath . '/scratch.php';

        $mpdf = new Mpdf();

        $fileName = Scratch::outputFileName() . '-' . $input->getArgument('theme');

        $mpdf->setSourceFile($currentPath . '/export/' . $fileName . '.pdf');

        foreach ($config['sample'] as $range) {
            foreach (range($range[0], $range[1]) as $page) {
                $mpdf->useTemplate(
                    $mpdf->importPage($page)
                );
                $mpdf->AddPage();
            }
        }

        $mpdf->WriteHTML('<p style="text-align: center; font-size: 16px; line-height: 40px;">' . $config['sample_notice'] . '</p>');

        $mpdf->Output(
            $currentPath . '/export/sample-.' . $fileName . '.pdf'
        );

        return 0;
    }

    protected function configure(): void
    {
        $this
            ->setName('sample')
            ->addArgument('theme', InputArgument::OPTIONAL, 'The name of the theme', 'light')
            ->setDescription('Generate a sample.');
    }
}
