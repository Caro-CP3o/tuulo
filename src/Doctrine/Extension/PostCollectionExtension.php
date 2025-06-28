<?php

namespace App\Doctrine\Extension;

use ApiPlatform\Doctrine\Orm\Extension\CollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryResultCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use App\Entity\Post;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;

class PostCollectionExtension implements QueryResultCollectionExtensionInterface
{
    /**
     * Summary of getResult
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param mixed $resourceClass
     * @param mixed $operation
     * @param array $context
     * @return \Traversable|array
     */
    public function getResult(QueryBuilder $queryBuilder, ?string $resourceClass = null, ?\ApiPlatform\Metadata\Operation $operation = null, array $context = []): \Traversable|array
    {
        return $queryBuilder->getQuery()->getResult();
    }

    private Security $security;

    /**
     * Summary of __construct
     * @param \Symfony\Bundle\SecurityBundle\Security $security
     */
    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    /**
     * Modify the Doctrine ORM query to filter posts by the user's active family.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param \ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param mixed $operation
     * @param array $context
     * @return void
     */
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?\ApiPlatform\Metadata\Operation $operation = null,
        array $context = []
    ): void {
        // ---------------------------
        // Only applies to Post entity
        // ---------------------------
        if (Post::class !== $resourceClass) {
            return;
        }
        // ---------------------------
        // Get the authenticated user
        // ---------------------------
        $user = $this->security->getUser();
        if (!$user) {
            return;
        }
        // ---------------------------
        // Retrieve the user's family memberships
        // ---------------------------
        $familyMembers = $user->getFamilyMembers();

        // ---------------------------
        // Get th first active family membership
        // ---------------------------
        $family = null;
        foreach ($familyMembers as $familyMember) {
            if ($familyMember->getStatus() === 'active') {
                $family = $familyMember->getFamily();
                break;
            }
        }
        // ---------------------------
        // Return early if no family is found
        // ---------------------------
        if (!$family) {
            return;
        }
        // ---------------------------
        // Get the root alias of the query builder
        // ---------------------------
        $rootAlias = $queryBuilder->getRootAliases()[0];
        // ---------------------------
        // Add WHERE clause to filter posts by the user's active family
        // ---------------------------
        $queryBuilder
            ->andWhere("$rootAlias.family = :family")
            ->setParameter('family', $family);
    }

    /**
     * Retrieves the results from the Doctrine query.
     *
     * This method executes the query built by applyToCollection and
     * returns the result set.
     *
     * @param string $resourceClass
     * @param mixed $operation
     * @param array $context
     * @return bool
     */
    public function supportsResult(string $resourceClass, ?\ApiPlatform\Metadata\Operation $operation = null, array $context = []): bool
    {
        return $resourceClass === Post::class;
    }
}
