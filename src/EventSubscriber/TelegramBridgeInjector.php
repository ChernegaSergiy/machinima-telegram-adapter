<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Injects the Telegram WebApp SDK and bridge script into HTML responses.
 *
 * This subscriber runs on kernel.response and injects <script> tags
 * right before </head>. The bridge script detects whether the app is
 * running inside Telegram, sets the __PLATFORM__ globals, stores a
 * cookie for server-side detection, and registers the platform bridge
 * initializer that the core app.js will call.
 *
 * The core application knows nothing about Telegram — all platform-specific
 * logic lives here in the adapter.
 */
final class TelegramBridgeInjector implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $contentType = $response->headers->get('content-type', '');

        if (!str_contains($contentType, 'text/html')) {
            return;
        }

        $content = $response->getContent();
        if ($content === false) {
            return;
        }

        $bridgeScript = $this->getBridgeScript();
        $content = str_replace('</head>', $bridgeScript . "\n</head>", $content);
        $response->setContent($content);
    }

    private function getBridgeScript(): string
    {
        return <<<'HTML'
<script src="https://telegram.org/js/telegram-web-app.js"></script>
<script>
(function() {
    var tg = window.Telegram && window.Telegram.WebApp;
    if (!tg || !tg.initData) return;

    // Update the core __PLATFORM__ object (set by base.html.twig)
    if (window.__PLATFORM__) {
        window.__PLATFORM__.isEmbedded = true;
        window.__PLATFORM__.platformName = 'telegram';
        window.__PLATFORM__.theme = tg.colorScheme || 'dark';
        window.__PLATFORM__.initData = tg.initData;
        window.__PLATFORM__.capabilities = ['tma', 'notifications', 'back_button'];
    }

    // Set cookie so the server can detect Telegram on subsequent requests
    var cookieStr = "tma_init_data=" + encodeURIComponent(tg.initData) + "; path=/; max-age=86400;";
    if (window.location.protocol === 'https:') cookieStr += " SameSite=None; Secure;";
    document.cookie = cookieStr;

    // Register the platform bridge for getPlatformBridge()
    window.__PLATFORM_BRIDGE__ = tg;

    // Register platform-specific initialization (called by core initEmbedded())
    window.__PLATFORM_BRIDGE_INIT__ = function() {
        document.body.classList.add('is-tma');
        document.title = 'Morf TMA';

        var currentRoute = document.body.dataset.route;
        var rootRoutes = ['app_index', 'app_categories', 'app_authors', 'app_notifications', 'app_profile', 'app_login'];

        if (!rootRoutes.includes(currentRoute)) {
            tg.BackButton.show();
            if (!window.__tgBackAssigned) {
                tg.BackButton.onClick(function() { window.history.back(); });
                window.__tgBackAssigned = true;
            }
        } else {
            if (tg.BackButton) tg.BackButton.hide();
        }

        var updateTheme = function() {
            document.documentElement.style.setProperty('--shimmer-base', tg.colorScheme === 'dark' ? '255, 255, 255' : '0, 0, 0');
        };
        updateTheme();
        tg.onEvent('themeChanged', updateTheme);

        // Intercept links to pass initData along
        if (!window.__platformLinkIntercept) {
            document.addEventListener('click', function(e) {
                var link = e.target.closest('a');
                if (link && link.href && link.href.startsWith(window.location.origin) && tg.initData) {
                    try {
                        var url = new URL(link.href);
                        if (!url.searchParams.has('initData')) {
                            url.searchParams.set('initData', tg.initData);
                            link.href = url.toString();
                        }
                    } catch(err) {}
                }
            });
            window.__platformLinkIntercept = true;
        }
    };
})();
</script>
HTML;
    }
}
