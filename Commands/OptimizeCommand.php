<?php declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxWebPOptimizer\Commands;

use Shopware\Bundle\MediaBundle\Exception\OptimizerNotFoundException;
use Shopware\Bundle\MediaBundle\OptimizerServiceInterface;
use Shopware\Commands\ShopwareCommand;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OptimizeCommand extends ShopwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('nlx:webpoptimizer:optimize')
            ->setHelp('The <info>%command.name%</info> optimizes your uploaded images by generating versions in the WebP format.')
            ->setDescription('Optimize uploaded images by generating versions in the WebP format.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $optimizerService = $this->getContainer()->get('shopware_media.cdn_optimizer_service');

        if (false === $this->hasRunnableOptimizer()) {
            $output->writeln('<error>No runnable optimizer found.</error>');
            return 1;
        }

        $progress = new ProgressBar($output, 0);

        $this->optimizeFiles('media', $optimizerService, $progress, $output);

        $progress->finish();

        return 0;
    }

    private function hasRunnableOptimizer(): bool
    {
        $optimizerService = $this->getContainer()
            ->get(\Shopware\Bundle\MediaBundle\OptimizerService::class);

        foreach ($optimizerService->getOptimizers() as $optimizer) {
            if ($optimizer->isRunnable()) {
                return true;
            }
        }

        return false;
    }

    private function optimizeFiles(
        string $directory,
        OptimizerServiceInterface $optimizerService,
        ProgressBar $progressBar,
        OutputInterface $output
    ): void {
        foreach (new \DirectoryIterator($directory) as $item) {
            if ($item->isDot()) {
                continue;
            }

            if ($item->isDir()) {
                $this->optimizeFiles($item->getRealPath(), $optimizerService, $progressBar, $output);
                continue;
            }

            if ($item->isFile()) {
                $progressBar->setMessage($item->getRealPath(), 'filename');

                if (OutputInterface::VERBOSITY_VERBOSE === $output->getVerbosity()) {
                    $output->writeln(' - ' . $item->getRealPath());
                }

                try {
                    $optimizerService->optimize($item->getRealPath());
                } catch (OptimizerNotFoundException $exception) {
                    // Empty catch intended since no optimizer is available
                }

                $progressBar->advance();
            }
        }
    }
}
