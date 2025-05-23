<?php

namespace App\Dto;
use Symfony\Component\Serializer\Annotation\Groups;

class UserRegistrationOutput
{
    #[Groups(['user:read'])]
    public int $id;

    #[Groups(['user:read'])]
    public string $email;

    #[Groups(['user:read'])]
    public string $firstName;

    #[Groups(['user:read'])]
    public string $lastName;

    #[Groups(['user:read'])]
    public string $slug;

    #[Groups(['user:read'])]
    public bool $isVerified;

    #[Groups(['user:read'])]
    public ?string $emailVerificationCode = null;
    #[Groups(['user:read'])]
    public string $registrationStep = 'step1';

    #[Groups(['user:read'])]
    public bool $success;


    public function __construct(bool $success, ?string $emailVerificationCode = null)
    {
        $this->success = $success;
        $this->emailVerificationCode = $emailVerificationCode;
    }
}
