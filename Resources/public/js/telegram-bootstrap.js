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
    let tg;
    try {
        tg = await loadTelegramSdk();
    } catch (e) {
        return null;
    }

    if (!tg.initData) return null;

    tg.ready();

    return {
        provider: 'telegram_mini_app',
        assertion: tg.initData,
    };
}
