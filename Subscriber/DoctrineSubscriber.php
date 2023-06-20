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
use Shopware\Bundle\MediaBundle\Strategy\StrategyInterface;
use Shopware\Models\Media\Media;

class DoctrineSubscriber implements EventSubscriber
{
    private StrategyInterface $strategy;

    public function __construct(StrategyInterface $strategy)
    {
        $this->strategy = $strategy;
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
        $encodedPath = $this->strategy->encode($path);
        $mediaWebpPath = $encodedPath . '.webp';

        if (\file_exists($mediaWebpPath)) {
            \unlink($mediaWebpPath);
        }
    }
}
