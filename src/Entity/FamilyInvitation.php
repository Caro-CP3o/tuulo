<?php

namespace App\Entity;

use App\Repository\FamilyInvitationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;

#[ORM\Entity(repositoryClass: FamilyInvitationRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ['invitation:read']],
    denormalizationContext: ['groups' => ['invitation:write']]
)]
class FamilyInvitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['invitation:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'familyInvitations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Assert\Type(\DateTimeImmutable::class)]
    private ?Family $family = null;

    #[ORM\Column(length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[Groups(['invitation:read', 'invitation:write'])]
    private ?string $code = null;

    #[ORM\Column]
    #[Groups(['invitation:read', 'invitation:write'])]
    private ?\DateTimeImmutable $expiresAt = null;

    #[ORM\Column]
    #[Groups(['invitation:read'])]
    #[Assert\NotNull]
    private ?bool $used = null;

    public function __construct()
    {
        $this->used = false;
        $this->expiresAt = new \DateTimeImmutable('+7 days');
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

    public function setUsed(bool $used): static
    {
        $this->used = $used;

        return $this;
    }
}
