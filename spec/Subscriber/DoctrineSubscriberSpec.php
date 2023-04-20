<?php
declare(strict_types=1);

/*
 * Created by netlogix GmbH & Co. KG
 *
 * @copyright netlogix GmbH & Co. KG
 */

namespace spec\nlxWebPOptimizer\Subscriber;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use nlxWebPOptimizer\Subscriber\DoctrineSubscriber;
use org\bovigo\vfs\vfsStream;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Shopware\Bundle\MediaBundle\MediaServiceInterface;
use Shopware\Models\Article\Article;
use Shopware\Models\Media\Media;

class DoctrineSubscriberSpec extends ObjectBehavior
{
    public function let(
        MediaServiceInterface $mediaService
    ): void {
        $this->beConstructedWith($mediaService);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(DoctrineSubscriber::class);
    }

    public function it_should_return_subscribed_events(): void
    {
        $this->getSubscribedEvents()->shouldReturn([
            Events::postRemove
        ]);
    }

    public function it_should_remove_webp_files(
        LifecycleEventArgs $args,
        Media $entity,
        MediaServiceInterface $mediaService
    ): void {
        $structure = [
            'media' => [
                'image' => [
                    'ab' => [
                        'cd' => [
                            '123456789.jpg' => 'This is an image! I am sure!',
                            '123456789.jpg.webp' => 'This is an image! I am sure!',
                        ],
                    ],
                    'ef' => [
                        'gh' => [
                            '123456789-thumbnail.jpg' => 'This is an image! I am sure!',
                            '123456789-thumbnail.jpg.webp' => 'This is an image! I am sure!',
                        ],
                    ],
                ],
            ],
        ];
        $root = vfsStream::setup('root', null, $structure);

        $args->getEntity()
            ->willReturn($entity);

        $entity->getType()
            ->willReturn(Media::TYPE_IMAGE);

        $entity->getPath()
            ->willReturn('path/to/123456789.jpg');

        $mediaService->encode('path/to/123456789.jpg')
            ->willReturn($root->url() . '/media/image/ab/cd/123456789.jpg');

        $mediaService->encode('path/to/123456789-thumbnail.jpg')
            ->willReturn($root->url() . '/media/image/ef/gh/123456789-thumbnail.jpg');

        $entity->getThumbnailFilePaths()
            ->willReturn(['path/to/123456789-thumbnail.jpg']);

        \expect(\file_exists($root->url() . '/media/image/ab/cd/123456789.jpg.webp'))->toBe(true);
        \expect(\file_exists($root->url() . '/media/image/ef/gh/123456789-thumbnail.jpg'))->toBe(true);

        $this->postRemove($args);

        \expect(\file_exists($root->url() . '/media/image/ab/cd/123456789.jpg.webp'))->toBe(false);
        \expect(\file_exists($root->url() . '/media/image/ef/gh/123456789-thumbnail.jpg.webp'))->toBe(false);
    }

    public function it_should_do_nothing_if_entity_is_not_media(
        LifecycleEventArgs $args,
        Article $article,
        MediaServiceInterface $mediaService
    ): void {
        $args->getEntity()
            ->willReturn($article);

        $mediaService->encode(Argument::any())
            ->shouldNotBeCalled();

        $this->postRemove($args);
    }

    public function it_should_do_nothing_if_media_is_not_image(
        LifecycleEventArgs $args,
        Media $entity,
        MediaServiceInterface $mediaService
    ): void {
        $args->getEntity()
            ->willReturn($entity);

        $entity->getType()
            ->willReturn(Media::TYPE_VECTOR);

        $mediaService->encode(Argument::any())
            ->shouldNotBeCalled();

        $this->postRemove($args);
    }
}
