/**
 * UI-hints module for the Telegram Mini App platform adapter.
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

    let cookie = 'tma_color_scheme=' + colorScheme + '; path=/; max-age=86400;';
    if (window.location.protocol === 'https:') cookie += ' SameSite=None; Secure;';
    document.cookie = cookie;
}

const ROOT_ROUTES = ['app_index', 'app_categories', 'app_authors', 'app_notifications', 'app_profile', 'app_login'];

function loadTelegramSdk() {
    if (window.Telegram && window.Telegram.WebApp) {
        return Promise.resolve(window.Telegram.WebApp);
    }
    return new Promise((resolve, reject) => {
        const script = document.createElement('script');
        script.src = 'https://telegram.org/js/telegram-web-app.js';
        script.onload = () => {
            (window.Telegram && window.Telegram.WebApp)
                ? resolve(window.Telegram.WebApp)
                : reject(new Error('telegram-web-app.js loaded but window.Telegram.WebApp is missing'));
        };
        script.onerror = () => reject(new Error('Failed to load telegram-web-app.js'));
        document.head.appendChild(script);
    });
}

function getThemeParams(tg) {
    let params = tg.themeParams || {};
    if (Object.keys(params).length > 0) {
        sessionStorage.setItem('tma_theme_params', JSON.stringify(params));
        sessionStorage.setItem('tma_color_scheme', tg.colorScheme);
    } else {
        try { 
            params = JSON.parse(sessionStorage.getItem('tma_theme_params')) || {}; 
        } catch(e) {}
    }
    return params;
}

function getColorScheme(tg) {
    let scheme = tg.colorScheme;
    if (scheme) {
        return scheme;
    }
    return sessionStorage.getItem('tma_color_scheme') || 'dark';
}

export async function apply(ctx) {
    let tg;
    try {
        tg = await loadTelegramSdk();
    } catch (e) {
        console.error(e);
        return;
    }

    document.body.classList.add('is-tma');
    document.title = 'Morf TMA';

    const updateTheme = () => {
        applyThemeVars(getThemeParams(tg), getColorScheme(tg));
    };

    updateTheme();
    tg.onEvent('themeChanged', updateTheme);

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
