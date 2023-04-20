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

    public function getSubscribedEvents()
    {
        return [
            Events::postRemove
        ];
    }

    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();

        if (false === $entity instanceof Media || $entity->getType() !== Media::TYPE_IMAGE) {
            return;
        }
        $encodedPath = $this->mediaService->encode($entity->getPath());
        $mediaWebpPath = $encodedPath . '.webp';

        if (file_exists($mediaWebpPath)) {
            unlink($mediaWebpPath);
        }
        $thumbnailFilePaths = $entity->getThumbnailFilePaths();

        foreach ($thumbnailFilePaths as $thumbnailFilePath) {
            $encodedThumbnailPath = $this->mediaService->encode($thumbnailFilePath);
            $thumbnailWebpPath = $encodedThumbnailPath . '.webp';

            if (file_exists($thumbnailWebpPath)) {
                unlink($thumbnailWebpPath);
            }
        }
    }
}
