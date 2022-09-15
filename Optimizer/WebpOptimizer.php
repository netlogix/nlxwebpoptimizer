<?php declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxWebPOptimizer\Optimizer;

use Shopware\Bundle\MediaBundle\Optimizer\BinaryOptimizer;

class WebpOptimizer extends BinaryOptimizer
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'nlx_webp_optimizer';
    }

    /**
     * {@inheritdoc}
     */
    public function getCommand()
    {
        return 'cwebp';
    }

    /**
     * {@inheritdoc}
     */
    public function getSupportedMimeTypes()
    {
        return ['image/jpeg', 'image/png'];
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandArguments($filepath)
    {
        return ['-q', '85', $filepath, '-o', $this->getOutputFilePath($filepath)];
    }

    public function getOutputFilePath(string $filepath): string
    {
        return \realpath($filepath) . '.webp';
    }
}
