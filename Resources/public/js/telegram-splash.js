export function injectAndHideSplash() {
    const splashTemplate = document.getElementById('splash-template-telegram');
    if (splashTemplate && !document.getElementById('morf-splash')) {
        document.body.appendChild(splashTemplate.content.cloneNode(true));
        
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
