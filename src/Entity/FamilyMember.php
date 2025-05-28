<?php

namespace App\Entity;

use App\Repository\FamilyMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;

#[ORM\Entity(repositoryClass: FamilyMemberRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['user' => 'exact'])]
#[ApiResource(
    normalizationContext: ['groups' => ['familyMember:read']],
    denormalizationContext: ['groups' => ['familyMember:write']],
)]
class FamilyMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['familyMember:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'familyMembers')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['familyMember:read', 'familyMember:write'])]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'familyMembers')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['familyMember:read', 'familyMember:write'])]
    private ?Family $family = null;

    #[ORM\Column]
    #[Groups(['familyMember:read'])]
    private ?\DateTimeImmutable $joinedAt = null;

    // pending, approved, active, rejected
    #[ORM\Column(length: 20)]
    #[Groups(['familyMember:read', 'familyMember:write'])]
    private string $status = 'pending';

    #[ORM\Column(length: 180, nullable: true)]
    #[Groups(['familyMember:read', 'familyMember:write'])]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['familyMember:write'])]
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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
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

    public function getJoinedAt(): ?\DateTimeImmutable
    {
        return $this->joinedAt;
    }

    public function setJoinedAt(\DateTimeImmutable $joinedAt): static
    {
        $this->joinedAt = $joinedAt;

        return $this;
    }
}
