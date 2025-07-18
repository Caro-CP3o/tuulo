<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post as ApiPost;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use App\Repository\UserRepository;
use Cocur\Slugify\Slugify;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Patch;
use Symfony\Component\Serializer\Annotation\MaxDepth;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ApiResource(
    security: "is_granted('ROLE_USER')",
    operations: [
        new Get(),
        new GetCollection(),
        new ApiPost(security: "is_granted('PUBLIC_ACCESS')"),
        new Put(security: "object == user"),
        new Patch(security: "object == user"),
        new Delete(security: "object == user or is_granted('ROLE_FAMILY_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
)]
#[ORM\HasLifecycleCallbacks]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
#[UniqueEntity(fields: ['email'], message: 'Un autre utilisateur possède déjà cette adresse e-mail, merci de la modifier')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['user:read', 'family:read', 'post:read', 'post_like:read', 'comment:read', 'family_member:read'])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\Email(message: 'Veuillez renseigner un email valide')]
    #[Groups(['user:read', 'user:write', 'family:read', 'family_member:read'])]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le mot de passe est obligatoire")]
    #[Assert\Length(min: 8, max: 4096)]
    #[Assert\Regex(
        pattern: '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[^\w\s]).+$/',
        message: 'Votre mot de passe doit contenir au minimum: 1 majuscule, 1 minuscule, 1 chiffre et 1 caractère spécial.'
    )]
    #[Groups(['user:write'])]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'vous devez renseigner votre prénom')]
    #[Groups(['user:read', 'user:write', 'family:read', 'post:read', 'post_like:read', 'comment:read', 'family_member:read'])]
    private ?string $firstName = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'vous devez renseigner votre nom de famille')]
    #[Groups(['user:read', 'user:write', 'family:read', 'post:read', 'post_like:read', 'comment:read', 'family_member:read'])]
    private ?string $lastName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['user:read', 'user:write', 'post:read', 'post_like:read', 'comment:read', 'family_member:read'])]
    private ?string $alias = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\LessThanOrEqual(value: '-13 years', message: 'Vous devez avoir minimum 13 ans pour vous inscrire')]
    #[Assert\NotBlank(message: "La date de naissance est obligatoire")]
    #[Groups(['user:read', 'user:write'])]
    private ?\DateTimeInterface $birthDate = null;

    #[ORM\OneToOne(targetEntity: MediaObject::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ApiProperty(types: ['https://schema.org/image'], writable: true)]
    #[ORM\JoinColumn(nullable: true, onDelete: "CASCADE")]
    #[Groups(['user:read', 'user:write', 'media_object:read', 'post:read', 'post_like:read', 'comment:read', 'family_member:read'])]
    private ?MediaObject $avatar = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Vous devez choisir une couleur')]
    #[Groups(['user:read', 'user:write', 'post:read', 'post_like:read', 'comment:read', 'family_member:read'])]
    private ?string $color = null;

    #[ORM\Column(length: 255)]
    #[Groups(['user:read'])]
    private ?string $slug = null;

    #[ORM\Column]
    #[Groups(['user:read', 'user:write', 'family_member:read', 'family:read', 'invitation:read'])]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $updatedAt = null;

    /**
     * @var Collection<int, FamilyMember>
     */
    #[ORM\OneToMany(targetEntity: FamilyMember::class, mappedBy: 'user', cascade: ['persist', 'remove'])]
    #[Groups(['user:read', 'user:write', 'family:write', 'family:read', 'invitation:read', 'family_member:read'])]
    #[MaxDepth(1)]
    private Collection $familyMembers;

    /**
     * @var Collection<int, Post>
     */
    #[ORM\OneToMany(targetEntity: Post::class, mappedBy: 'author')]
    #[Groups(['user:read', 'post:read', 'comment:read'])]
    #[MaxDepth(1)]
    private Collection $posts;

    /**
     * @var Collection<int, PostLike>
     */
    #[ORM\OneToMany(targetEntity: PostLike::class, mappedBy: 'user', orphanRemoval: true)]
    #[Groups(['user:read', 'post:read'])]
    #[MaxDepth(1)]
    private Collection $postLikes;

    /**
     * @var Collection<int, PostComment>
     */
    #[ORM\OneToMany(targetEntity: PostComment::class, mappedBy: 'user')]
    #[Groups(['user:read', 'post:read', 'comment:read'])]
    #[MaxDepth(1)]
    private Collection $postComments;

    #[ORM\Column(type: 'boolean')]
    #[Groups(['user:read'])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'string', length: 64, nullable: true)]
    private ?string $emailVerificationCode = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    #[Groups(['user:read', 'user:write'])]
    private ?string $registrationStep = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Groups(['user:read'])]
    private ?\DateTimeInterface $emailVerificationExpiresAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: MediaObject::class, cascade: ['persist', 'remove'])]
    #[Groups(['user:read', 'user:write', 'media_object:read'])]
    private Collection $mediaObjects;

    public function getMediaObjects(): Collection
    {
        return $this->mediaObjects;
    }

    public function addMediaObject(MediaObject $mediaObject): self
    {
        if (!$this->mediaObjects->contains($mediaObject)) {
            $this->mediaObjects[] = $mediaObject;
            $mediaObject->setUser($this);
        }
        return $this;
    }

    public function removeMediaObject(MediaObject $mediaObject): self
    {
        if ($this->mediaObjects->removeElement($mediaObject)) {
            if ($mediaObject->getUser() === $this) {
                $mediaObject->setUser(null);
            }
        }
        return $this;
    }

    public function getEmailVerificationExpiresAt(): ?\DateTimeInterface
    {
        return $this->emailVerificationExpiresAt;
    }

    public function setEmailVerificationExpiresAt(?\DateTimeInterface $dateTime): self
    {
        $this->emailVerificationExpiresAt = $dateTime;
        return $this;
    }

    public function __construct()
    {
        $this->familyMembers = new ArrayCollection();
        $this->posts = new ArrayCollection();
        $this->postLikes = new ArrayCollection();
        $this->postComments = new ArrayCollection();
        $this->mediaObjects = new ArrayCollection();
    }

    public function getRegistrationStep(): string
    {
        return $this->registrationStep;
    }

    public function setRegistrationStep(string $step): self
    {
        $this->registrationStep = $step;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    public function getEmailVerificationCode(): ?string
    {
        return $this->emailVerificationCode;
    }

    public function setEmailVerificationCode(?string $code): self
    {
        $this->emailVerificationCode = $code;
        return $this;
    }


    public function getFullName(): string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(string $firstName): static
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(string $lastName): static
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    public function getBirthDate(): ?\DateTimeInterface
    {
        return $this->birthDate;
    }

    public function setBirthDate(\DateTimeInterface $birthDate): static
    {
        $this->birthDate = $birthDate;

        return $this;
    }

    public function getAvatar(): ?MediaObject
    {
        return $this->avatar;
    }

    public function setAvatar(?MediaObject $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    public function getColor(): ?string
    {
        return $this->color;
    }

    public function setColor(string $color): static
    {
        $this->color = $color;

        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;

        // guarantee every user at least has ROLE_USER
        if (!in_array('ROLE_USER', $roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function generateSlug(): void
    {
        if (!$this->slug) {
            $slugify = new Slugify();
            $this->slug = $slugify->slugify($this->firstName . ' ' . $this->lastName . ' ' . uniqid());
        }
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

    /**
     * @return Collection<int, FamilyMember>
     */
    public function getFamilyMembers(): Collection
    {
        return $this->familyMembers;
    }

    public function addFamilyMember(FamilyMember $familyMember): self
    {
        if (!$this->familyMembers->contains($familyMember)) {
            $this->familyMembers->add($familyMember);
            $familyMember->setUser($this);
        }

        return $this;
    }


    public function removeFamilyMember(FamilyMember $familyMember): self
    {
        if ($this->familyMembers->contains($familyMember)) {
            $this->familyMembers->removeElement($familyMember);
            // break the link back
            if ($familyMember->getUser() === $this) {
                $familyMember->setUser(null);
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
            $post->setAuthor($this);
        }

        return $this;
    }

    public function removePost(Post $post): static
    {
        if ($this->posts->removeElement($post)) {

            if ($post->getAuthor() === $this) {
                $post->setAuthor(null);
            }
        }

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
            $postLike->setUser($this);
        }

        return $this;
    }

    public function removePostLike(PostLike $postLike): static
    {
        if ($this->postLikes->removeElement($postLike)) {

            if ($postLike->getUser() === $this) {
                $postLike->setUser(null);
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
            $postComment->setUser($this);
        }

        return $this;
    }

    public function removePostComment(PostComment $postComment): static
    {
        if ($this->postComments->removeElement($postComment)) {

            if ($postComment->getUser() === $this) {
                $postComment->setUser(null);
            }
        }

        return $this;
    }
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

}
