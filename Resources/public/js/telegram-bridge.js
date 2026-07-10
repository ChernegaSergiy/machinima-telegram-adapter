(function() {
    var tg = window.Telegram && window.Telegram.WebApp;

    if (!tg || !tg.initData) {
        document.addEventListener('DOMContentLoaded', function() {
            var route = document.body && document.body.dataset.route;
            var authRoutes = ['app_login', 'telegram_oidc_login', 'telegram_oidc_callback'];
            if (route && authRoutes.includes(route)) return;

            document.getElementById('main-content').style.display = 'none';
            var ns = document.getElementById('not-supported');
            if (ns) ns.style.display = 'flex';

            var botLink = window.__PLATFORM__ && window.__PLATFORM__.botLink;
            var linkEl = document.getElementById('not-supported-bot-link');
            if (linkEl && botLink) linkEl.href = botLink;

            if (typeof lucide !== 'undefined') lucide.createIcons();
        });
        return;
    }

    tg.ready();

    if (window.__PLATFORM__) {
        window.__PLATFORM__.isEmbedded   = true;
        window.__PLATFORM__.platformName = 'telegram';
        window.__PLATFORM__.theme        = tg.colorScheme || 'dark';
        window.__PLATFORM__.initData     = tg.initData;
        window.__PLATFORM__.capabilities = ['tma', 'notifications', 'back_button'];
    }

    var cookieStr = 'tma_init_data=' + encodeURIComponent(tg.initData) + '; path=/; max-age=86400;';
    if (window.location.protocol === 'https:') cookieStr += ' SameSite=None; Secure;';
    document.cookie = cookieStr;

    var colorSchemeCookie = 'tma_color_scheme=' + (tg.colorScheme || 'dark') + '; path=/; max-age=86400;';
    if (window.location.protocol === 'https:') colorSchemeCookie += ' SameSite=None; Secure;';
    document.cookie = colorSchemeCookie;

    var themeParams = tg.themeParams || {};
    var themeMap = {
        '--tg-theme-bg-color':           themeParams.bg_color || '#FFFFFF',
        '--tg-theme-text-color':         themeParams.text_color || '#000000',
        '--tg-theme-hint-color':         themeParams.hint_color || '#555555',
        '--tg-theme-link-color':         themeParams.link_color || '#2481cc',
        '--tg-theme-button-color':       themeParams.button_color || '#2481cc',
        '--tg-theme-button-text-color':  themeParams.button_text_color || '#ffffff',
        '--tg-theme-secondary-bg-color': themeParams.secondary_bg_color || '#f4f4f0'
    };
    Object.keys(themeMap).forEach(function(key) {
        document.documentElement.style.setProperty(key, themeMap[key]);
    });

    var updateShimmer = function() {
        document.documentElement.style.setProperty(
            '--shimmer-base',
            tg.colorScheme === 'dark' ? '255, 255, 255' : '0, 0, 0'
        );
    };
    updateShimmer();
    tg.onEvent('themeChanged', updateShimmer);

    if (!window.__SERVER_EMBEDDED__) {
        var authUrl = new URL(window.location.href);
        authUrl.searchParams.set('initData', tg.initData);
        fetch(authUrl.toString(), { credentials: 'same-origin', redirect: 'follow' })
            .then(function() { window.location.reload(); })
            .catch(function() {});
        return;
    }

    window.__PLATFORM_BRIDGE__ = tg;

    window.__PLATFORM_BRIDGE_INIT__ = function() {
        document.body.classList.add('is-tma');
        document.title = 'Morf TMA';

        if (window.location.pathname === '/login') {
            if (typeof Turbo !== 'undefined') {
                Turbo.visit('/', { action: 'replace' });
            } else {
                window.location.href = '/';
            }
            return;
        }

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
