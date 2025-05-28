<?php
namespace App\Events;

use App\Entity\MediaObject;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class MediaObjectDeletionSubscriber implements EventSubscriber
{
    public function getSubscribedEvents(): array
    {
        return [Events::preRemove];
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getEntity();

        if (!$entity instanceof MediaObject) {
            return;
        }

        $filePath = __DIR__ . '/../../public/media/' . $entity->getFilePath();

        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
