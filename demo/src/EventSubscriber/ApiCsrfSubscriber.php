<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Désactive CSRF pour les routes API filesystem.
 */
final class ApiCsrfSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 512],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        
        // Désactiver CSRF pour les routes API filesystem
        if (str_starts_with($request->getPathInfo(), '/api/filesystem')) {
            $request->attributes->set('_token_check', false);
        }
    }
}
