export function dismissSplashScreen() {
    let splashElement = document.getElementById('morf-splash');
    
    if (!splashElement) {
        const splashTemplate = document.getElementById('splash-template-telegram');
        if (splashTemplate) {
            document.body.appendChild(splashTemplate.content.cloneNode(true));
            splashElement = document.getElementById('morf-splash');
        }
    }
    
    if (splashElement) {
        const hideSplash = () => {
            const s = document.getElementById('morf-splash');
            if (!s) return;
            s.classList.add('morf-hide');
            setTimeout(() => s.remove(), 650);
        };

        if (document.readyState === 'complete') {
            setTimeout(hideSplash, 1000);
        } else {
            window.addEventListener('load', () => setTimeout(hideSplash, 1000));
        }
    }
}
