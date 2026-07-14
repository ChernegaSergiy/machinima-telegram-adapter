import { injectAndHideSplash } from './telegram-splash.js';

/**
 * UI-hints module for the Telegram Mini App platform adapter.
 */

function applyThemeVars(themeParams, colorScheme) {
    if (!themeParams) themeParams = {};
    const themeMap = {
        '--tg-theme-bg-color': themeParams.bg_color,
        '--tg-theme-text-color': themeParams.text_color,
        '--tg-theme-hint-color': themeParams.hint_color,
        '--tg-theme-link-color': themeParams.link_color,
        '--tg-theme-button-color': themeParams.button_color,
        '--tg-theme-button-text-color': themeParams.button_text_color,
        '--tg-theme-secondary-bg-color': themeParams.secondary_bg_color,
    };
    Object.keys(themeMap).forEach((key) => {
        if (themeMap[key]) {
            document.documentElement.style.setProperty(key, themeMap[key]);
        }
    });

    if (colorScheme) {
        document.documentElement.style.setProperty(
            '--shimmer-base',
            colorScheme === 'dark' ? '255, 255, 255' : '0, 0, 0',
        );
    }

    const setCookie = (name, value) => {
        let cookie = `${name}=${value}; path=/; max-age=86400;`;
        if (window.location.protocol === 'https:') {
            cookie += ' SameSite=None; Secure;';
        }
        document.cookie = cookie;
    };

    if (colorScheme) {
        setCookie('tma_color_scheme', colorScheme);
    }
    if (Object.keys(themeParams).length > 0) {
        setCookie('tma_theme_params', encodeURIComponent(JSON.stringify(themeParams)));
    }
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

export async function apply(ctx) {
    injectAndHideSplash();

    let cachedParams = {};
    let cachedScheme = null;
    try {
        let cookies = document.cookie.split(';');
        for (let i = 0; i < cookies.length; i++) {
            let cookie = cookies[i].trim();
            if (cookie.startsWith('tma_theme_params=')) {
                cachedParams = JSON.parse(decodeURIComponent(cookie.substring('tma_theme_params='.length)));
            } else if (cookie.startsWith('tma_color_scheme=')) {
                cachedScheme = cookie.substring('tma_color_scheme='.length);
            }
        }
        if (Object.keys(cachedParams).length > 0) {
            applyThemeVars(cachedParams, cachedScheme);
        }
    } catch (e) {}

    let tg;
    try {
        tg = await loadTelegramSdk();
    } catch (e) {
        console.error(e);
        return;
    }

    document.body.classList.add('is-tma');
    document.title = 'Morf TMA';

    // We must call ready() even if we are not bootstrapping, so the Telegram
    // bridge initializes and syncs the theme via events, even if the URL hash is missing.
    tg.ready();

    if (Object.keys(tg.themeParams || {}).length > 0) {
        applyThemeVars(tg.themeParams, tg.colorScheme);
    }
    
    tg.onEvent('themeChanged', () => {
        if (Object.keys(tg.themeParams || {}).length > 0) {
            applyThemeVars(tg.themeParams, tg.colorScheme);
        }
    });

    let backButtonAssigned = false;

    function handleNavigation(route) {
        const isRoot = ROOT_ROUTES.includes(route);

        if (isRoot) {
            tg.BackButton.hide();
        } else {
            tg.BackButton.show();
            if (!backButtonAssigned) {
                tg.BackButton.onClick(() => window.history.back());
                backButtonAssigned = true;
            }
        }
    }

    if (document.body && document.body.dataset.route) {
        handleNavigation(document.body.dataset.route);
    }

    window.addEventListener('platform:navigate', (e) => {
        handleNavigation(e.detail.route);
        
        // Turbo completely replaces the body and sometimes merges the html tag, 
        // which can wipe out our inline styles and classes. We must re-apply them.
        document.body.classList.add('is-tma');
        
        if (Object.keys(tg.themeParams || {}).length > 0) {
            applyThemeVars(tg.themeParams, tg.colorScheme);
        } else if (Object.keys(cachedParams || {}).length > 0) {
            applyThemeVars(cachedParams, cachedScheme);
        }
    });

    if (window.location.pathname === '/login') {
        if (typeof Turbo !== 'undefined') {
            Turbo.visit('/', { action: 'replace' });
        } else {
            window.location.href = '/';
        }
    }
}
