<?php

namespace App\Events;

use App\Entity\FamilyInvitation;
use App\Entity\User;
use App\Entity\Family;
use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\PrePersistEventArgs;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\ByteString;

class FamilyInvitationSubscriber implements EventSubscriber
{
    public function __construct(private Security $security)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::prePersist,
        ];
    }

    public function prePersist(PrePersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof FamilyInvitation) {
            return;
        }

        // Generate the invitation code if not already set
        // if (empty($entity->getCode())) {
        //     $entity->setCode(ByteString::fromRandom(10)->toString());
        // }

        // Set the family if not already set
        if (null === $entity->getFamily()) {
            $user = $this->security->getUser();

            if (!$user instanceof User) {
                throw new \RuntimeException('Cannot create invitation: no authenticated user.');
            }

            if (!in_array('ROLE_FAMILY_ADMIN', $user->getRoles(), true)) {
                throw new \RuntimeException('Only family admins can generate invitations.');
            }

            $family = $this->getCurrentFamilyFromUser($user);
            if (null === $family) {
                throw new \RuntimeException('User is not associated with a family.');
            }

            $entity->setFamily($family);
        }
    }

    // private function getCurrentFamilyFromUser(User $user): ?\App\Entity\Family
    // {
    //     foreach ($user->getFamilyMembers() as $familyMember) {
    //         return $familyMember->getFamily(); // Return first associated family
    //     }

    //     return null;
    // }
    private function getCurrentFamilyFromUser(User $user): ?Family
    {
        foreach ($user->getFamilyMembers() as $familyMember) {
            return $familyMember->getFamily();
        }

        return null;
    }

}
