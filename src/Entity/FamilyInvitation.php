<?php

namespace App\Entity;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\Controller\CreateFamilyInvitationController;
use App\Repository\FamilyInvitationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Post as ApiPost;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\String\ByteString;

#[ORM\Entity(repositoryClass: FamilyInvitationRepository::class)]
#[ApiResource(
    security: "is_granted('ROLE_USER')",
    operations: [
        new Get(),
        new GetCollection(),
        new ApiPost(
            controller: CreateFamilyInvitationController::class,
            security: "is_granted('ROLE_FAMILY_ADMIN')",
            deserialize: false, // We'll handle JSON manually
            name: 'create_family_invitation'
        ),
        new Put(security: "is_granted('ROLE_FAMILY_ADMIN')"),
        new Patch(security: "is_granted('ROLE_FAMILY_ADMIN')"),
        new Delete(security: "is_granted('ROLE_FAMILY_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['invitation:read']],
    denormalizationContext: ['groups' => ['invitation:write']]
)]
// #[ApiResource(
//     security: "is_granted('ROLE_USER')",
//     operations: [
//         new Get(),
//         new GetCollection(),
//         new ApiPost(security: "is_granted('ROLE_FAMILY_ADMIN')"),
//         new Put(security: "is_granted('ROLE_FAMILY_ADMIN')"),
//         new Patch(security: "is_granted('ROLE_FAMILY_ADMIN')"),
//         new Delete(security: "is_granted('ROLE_FAMILY_ADMIN')"),
//     ],
//     normalizationContext: ['groups' => ['invitation:read']],
//     denormalizationContext: ['groups' => ['invitation:write']]
// )]
#[ORM\HasLifecycleCallbacks]
class FamilyInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['invitation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'familyInvitations')]
    #[ORM\JoinColumn(nullable: false)]
    // #[Assert\NotNull]
    #[Groups(['invitation:read', 'invitation:write'])]
    private ?Family $family = null;

    #[ORM\Column(length: 255, nullable: false, unique: true)]
    // #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Groups(['invitation:read'])]
    private ?string $code;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['invitation:read', 'invitation:write'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Groups(['invitation:read', 'invitation:write'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['invitation:read', 'invitation:write', 'user:read', 'user:write', 'family:read'])]
    // #[Assert\NotNull]
    private ?bool $used = false;

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }
    public function __construct()
    {
        $this->used = false;
        $this->expiresAt = new \DateTimeImmutable('+7 days');
        $this->code = ByteString::fromRandom(10)->toString();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFamily(): ?Family
    {
        return $this->family;
    }

    public function setFamily(?Family $family): static
    {
        $this->family = $family;

        return $this;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getExpiresAt(): ?\DateTimeImmutable
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(\DateTimeImmutable $expiresAt): static
    {
        $this->expiresAt = $expiresAt;

        return $this;
    }

    public function isUsed(): ?bool
    {
        return $this->used;
    }

    public function setUsed(bool $used): self
    {
        $this->used = $used;

        return $this;
    }
}
