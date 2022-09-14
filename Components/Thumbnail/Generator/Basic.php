<?php declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxWebPOptimizer\Components\Thumbnail\Generator;

use RuntimeException;
use Shopware\Bundle\MediaBundle\Exception\OptimizerNotFoundException;
use Shopware\Bundle\MediaBundle\MediaServiceInterface;
use Shopware\Bundle\MediaBundle\OptimizerServiceInterface;
use Shopware\Bundle\MediaBundle\Strategy\StrategyInterface;
use Shopware\Components\Thumbnail\Generator\GeneratorInterface;
use Shopware_Components_Config;

/**
 * This class is primarily a carbon copy of \Shopware\Components\Thumbnail\Generator\Basic to be able to
 * override the private method optimizeImage. Else Shopware will only provide a temporary path for the
 * image file, although the final image path is known. The information about the final path is crucial
 * to be able to place a WebP variant of a JPEG or PNG image file inside the same folder.
 */
class Basic implements GeneratorInterface
{
    /** @var bool */
    private $fixGdImageBlur;

    /** @var MediaServiceInterface */
    private $mediaService;

    /** @var OptimizerServiceInterface */
    private $optimizerService;

    /** @var array<string, mixed> */
    private array $cdnConfig;

    /** @var StrategyInterface */
    private $strategy;

    public function __construct(
        Shopware_Components_Config $config,
        MediaServiceInterface $mediaService,
        OptimizerServiceInterface $optimizerService,
        StrategyInterface $strategy
    ) {
        $container = \Shopware()->Container();

        $this->fixGdImageBlur = $config->get('thumbnailNoiseFilter');
        $this->mediaService = $mediaService;
        $this->optimizerService = $optimizerService;
        $this->strategy = $strategy;

        $this->cdnConfig = (array) $container->getParameter('shopware.cdn');
    }

    /**
     * {@inheritdoc}
     */
    public function createThumbnail($imagePath, $destination, $maxWidth, $maxHeight, $keepProportions = false, $quality = 90)
    {
        $maxWidth = (int) $maxWidth;
        $maxHeight = (int) $maxHeight;
        $quality = (int) $quality;

        if (!$this->mediaService->has($imagePath)) {
            throw new RuntimeException(\sprintf('File not found: %s', $imagePath));
        }

        $content = $this->mediaService->read($imagePath);
        if (!\is_string($content)) {
            throw new RuntimeException(\sprintf('Could not read image from file: %s', $imagePath));
        }

        $image = $this->createImageResource($content, $imagePath);

        // Determines the width and height of the original image
        $originalSize = $this->getOriginalImageSize($image);

        if (empty($maxHeight)) {
            $maxHeight = $maxWidth;
        }

        $newSize = [
            'width' => $maxWidth,
            'height' => $maxHeight,
        ];

        if ($keepProportions) {
            $newSize = $this->calculateProportionalThumbnailSize($originalSize, $maxWidth, $maxHeight);
        }

        $newImage = $this->createNewImage($image, $originalSize, $newSize, $this->getImageExtension($destination));

        if ($this->fixGdImageBlur) {
            $this->fixGdImageBlur($newSize, $newImage);
        }

        $this->saveImage($destination, $newImage, $quality);
        $this->optimizeImage($destination);

        // Removes both the original and the new created image from memory
        \imagedestroy($newImage);
        \imagedestroy($image);
    }

    /**
     * Determines the extension of the file according to
     * the given path and calls the right creation
     * method for the image extension
     *
     * @return resource
     *
     * @throws RuntimeException
     */
    private function createImageResource(string $fileContent, string $imagePath)
    {
        $image = \imagecreatefromstring($fileContent);
        if (false === $image) {
            throw new RuntimeException(\sprintf('Image is not in a recognized format (%s)', $imagePath));
        }

        return $image;
    }

    /**
     * Returns an array with a width and height index
     * according to the passed sizes
     *
     * @param resource $imageResource
     *
     * @return array{width: int, height: int}
     */
    private function getOriginalImageSize($imageResource): array
    {
        return [
            'width' => (int) \imagesx($imageResource),
            'height' => (int) \imagesy($imageResource),
        ];
    }

