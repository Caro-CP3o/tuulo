<?php

namespace App\Events;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\Post;
use App\Repository\FamilyMemberRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PostAuthorSubscriber implements EventSubscriberInterface
{
    /**
     * Summary of __construct
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @param \App\Repository\FamilyMemberRepository $familyMemberRepo
     */
    public function __construct(
        private Security $security,
        private FamilyMemberRepository $familyMemberRepo
    ) {
    }

    /**
     * Summary of getSubscribedEvents
     * @return array<int|string>[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['setAuthorAndFamily', EventPriorities::PRE_VALIDATE],
        ];
    }

    /**
     * Automatically set the author and family of a Post entity
     * before it is validated and persisted.
     *
     * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
     * @return void
     */
    public function setAuthorAndFamily(ViewEvent $event): void
    {
        // ---------------------------
        // Get the entity returned by the controller (should be a Post)
        // ---------------------------
        $post = $event->getControllerResult();
        // ---------------------------
        // Get the HTTP method of the current request
        // ---------------------------
        $method = $event->getRequest()->getMethod();

        // ---------------------------
        // Only proceed if the entity is a Post and the method is POST or PATCH
        // ---------------------------
        if (!$post instanceof Post || !in_array($method, ['POST', 'PATCH'], true)) {
            return;
        }
        // ---------------------------
        // Get the currently authenticated user
        // ---------------------------
        $user = $this->security->getUser();
        // ---------------------------
        // If a user is authenticated, set them as the post author
        // and also link the post to the user's family (if they belong to one)
        // ---------------------------
        if ($user) {
            $post->setAuthor($user);

            // Find the FamilyMember entity for this user
            $familyMember = $this->familyMemberRepo->findOneBy(['user' => $user]);

            // If the user is linked to a family, link the post to the family
            if ($familyMember) {
                $post->setFamily($familyMember->getFamily());
            }
        }
    }
}
