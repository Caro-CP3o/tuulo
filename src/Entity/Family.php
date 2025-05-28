<?php

namespace App\Entity;

use App\Repository\FamilyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: FamilyRepository::class)]
#[ORM\Table(uniqueConstraints: [new ORM\UniqueConstraint(name: 'UNIQ_JOIN_CODE', columns: ['joinCode'])])]

#[ApiResource(
    normalizationContext: ['groups' => ['family:read']],
    denormalizationContext: ['groups' => ['family:write']],
)]

class Family
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['family:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de votre famille est requis.")]
    #[Assert\Length(max: 255, maxMessage: "Le nom de votre famille ne peut être supérieur à {{ limit }} caractères.")]
    #[Groups(['family:write', 'family:read'])]
    private ?string $name = null;

    // #[ORM\Column(length: 255, nullable: true)]
    #[ORM\ManyToOne(targetEntity: MediaObject::class, cascade: ['persist', 'remove',])]
    #[ApiProperty(types: ['https://schema.org/image'], writable: true)]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['family:write', 'family:read', 'media_object:read'])]
    private ?MediaObject $coverImage = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: "La description ne peut pas excéder {{ limit }} caractères."
    )]
    #[Groups(['family:write', 'family:read'])]
    private ?string $description = null;

    #[ORM\Column(length: 10, nullable: true)]
    #[Groups(['family:read'])]
    private ?string $joinCode = null;

    /**
     * @var Collection<int, FamilyMember>
     */
    #[ORM\OneToMany(targetEntity: FamilyMember::class, mappedBy: 'family', orphanRemoval: true)]
    #[Groups(['family:read'])]
    #[MaxDepth(1)]
    private Collection $familyMembers;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'family')]
    #[Groups(['family:read'])]
    #[MaxDepth(1)]
    private Collection $posts;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[Groups(['family:read', 'family:write'])]
    #[MaxDepth(1)]
    private ?User $creator = null;

    /**
     * @var Collection<int, FamilyInvitation>
     */
    #[ORM\OneToMany(targetEntity: FamilyInvitation::class, mappedBy: 'family', orphanRemoval: true)]
    #[Groups(['family:read', 'family:write'])]
    #[MaxDepth(1)]
    private Collection $familyInvitations;

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): static
    {
        $this->creator = $creator;

        return $this;
    }
    public function __construct()
    {
        $this->familyMembers = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->familyInvitations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }
    public function getCoverImage(): ?MediaObject
    {
        return $this->coverImage;
    }

    public function setCoverImage(?MediaObject $coverImage): self
    {
        $this->coverImage = $coverImage;
        return $this;
    }
    // public function getCoverImage(): ?string
    // {
    //     return $this->coverImage;
    // }

    // public function setCoverImage(?string $coverImage): static
    // {
    //     $this->coverImage = $coverImage;

    //     return $this;
    // }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getJoinCode(): ?string
    {
        return $this->joinCode;
    }

    public function setJoinCode(?string $joinCode): static
    {
        $this->joinCode = $joinCode;

        return $this;
    }

    /**
     * @return Collection<int, FamilyMember>
     */
    public function getFamilyMembers(): Collection
    {
        return $this->familyMembers;
    }

    public function addFamilyMember(FamilyMember $familyMember): static
    {
        if (!$this->familyMembers->contains($familyMember)) {
            $this->familyMembers->add($familyMember);
            $familyMember->setFamily($this);
        }

        return $this;
    }

    public function removeFamilyMember(FamilyMember $familyMember): static
    {
        if ($this->familyMembers->removeElement($familyMember)) {
            // set the owning side to null (unless already changed)
            if ($familyMember->getFamily() === $this) {
                $familyMember->setFamily(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Post>
     */
    public function getPosts(): Collection
    {
        return $this->posts;
    }

    public function addPost(Post $post): static
    {
        if (!$this->posts->contains($post)) {
            $this->posts->add($post);
            $post->setFamily($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {
            // set the owning side to null (unless already changed)
            if ($post->getFamily() === $this) {
                $post->setFamily(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, FamilyInvitation>
     */
    public function getFamilyInvitations(): Collection
    {
        return $this->familyInvitations;
    }

    public function addFamilyInvitation(FamilyInvitation $familyInvitation): static
    {
        if (!$this->familyInvitations->contains($familyInvitation)) {
            $this->familyInvitations->add($familyInvitation);
            $familyInvitation->setFamily($this);
        }

        return $this;
    }

    public function removeFamilyInvitation(FamilyInvitation $familyInvitation): static
    {
        if ($this->familyInvitations->removeElement($familyInvitation)) {
            // set the owning side to null (unless already changed)
            if ($familyInvitation->getFamily() === $this) {
                $familyInvitation->setFamily(null);
            }
        }

        return $this;
    }
}
