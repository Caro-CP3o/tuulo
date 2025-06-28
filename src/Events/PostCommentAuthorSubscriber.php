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
    /**
     * Summary of __construct
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     * @param \App\Repository\PostRepository $postRepository
     */
    public function __construct(
        private Security $security,
        private PostRepository $postRepository
    ) {
    }

    /**
     * Summary of getSubscribedEvents
     * @return array<int|string>[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['setAuthorAndPost', EventPriorities::PRE_VALIDATE],
        ];
    }

    /**
     * Automatically set the author and the related Post of a PostComment entity
     * before it is validated and persisted.
     *
     * @param \Symfony\Component\HttpKernel\Event\ViewEvent $event
     * @return void
     */
    public function setAuthorAndPost(ViewEvent $event): void
    {
        // ---------------------------
        // Get the entity returned by the controller (PostComment)
        // ---------------------------
        $comment = $event->getControllerResult();
        // ---------------------------
        // Get the HTTP method of the current request
        // ---------------------------
        $method = $event->getRequest()->getMethod();
        // ---------------------------
        // Only proceed if the entity is a PostComment and the method is POST
        // ---------------------------
        if (!$comment instanceof PostComment || $method !== 'POST') {
            return;
        }
        // ---------------------------
        // Get the currently authenticated user
        // ---------------------------
        $user = $this->security->getUser();
        // ---------------------------
        // If no user is logged in, we cannot set the author; exit early
        // ---------------------------
        if (!$user) {
            return;
        }
        // ---------------------------
        // Set the currently authenticated user as the comment author
        // ---------------------------
        $comment->setUser($user);

        // ---------------------------
        // Extract post IRI (e.g., "/api/posts/123") from request JSON body
        // ---------------------------
        $data = json_decode($event->getRequest()->getContent(), true);
        if (isset($data['post'])) {
            // ---------------------------
            // Get the post ID by taking the last part of the IRI
            // ---------------------------
            $postId = (int) basename($data['post']);
            // ---------------------------
            // Fetch the Post entity from the database
            // ---------------------------
            $post = $this->postRepository->find($postId);
            // ---------------------------
            // If the post exists, link it to the comment
            // ---------------------------
            if ($post instanceof Post) {
                $comment->setPost($post);
            }
        }
    }
}
