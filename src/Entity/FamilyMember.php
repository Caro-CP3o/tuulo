<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use App\Repository\FamilyMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post as ApiPost;
use App\Controller\FamilyMemberController;

#[ORM\UniqueConstraint(name: "unique_user", columns: ["user_id"])]

#[ORM\Entity(repositoryClass: FamilyMemberRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['user' => 'exact', 'family' => 'exact', 'status' => 'exact'])]
#[ApiResource(
    security: "is_granted('ROLE_USER')",
    operations: [
        new Get(security: "object.getUser() == user or is_granted('ROLE_FAMILY_ADMIN')"),
        new GetCollection(),
        new ApiPost(),
        new Put(security: "is_granted('ROLE_USER') and object.getUser() == user"),
        new Delete(),
        // security: "is_granted('ROLE_FAMILY_ADMIN') or object.getUser() == user"
        new Patch(
            name: 'approve_family_member',
            uriTemplate: '/family_members/{id}/approve',
            controller: FamilyMemberController::class,
            read: true,
            deserialize: false,
            security: "is_granted('ROLE_FAMILY_ADMIN')",
        ),
    ],
    normalizationContext: ['groups' => ['family_member:read']],
    denormalizationContext: ['groups' => ['family_member:write']],
)]
class FamilyMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['family_member:read', 'user:read', 'family:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'familyMembers')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['family_member:read', 'family_member:write', 'invitation:read', 'user:read', 'family:read'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'familyMembers')]
    #[ORM\JoinColumn(nullable: false, onDelete: "CASCADE")]
    #[Groups(['family_member:read', 'family_member:write', 'invitation:read', 'user:read', 'family:read'])]
    private ?Family $family = null;

    #[ORM\Column]
    #[Groups(['family_member:read', 'user:read'])]
    private ?\DateTimeImmutable $joinedAt = null;

    // pending, active, rejected
    #[ORM\Column(length: 20)]
    #[Groups(['family_member:read', 'family_member:write', 'invitation:read', 'user:read', 'family:read'])]
    private string $status = 'pending';

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['family_member:read', 'family_member:write', 'invitation:read', 'user:read', 'family:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['family_member:write', 'family_member:read', 'invitation:read', 'user:read', 'family:read'])]
    private ?string $token = null;

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): self
    {
        $this->token = $token;
        return $this;
    }
    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;
        return $this;
    }
    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }


    public function __construct()
    {
        $this->joinedAt = new \DateTimeImmutable(); // Automatically set the joinedAt to the current date and time
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Summary of getUser
     * @return User|null
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }
    /**
     * Summary of getFamily
     * @return Family|null
     */
    public function getFamily(): ?Family
    {
        return $this->family;
    }

    public function setFamily(?Family $family): static
    {
        $this->family = $family;

        return $this;
    }

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }

    #[Groups(['user:read'])]
    public function getUserId(): ?int
    {
        return $this->user?->getId();
    }

    #[Groups(['user:read'])]
    public function getFamilyId(): ?int
    {
        return $this->family?->getId();
    }

    public function getColor(): ?string
    {
        return $this->user?->getColor();
    }
}
