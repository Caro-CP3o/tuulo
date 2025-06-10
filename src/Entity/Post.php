<?php

namespace App\Entity;

use ApiPlatform\Metadata\Patch;
use App\Repository\PostRepository;
use App\Repository\FamilyMemberRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\ApiProperty;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post as ApiPost;
use ApiPlatform\Metadata\Put;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ApiFilter(SearchFilter::class, properties: ['family.id' => 'exact'])]
// #[ApiFilter(OrderFilter::class, properties: ['createdAt' => 'DESC'], arguments: ['orderParameterName' => 'order'])]
#[ApiResource(
    security: "is_granted('ROLE_USER')",
    operations: [
        new Get(),
        new GetCollection(),
        new ApiPost(),
        new Put(security: "object.getAuthor() == user"),
        new Patch(security: "object.getAuthor() == user"),
        new Delete(security: "object.getAuthor() == user or is_granted('ROLE_FAMILY_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['post:read']],
    denormalizationContext: ['groups' => ['post:write']],
)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['post:read', 'post_like:read'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['post:read', 'post_like:read'])]
    private ?User $author = null;

    #[ORM\ManyToOne(targetEntity: Family::class, inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull]
    #[Groups(['post:read'])]
    private ?Family $family = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Le contenu ne peut pas être vide.")]
    #[Assert\Length(
        min: 1,
        max: 5000,
        minMessage: "Le contenu ne peut pas être vide.",
        maxMessage: "Le contenu est trop long. Il doit faire au maximum {{ limit }} caractères."
    )]
    #[Groups(['post:read', 'post:write'])]
    private ?string $content = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Vous devez ajouter un titre.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    #[Groups(['post:read', 'post:write', 'post_like:read'])]
    private ?string $title = null;

    #[ORM\OneToOne(targetEntity: MediaObject::class, inversedBy: 'post', cascade: ['persist', 'remove',], orphanRemoval: true)]
    #[ApiProperty(types: ['https://schema.org/image'], writable: true)]
    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    #[Groups(['post:write', 'post:read', 'media_object:read'])]
    private ?MediaObject $image = null;

    #[ORM\OneToOne(targetEntity: MediaObject::class, inversedBy: 'post', cascade: ['persist', 'remove',], orphanRemoval: true)]
    #[ApiProperty(types: ['https://schema.org/image'], writable: true)]
    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    #[Groups(['post:write', 'post:read', 'media_object:read'])]
    private ?MediaObject $video = null;

    #[ORM\Column]
    #[Groups(['post:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    #[Groups(['post:read'])]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, PostLike>
     */
    #[ORM\OneToMany(targetEntity: PostLike::class, mappedBy: 'post', orphanRemoval: true)]
    #[Groups(['post:read', 'post_like:read'])]
    #[ORM\JoinColumn(onDelete: "CASCADE")]
    private Collection $postLikes;

    /**
     * @var Collection<int, PostComment>
     */
    #[ORM\OneToMany(targetEntity: PostComment::class, mappedBy: 'post', orphanRemoval: true)]
    #[Groups(['post:read'])]
    #[ORM\JoinColumn(onDelete: "CASCADE")]
    private Collection $postComments;

    public function __construct()
    {
        $this->postLikes = new ArrayCollection();
        $this->postComments = new ArrayCollection();
        // $this->images = new ArrayCollection();
    }


    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

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

    // public function isMember(User $user): bool
    // {
    //     foreach ($this->familyMembers as $familyMember) {
    //         if ($familyMember->getUser()->getId() === $user->getId()) {
    //             return true;
    //         }
    //     }
    //     return false;
    // }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getImage(): ?MediaObject
    {
        return $this->image;
    }
    #[Groups(['post:read'])]
    public function getImageUrl(): ?string
    {
        return $this->image?->getContentUrl();
    }
    public function setImage(?MediaObject $image): static
    {
        $this->image = $image;

        return $this;
    }
    public function getVideo(): ?MediaObject
    {
        return $this->video;
    }

    public function setVideo(?MediaObject $video): static
    {
        $this->video = $video;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, PostLike>
     */
    public function getPostLikes(): Collection
    {
        return $this->postLikes;
    }

    public function addPostLike(PostLike $postLike): static
    {
        if (!$this->postLikes->contains($postLike)) {
            $this->postLikes->add($postLike);
            $postLike->setPost($this);
        }

        return $this;
    }

    public function removePostLike(PostLike $postLike): static
    {
        if ($this->postLikes->removeElement($postLike)) {
            // set the owning side to null (unless already changed)
            if ($postLike->getPost() === $this) {
                $postLike->setPost(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PostComment>
     */
    public function getPostComments(): Collection
    {
        return $this->postComments;
    }

    public function addPostComment(PostComment $postComment): static
    {
        if (!$this->postComments->contains($postComment)) {
            $this->postComments->add($postComment);
            $postComment->setPost($this);
        }

        return $this;
    }

    public function removePostComment(PostComment $postComment): static
    {
        if ($this->postComments->removeElement($postComment)) {
            // set the owning side to null (unless already changed)
            if ($postComment->getPost() === $this) {
                $postComment->setPost(null);
            }
        }

        return $this;
    }
}

