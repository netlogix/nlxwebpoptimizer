<?php declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxWebPOptimizer\Services;

use nlxWebPOptimizer\Optimizer\WebpOptimizer;
use Shopware\Bundle\MediaBundle\OptimizerServiceInterface;

class OptimizerService implements OptimizerServiceInterface
{
    /** @var OptimizerServiceInterface */
    private $optimizerService;

    /** @var WebpOptimizer */
    private $webpOptimizer;

    public function __construct(OptimizerServiceInterface $optimizerService, WebpOptimizer $webpOptimizer)
    {
        $this->optimizerService = $optimizerService;
        $this->webpOptimizer = $webpOptimizer;
    }

    /**
     * {@inheritdoc}
     */
    public function optimize($filepath)
    {
        $this->optimizerService->optimize($filepath);

        $mime = $this->getMimeTypeByFile($filepath);

        if (\in_array($mime, $this->webpOptimizer->getSupportedMimeTypes())) {
            $perms = \fileperms($filepath);
            $this->webpOptimizer->run($filepath);
            \chmod($this->webpOptimizer->getOutputFilePath($filepath), $perms);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getOptimizers()
    {
        return $this->optimizerService->getOptimizers();
    }

    /**
     * {@inheritdoc}
     */
    public function getOptimizerByMimeType($mime)
    {
        return $this->optimizerService->getOptimizerByMimeType($mime);
    }

    private function getMimeTypeByFile(string $filepath): string
    {
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($filepath);
    }
}
