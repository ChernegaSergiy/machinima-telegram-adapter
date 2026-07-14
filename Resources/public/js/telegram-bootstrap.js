import { dismissSplashScreen } from './telegram-splash.js';

/**
 * Bootstrap module for the Telegram Mini App platform adapter.
 */

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

export async function detect() {
    dismissSplashScreen();

    let tg;
    try {
        tg = await loadTelegramSdk();
    } catch (e) {
        return null;
    }

    if (!tg.initData) return null;

    if (Object.keys(tg.themeParams || {}).length > 0) {
        let cookie = 'tma_theme_params=' + encodeURIComponent(JSON.stringify(tg.themeParams)) + '; path=/; max-age=86400;';
        if (window.location.protocol === 'https:') cookie += ' SameSite=None; Secure;';
        document.cookie = cookie;
    }
    
    tg.ready();

    return {
        provider: 'telegram_mini_app',
        assertion: tg.initData,
    };
}