    /**
     * Calculate image proportion and set the new resolution
     *
     * @param array{width: int, height: int} $originalSize
     *
     * @return array{width: int, height: int, proportion: float}
     */
    private function calculateProportionalThumbnailSize(array $originalSize, int $width, int $height): array
    {
        // Source image size
        $srcWidth = $originalSize['width'];
        $srcHeight = $originalSize['height'];

        // Calculate the scale factor
        if (0 === $width) {
            $factor = $height / $srcHeight;
        } elseif (0 === $height) {
            $factor = $width / $srcWidth;
        } else {
            $factor = \min($width / $srcWidth, $height / $srcHeight);
        }

        if ($factor >= 1) {
            $dstWidth = $srcWidth;
            $dstHeight = $srcHeight;
            $factor = 1;
        } else {
            //Get the destination size
            $dstWidth = \round($srcWidth * $factor);
            $dstHeight = \round($srcHeight * $factor);
        }

        return [
            'width' => (int) $dstWidth,
            'height' => (int) $dstHeight,
            'proportion' => $factor,
        ];
    }

    /**
     * @param resource                       $image
     * @param array{width: int, height: int} $originalSize
     * @param array{width: int, height: int} $newSize
     *
     * @return resource
     */
    private function createNewImage($image, array $originalSize, array $newSize, string $extension)
    {
        // Creates a new image with given size
        $newImage = \imagecreatetruecolor($newSize['width'], $newSize['height']);
        if (false === $newImage) {
            throw new RuntimeException('Could not create image');
        }

        if (\in_array($extension, ['jpg', 'jpeg'])) {
            $background = (int) \imagecolorallocate($newImage, 255, 255, 255);
            \imagefill($newImage, 0, 0, $background);
        } else {
            // Disables blending
            \imagealphablending($newImage, false);
        }

        // Saves the alpha information
        \imagesavealpha($newImage, true);
        // Copies the original image into the new created image with re-sampling
        \imagecopyresampled(
            $newImage,
            $image,
            0,
            0,
            0,
            0,
            $newSize['width'],
            $newSize['height'],
            $originalSize['width'],
            $originalSize['height']
        );

        return $newImage;
    }

    /**
     * Returns the extension of the file with passed path
     */
    private function getImageExtension(string $path): string
    {
        return \pathinfo($path, PATHINFO_EXTENSION);
    }

    /**
     * Fix #fefefe in white backgrounds
     *
     * @param array{width: int, height: int} $newSize
     * @param resource                       $newImage
     */
    private function fixGdImageBlur(array $newSize, $newImage): void
    {
        $colorWhite = (int) \imagecolorallocate($newImage, 255, 255, 255);
        $processHeight = $newSize['height'] + 0;
        $processWidth = $newSize['width'] + 0;
        for ($y = 0; $y < $processHeight; ++$y) {
            for ($x = 0; $x < $processWidth; ++$x) {
                $colorat = \imagecolorat($newImage, $x, $y);
                $r = ($colorat >> 16) & 0xFF;
                $g = ($colorat >> 8) & 0xFF;
                $b = $colorat & 0xFF;
                if ((253 == $r && 253 == $g && 253 == $b) || (254 == $r && 254 == $g && 254 == $b)) {
                    \imagesetpixel($newImage, $x, $y, $colorWhite);
                }
            }
        }
    }

    /**
     * @param resource $newImage
     * @param int      $quality  - JPEG quality
     */
    private function saveImage(string $destination, $newImage, int $quality): void
    {
        \ob_start();
        // saves the image information into a specific file extension
        switch (\strtolower($this->getImageExtension($destination))) {
            case 'png':
                \imagepng($newImage);
                break;
            case 'gif':
                \imagegif($newImage);
                break;
            default:
                \imagejpeg($newImage, null, $quality);
                break;
        }

        $content = \ob_get_contents();
        if (!\is_string($content)) {
            throw new RuntimeException('Could not open image');
        }

        \ob_end_clean();

        $this->mediaService->write($destination, $content);
    }

    private function optimizeImage(string $destination): void
    {
        if ('local' !== $this->cdnConfig['backend']) {
            throw new RuntimeException('nlxWebpOptimizer is only compatible with the local filesystem backend!');
        }

        if (!$this->strategy->isEncoded($destination)) {
            $destination = $this->strategy->encode($destination);
        }

        try {
            $this->optimizerService->optimize(\realpath($this->cdnConfig['adapters']['local']['path'] . $destination));
        } catch (OptimizerNotFoundException $exception) {
            // empty catch intended since no optimizer is available
        }
    }
}
