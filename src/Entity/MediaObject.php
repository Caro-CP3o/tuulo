<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post as ApiPost;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Delete;
use App\Repository\MediaObjectRepository;
use App\Repository\PostRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\OpenApi\Model;
use App\Controller\CreateMediaObjectActionController;
use Symfony\Component\Serializer\Annotation\SerializedName;

#[ORM\Entity(repositoryClass: MediaObjectRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
#[ApiResource(
    security: "is_granted('ROLE_USER')",
    normalizationContext: ['groups' => ['media_object:read']],
    types: ['https://schema.org/MediaObject'],
    outputFormats: ['jsonld' => ['application/ld+json']],
    operations: [
        new Get(),
        new GetCollection(),
        new Delete(),
        new ApiPost(
            security: "is_granted('PUBLIC_ACCESS')",
            controller: CreateMediaObjectActionController::class,
            inputFormats: ['multipart' => ['multipart/form-data']],
            validationContext: ['groups' => ['media_object_create']],
            deserialize: false,
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary'
                                    ]
                                ]
                            ]
                        ]
                    ])
                )
            )
        ),
        new Patch(
            controller: CreateMediaObjectActionController::class,
            inputFormats: ['multipart' => ['multipart/form-data']],
            validationContext: ['groups' => ['media_object_create']],
            deserialize: false,
            openapi: new Model\Operation(
                requestBody: new Model\RequestBody(
                    content: new \ArrayObject([
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'file' => [
                                        'type' => 'string',
                                        'format' => 'binary'
                                    ]
                                ]
                            ]
                        ]
                    ])
                )
            )
        )

    ]
)]
class MediaObject
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['media_object:read'])]
    private ?int $id = null;

    #[Vich\UploadableField(mapping: 'media_object', fileNameProperty: 'filePath')]
    #[Assert\NotNull(groups: ['media_object_create'])]
    #[Assert\File(
        maxSize: '10M',
        mimeTypes: [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'video/mp4',
            'video/quicktime', // .mov
            'video/x-msvideo', // .avi
        ],
        mimeTypesMessage: 'Please upload a valid image or video file',
        groups: ['media_object_create']
    )]
    private ?File $file = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['media_object:read'])]
    private ?string $filePath = null;

    #[ORM\Column(type: 'datetime')]
    #[Groups(['media_object:read'])]
    private \DateTimeInterface $updatedAt;

    #[ApiProperty(types: ['https://schema.org/contentUrl'], writable: false)]
    #[Groups(['media_object:read', 'media_object:write', 'user:read', 'family:read', 'post:read'])]
    #[SerializedName('contentUrl')]
    public function getContentUrl(): ?string
    {
        return $this->filePath ? '/media/' . $this->filePath : null;
    }

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'mediaObjects')]
    #[ORM\JoinColumn(nullable: true)]
    #[Groups(['media_object:read'])]
    private ?User $user = null;


    #[ORM\OneToOne(targetEntity: Post::class, mappedBy: 'image')]
    private ?Post $imagePost = null;

    #[ORM\OneToOne(targetEntity: Post::class, mappedBy: 'video')]
    private ?Post $videoPost = null;

    public function getImagePost(): ?Post
    {
        return $this->imagePost;
    }

    public function setImagePost(?Post $post): self
    {
        $this->imagePost = $post;

        if ($post !== null && $post->getImage() !== $this) {
            $post->setImage($this);
        }

        return $this;
    }

    public function getVideoPost(): ?Post
    {
        return $this->videoPost;
    }

    public function setVideoPost(?Post $post): self
    {
        $this->videoPost = $post;

        if ($post !== null && $post->getVideo() !== $this) {
            $post->setVideo($this);
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;
        return $this;
    }
    public function __construct()
    {
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

    public function setFile(?File $file = null): void
    {
        $this->file = $file;

        if ($file !== null) {
            $this->updatedAt = new \DateTime();
        }
    }

    public function getFile(): ?File
    {
        return $this->file;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getUpdatedAt(): \DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
}
