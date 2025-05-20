<?php
namespace App\Events;

use App\Entity\Family;
use App\Entity\FamilyMember;
use App\Entity\User;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use ApiPlatform\Symfony\EventListener\EventPriorities;
use Symfony\Bundle\SecurityBundle\Security;

class FamilyCreatorSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::VIEW => ['onFamilyCreate', EventPriorities::PRE_WRITE],
        ];
    }

    public function onFamilyCreate(ViewEvent $event): void
    {
        $family = $event->getControllerResult();
        $method = $event->getRequest()->getMethod();

        if (!$family instanceof Family || $method !== 'POST') {
            return;
        }

        //no pcq user fait tout d'un coup ? quid si abandonne la registration en pein mileiu ? le user n'a pas créé de famille et n'est pas vérifié ? le user n'a pas créé de famille et est connecté ?
        /** @var User $user */
        $user = $this->security->getUser();

        // Fallback to family->creator if no authenticated user
        if (!$user && $family->getCreator() instanceof User) {
            $user = $family->getCreator();
        }


        // If no user at all, abort (this shouldn't happen)
        if (!$user) {
            $this->logger->warning('No user found when creating family.');
            return;
        }

        $this->logger->info('FamilyCreatorSubscriber triggered.', [
            'family_name' => $family->getName(),
            'user_email' => $user->getEmail(),
        ]);

        // Ensure creator is set
        if (!$family->getCreator()) {
            $family->setCreator($user);
            $this->em->persist($family);
        }


        $roles = $user->getRoles();
        if (!in_array('ROLE_FAMILY_ADMIN', $roles, true)) {
            $roles[] = 'ROLE_FAMILY_ADMIN';
            $user->setRoles($roles);
            $this->em->persist($user); // needed because we're modifying user

        }

        // Create FamilyMember link
        $familyMember = new FamilyMember();
        $familyMember->setFamily($family);
        $familyMember->setUser($user);
        $familyMember->setStatus('active');
        // Optionally generate a token or other fields here if needed

        $this->em->persist($familyMember);

        // No flush here, API Platform will flush after the controller returns
    }
}