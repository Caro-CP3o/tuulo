<?php
namespace App\Security;

use Lexik\Bundle\JWTAuthenticationBundle\TokenExtractor\TokenExtractorInterface;
use Symfony\Component\HttpFoundation\Request;

class CookieTokenExtractor implements TokenExtractorInterface
{
    private string $cookieName;

    /**
     * Summary of __construct
     * @param string $cookieName
     */
    public function __construct(string $cookieName = 'token')
    {
        $this->cookieName = $cookieName;
    }

    /**
     * Summary of extract
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return bool|float|int|string|null
     */
    public function extract(Request $request): ?string
    {
        return $request->cookies->get($this->cookieName);
    }
}
