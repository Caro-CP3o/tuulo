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
    public function __construct(
        private Security $security,
        private FamilyMemberRepository $familyMemberRepo
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['setAuthorAndFamily', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function setAuthorAndFamily(ViewEvent $event): void
    {
        $post = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        // if (!$post instanceof Post || $method !== 'POST') {
        //     return;
        // }
        if (!$post instanceof Post || !in_array($method, ['POST', 'PATCH'], true)) {
            return;
        }


        $user = $this->security->getUser();

        // if ($method === 'POST' && $user)
        if ($user) {
            $post->setAuthor($user);

            $familyMember = $this->familyMemberRepo->findOneBy(['user' => $user]);
            if ($familyMember) {
                $post->setFamily($familyMember->getFamily());
            }
        }
        // foreach ($post->getImage() as $image) {
        //     $image->setPost($post);
        // }
        $image = $post->getImage();
        if ($image !== null) {
            $image->setPost($post);
        }
        $video = $post->getVideo();
        // if ($video !== null) {
        //     $video->setPost($post);
        // }
    }
}
