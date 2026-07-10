<?php
declare(strict_types=1);

namespace Morfeditorial\MachinimaTelegramAdapter\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Injects the Telegram WebApp SDK and bridge script into HTML responses.
 *
 * Responsibilities:
 * - Calls tg.ready() immediately so Telegram dismisses its loading screen.
 * - Sets window.__PLATFORM__.isEmbedded = true when initData is present.
 * - Runs zero-click login: if the SERVER didn't know it was Telegram (__SERVER_EMBEDDED__ = false),
 *   fetches the current URL with ?initData= appended to establish a session, then reloads.
 * - Shows "not supported" screen when NOT in Telegram (no initData).
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

    if (!tg || !tg.initData) {
        // Not a Telegram Mini App — show "not supported" for non-auth routes.
        document.addEventListener('DOMContentLoaded', function() {
            var route = document.body && document.body.dataset.route;
            var authRoutes = ['app_login', 'telegram_oidc_login', 'telegram_oidc_callback'];
            if (route && authRoutes.includes(route)) return;

            var botLink = window.__PLATFORM__ && window.__PLATFORM__.botLink;
            document.body.innerHTML =
                '<div style="display:flex;min-height:100vh;align-items:center;justify-content:center;padding:1rem">' +
                  '<div style="text-align:center;max-width:360px;width:100%">' +
                    '<p style="margin-bottom:1.5rem;font-weight:600">Цей застосунок призначено для використання в підтримуваному середовищі.</p>' +
                    (botLink ? '<a href="' + botLink + '" style="display:block;padding:.75rem 1.5rem;font-weight:700;text-decoration:none">Відкрити застосунок</a>' : '') +
                  '</div>' +
                '</div>';
        });
        return;
    }

    // ── We are inside a Telegram Mini App ────────────────────────────────────

    // 1. Tell Telegram the app is ready — clears the black loading screen.
    tg.ready();

    // 2. Update the __PLATFORM__ object that base.html.twig set server-side.
    if (window.__PLATFORM__) {
        window.__PLATFORM__.isEmbedded   = true;
        window.__PLATFORM__.platformName = 'telegram';
        window.__PLATFORM__.theme        = tg.colorScheme || 'dark';
        window.__PLATFORM__.initData     = tg.initData;
        window.__PLATFORM__.capabilities = ['tma', 'notifications', 'back_button'];
    }

    // 3. Store cookie so the server recognises Telegram on every subsequent request.
    var cookieStr = 'tma_init_data=' + encodeURIComponent(tg.initData) + '; path=/; max-age=86400;';
    if (window.location.protocol === 'https:') cookieStr += ' SameSite=None; Secure;';
    document.cookie = cookieStr;

    // 4. Zero-click login.
    //    __SERVER_EMBEDDED__ is the value the server baked into the page BEFORE
    //    we changed isEmbedded above.  If it was false the server had no session
    //    for this user yet → silently fetch with ?initData= to create one, then
    //    reload.  After the reload the server will have the cookie → isEmbedded=true
    //    → __SERVER_EMBEDDED__=true → this block is skipped.
    if (!window.__SERVER_EMBEDDED__) {
        var authUrl = new URL(window.location.href);
        authUrl.searchParams.set('initData', tg.initData);
        fetch(authUrl.toString(), { credentials: 'same-origin', redirect: 'follow' })
            .then(function() { window.location.reload(); })
            .catch(function() {});
        return; // don't register bridge init — page is about to reload
    }

    // 5. Register the platform bridge (only reached when already authenticated).
    window.__PLATFORM_BRIDGE__ = tg;

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
            document.documentElement.style.setProperty(
                '--shimmer-base',
                tg.colorScheme === 'dark' ? '255, 255, 255' : '0, 0, 0'
            );
        };
        updateTheme();
        tg.onEvent('themeChanged', updateTheme);

        // Attach initData to every internal link for authenticator support.
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
