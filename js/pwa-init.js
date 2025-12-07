/**
 * Dinero PWA Initialization
 * Enregistre le Service Worker et gère l'installation de la PWA
 */

class PWAManager {
    constructor() {
        this.deferredPrompt = null;
        this.isStandalone = false;

        this.init();
    }

    init() {
        // Vérifier si l'app est déjà installée
        this.checkIfStandalone();

        // Enregistrer le Service Worker
        if ('serviceWorker' in navigator) {
            this.registerServiceWorker();
        }

        // Gérer l'événement d'installation
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallPrompt();
        });

        // Détecter l'installation réussie
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App installée avec succès');
            this.deferredPrompt = null;
            this.hideInstallPrompt();
        });
    }

    /**
     * Vérifier si l'app est en mode standalone
     */
    checkIfStandalone() {
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone ||
            document.referrer.includes('android-app://');

        if (this.isStandalone) {
            console.log('[PWA] App en mode standalone');
            document.body.classList.add('pwa-standalone');
        }
    }

    /**
     * Enregistrer le Service Worker
     */
    async registerServiceWorker() {
        try {
            const registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            });

            console.log('[PWA] Service Worker enregistré:', registration.scope);

            // Vérifier les mises à jour
            registration.addEventListener('updatefound', () => {
                const newWorker = registration.installing;

                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                        this.showUpdateNotification();
                    }
                });
            });

            // Vérifier les mises à jour toutes les heures
            setInterval(() => {
                registration.update();
            }, 60 * 60 * 1000);

        } catch (error) {
            console.error('[PWA] Erreur enregistrement Service Worker:', error);
        }
    }

    /**
     * Afficher le prompt d'installation
     */
    showInstallPrompt() {
        if (this.isStandalone) return;

        const promptHTML = `
            <div class="pwa-install-prompt" id="installPrompt">
                <div>
                    <strong>Installer Dinero</strong>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #64748b;">
                        Accédez rapidement à l'app depuis votre écran d'accueil
                    </p>
                </div>
                <div style="display: flex; gap: 8px;">
                    <button onclick="pwaManager.install()">Installer</button>
                    <button class="close-btn" onclick="pwaManager.hideInstallPrompt()">
                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        `;

        // Ajouter au DOM si pas déjà présent
        if (!document.getElementById('installPrompt')) {
            document.body.insertAdjacentHTML('beforeend', promptHTML);
        }
    }

    /**
     * Masquer le prompt d'installation
     */
    hideInstallPrompt() {
        const prompt = document.getElementById('installPrompt');
        if (prompt) {
            prompt.remove();
        }
    }

    /**
     * Installer la PWA
     */
    async install() {
        if (!this.deferredPrompt) {
            console.log('[PWA] Prompt d\'installation non disponible');
            return;
        }

        this.deferredPrompt.prompt();

        const { outcome } = await this.deferredPrompt.userChoice;
        console.log('[PWA] Choix utilisateur:', outcome);

        this.deferredPrompt = null;
        this.hideInstallPrompt();
    }

    /**
     * Afficher la notification de mise à jour
     */
    showUpdateNotification() {
        const updateHTML = `
            <div class="update-badge" id="updateBadge" onclick="pwaManager.applyUpdate()">
                <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: inline; margin-right: 8px;">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Mise à jour disponible - Cliquez pour actualiser
            </div>
        `;

        if (!document.getElementById('updateBadge')) {
            document.body.insertAdjacentHTML('beforeend', updateHTML);
        }
    }

    /**
     * Appliquer la mise à jour
     */
    async applyUpdate() {
        const registration = await navigator.serviceWorker.getRegistration();

        if (registration && registration.waiting) {
            registration.waiting.postMessage({ type: 'SKIP_WAITING' });

            navigator.serviceWorker.addEventListener('controllerchange', () => {
                window.location.reload();
            });
        }
    }

    /**
     * Vider le cache et recharger
     */
    async clearCacheAndReload() {
        if ('serviceWorker' in navigator) {
            const registration = await navigator.serviceWorker.getRegistration();
            if (registration) {
                registration.active.postMessage({ type: 'CLEAR_CACHE' });
            }
        }

        localStorage.clear();
        window.location.reload();
    }
}

// Initialiser le gestionnaire PWA
const pwaManager = new PWAManager();

// Exposer globalement
window.pwaManager = pwaManager;

// Log pour debug
console.log('[PWA] Manager initialisé');
console.log('[PWA] Service Worker supporté:', 'serviceWorker' in navigator);
console.log('[PWA] Mode standalone:', pwaManager.isStandalone);
