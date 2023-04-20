<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace nlxWebPOptimizer\Subscriber;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Shopware\Bundle\MediaBundle\MediaServiceInterface;
use Shopware\Models\Media\Media;

class DoctrineSubscriber implements EventSubscriber
{
    private MediaServiceInterface $mediaService;

    public function __construct(MediaServiceInterface $mediaService)
    {
        $this->mediaService = $mediaService;
    }

    /**
     * {@inheritdoc}
     */
    public function getSubscribedEvents(): array
    {
        return [
            Events::postRemove
        ];
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if (false === $entity instanceof Media || Media::TYPE_IMAGE !== $entity->getType()) {
            return;
        }

        $this->deleteImage($entity->getPath());

        $thumbnailFilePaths = $entity->getThumbnailFilePaths();

        foreach ($thumbnailFilePaths as $thumbnailFilePath) {
            $this->deleteImage($thumbnailFilePath);
        }
    }

    private function deleteImage(string $path): void
    {
        $encodedPath = $this->mediaService->encode($path);
        $mediaWebpPath = $encodedPath . '.webp';

        if (\file_exists($mediaWebpPath)) {
            \unlink($mediaWebpPath);
        }
    }
}
