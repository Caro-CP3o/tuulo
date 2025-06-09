<?php

namespace App\Events;

use ApiPlatform\Symfony\EventListener\EventPriorities;
use App\Entity\PostComment;
use App\Entity\Post;
use App\Repository\PostRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class PostCommentAuthorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private PostRepository $postRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['setAuthorAndPost', EventPriorities::PRE_VALIDATE],
        ];
    }

    public function setAuthorAndPost(ViewEvent $event): void
    {
        $comment = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$comment instanceof PostComment || $method !== 'POST') {
            return;
        }

        $user = $this->security->getUser();

        if (!$user) {
            return;
        }

        $comment->setUser($user);

        // Extract post IRI from request body (e.g. "/api/posts/123")
        $data = json_decode($event->getRequest()->getContent(), true);
        if (isset($data['post'])) {
            $postId = (int) basename($data['post']);
            $post = $this->postRepository->find($postId);
            if ($post instanceof Post) {
                $comment->setPost($post);
            }
        }
    }
}
