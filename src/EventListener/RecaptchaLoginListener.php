<?php

namespace App\EventListener;

use App\Service\RecaptchaService;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Event\CheckPassportEvent;

#[AsEventListener(event: CheckPassportEvent::class, priority: 512)]
class RecaptchaLoginListener
{
    public function __construct(
        private readonly RecaptchaService $recaptcha,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function __invoke(CheckPassportEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request || !$request->isMethod('POST')) {
            return;
        }

        if ($request->attributes->get('_route') !== 'app_login') {
            return;
        }

        $token = $request->request->getString('_recaptcha_token');

        if (!$this->recaptcha->isValid($token, 'login')) {
            throw new \Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException(
                'Verificação de segurança falhou. Tente novamente.'
            );
        }
    }
}
