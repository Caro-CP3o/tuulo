<?php
namespace App\Events;

use App\Entity\MediaObject;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;

class MediaObjectDeletionSubscriber implements EventSubscriber
{
    /**
     * Summary of getSubscribedEvents
     * @return string[]
     */
    public function getSubscribedEvents(): array
    {
        return [Events::preRemove];
    }

    /**
     * Deletes the physical media file from disk when a MediaObject entity is removed.
     *
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     * @return void
     */
    public function preRemove(LifecycleEventArgs $args): void
    {
        // ---------------------------
        // Retrieve the entity being removed
        // ---------------------------
        $entity = $args->getEntity();
        // ---------------------------
        // Only handle MediaObject entities; ignore all others
        // ---------------------------
        if (!$entity instanceof MediaObject) {
            return;
        }
        // ---------------------------
        // Build the absolute file path of the media file
        // ---------------------------
        $filePath = __DIR__ . '/../../public/media/' . $entity->getFilePath();
        // ---------------------------
        // Check if the file exists and attempt to delete it
        // The @ suppresses warnings if the file cannot be deleted
        // ---------------------------
        if (file_exists($filePath)) {
            @unlink($filePath);
        }
    }
}
