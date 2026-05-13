<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaService
{
    private const VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';
    private const MIN_SCORE = 0.5;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        #[\Symfony\Component\DependencyInjection\Attribute\Autowire(env: 'RECAPTCHA_SECRET_KEY')]
        private readonly string $secretKey,
    ) {
    }

    public function isValid(string $token, string $action): bool
    {
        if ('' === $token) {
            return false;
        }

        try {
            $response = $this->httpClient->request('POST', self::VERIFY_URL, [
                'body' => [
                    'secret' => $this->secretKey,
                    'response' => $token,
                ],
            ]);

            $data = $response->toArray();

            return ($data['success'] ?? false)
                && ($data['score'] ?? 0) >= self::MIN_SCORE
                && ($data['action'] ?? '') === $action;
        } catch (\Throwable) {
            return false;
        }
    }
}
