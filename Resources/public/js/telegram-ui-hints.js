/**
 * UI-hints module for the Telegram Mini App platform adapter.
 *
 * Contract (see PlatformAdapterInterface::getUiHintsModulePath()): export a
 * apply(ctx) called once, only for a session already authenticated via this
 * adapter. Purely presentational — never involved in login. Core dispatches
 * a generic "platform:navigate" event on every Turbo navigation; what (if
 * anything) a hints module does with it is entirely its own business.
 */

function applyThemeVars(themeParams, colorScheme) {
    const themeMap = {
        '--tg-theme-bg-color': themeParams.bg_color || '#FFFFFF',
        '--tg-theme-text-color': themeParams.text_color || '#000000',
        '--tg-theme-hint-color': themeParams.hint_color || '#555555',
        '--tg-theme-link-color': themeParams.link_color || '#2481cc',
        '--tg-theme-button-color': themeParams.button_color || '#2481cc',
        '--tg-theme-button-text-color': themeParams.button_text_color || '#ffffff',
        '--tg-theme-secondary-bg-color': themeParams.secondary_bg_color || '#f4f4f0',
    };
    Object.keys(themeMap).forEach((key) => {
        document.documentElement.style.setProperty(key, themeMap[key]);
    });
    document.documentElement.style.setProperty(
        '--shimmer-base',
        colorScheme === 'dark' ? '255, 255, 255' : '0, 0, 0',
    );

    // Cosmetic-only cookie, read back by TelegramPlatformAdapter::getUiContext()
    // purely to avoid a flash of the wrong theme on the next SSR render. Not
    // used for auth or platform detection.
    let cookie = 'tma_color_scheme=' + colorScheme + '; path=/; max-age=86400;';
    if (window.location.protocol === 'https:') cookie += ' SameSite=None; Secure;';
    document.cookie = cookie;
}

const ROOT_ROUTES = ['app_index', 'app_categories', 'app_authors', 'app_notifications', 'app_profile', 'app_login'];

export function apply(ctx) {
    const tg = window.Telegram && window.Telegram.WebApp;
    if (!tg) return;

    document.body.classList.add('is-tma');
    document.title = 'Morf TMA';

    applyThemeVars(tg.themeParams || {}, tg.colorScheme || 'dark');
    tg.onEvent('themeChanged', () => applyThemeVars(tg.themeParams || {}, tg.colorScheme || 'dark'));

    if (window.location.pathname === '/login') {
        if (typeof Turbo !== 'undefined') {
            Turbo.visit('/', { action: 'replace' });
        } else {
            window.location.href = '/';
        }
        return;
    }

    let backButtonAssigned = false;

    window.addEventListener('platform:navigate', (e) => {
        const isRoot = ROOT_ROUTES.includes(e.detail.route);

        if (isRoot) {
            tg.BackButton.hide();
        } else {
            tg.BackButton.show();
            if (!backButtonAssigned) {
                tg.BackButton.onClick(() => window.history.back());
                backButtonAssigned = true;
            }
        }
    });
}
