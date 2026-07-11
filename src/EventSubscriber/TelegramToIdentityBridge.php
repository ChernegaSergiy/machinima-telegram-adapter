<?php

declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\EventSubscriber;

use App\Contract\IdentityAssertion;
use App\Event\UserAuthenticatedEvent;
use Morfeditorial\TelegramBotBundle\Event\TelegramUserAuthenticatedEvent;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEventListener(event: TelegramUserAuthenticatedEvent::class)]
class TelegramToIdentityBridge
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
    ) {
    }

    public function __invoke(TelegramUserAuthenticatedEvent $event): void
    {
        $telegramUser = $event->getTelegramUserData();
        $telegramId = (string) ($telegramUser['id'] ?? '');

        if (!$telegramId) {
            return;
        }

        $nameParts = array_filter([$telegramUser['first_name'] ?? '', $telegramUser['last_name'] ?? '']);
        $displayName = !empty($nameParts) ? implode(' ', $nameParts) : null;

        $assertion = new IdentityAssertion(
            providerName: 'telegram',
            providerSubjectId: $telegramId,
            displayName: $displayName,
            claims: $telegramUser,
        );

        $authEvent = new UserAuthenticatedEvent($assertion);
        $this->eventDispatcher->dispatch($authEvent);

        if ($authEvent->getUser()) {
            $event->setUser($authEvent->getUser());
            
            $request = $this->requestStack->getCurrentRequest();
            if ($request && $request->hasSession()) {
                $request->getSession()->set('active_platform_provider', 'telegram');
            }
        }
    }
}
