/**
 * Dinero Sync Manager
 * Gère la synchronisation des données entre le serveur et le stockage local
 */

class SyncManager {
    constructor() {
        this.syncInProgress = false;
        this.lastSyncTime = localStorage.getItem('lastSync');
        this.syncInterval = 5 * 60 * 1000; // 5 minutes
        this.autoSyncTimer = null;

        this.init();
    }

    init() {
        // Écouter les changements de statut réseau
        window.addEventListener('online', () => this.onOnline());
        window.addEventListener('offline', () => this.onOffline());

        // Écouter les messages du Service Worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data.type === 'SYNC_COMPLETE') {
                    this.handleSyncComplete(event.data.data);
                }
            });
        }

        // Démarrer la synchronisation automatique si en ligne
        if (navigator.onLine) {
            this.startAutoSync();
        }

        // Afficher le statut initial
        this.updateSyncStatus();
    }

    /**
     * Synchronisation manuelle
     */
    async syncNow() {
        if (this.syncInProgress) {
            console.log('[Sync] Synchronisation déjà en cours');
            return;
        }

        if (!navigator.onLine) {
            this.showNotification('Vous êtes hors ligne', 'error');
            return;
        }

        this.syncInProgress = true;
        this.updateSyncButton(true);

        try {
            // Récupérer toutes les données
            const [accounts, operations, banks, dashboard] = await Promise.all([
                this.fetchData('/api/accounts'),
                this.fetchData('/api/operations'),
                this.fetchData('/api/banks'),
                this.fetchData('/api/dashboard')
            ]);

            // Stocker dans localStorage
            const data = {
                accounts: accounts,
                operations: operations,
                banks: banks,
                dashboard: dashboard,
                timestamp: new Date().toISOString()
            };

            localStorage.setItem('dineroData', JSON.stringify(data));
            localStorage.setItem('lastSync', data.timestamp);
            this.lastSyncTime = data.timestamp;

            // Enregistrer pour background sync si disponible
            if ('serviceWorker' in navigator && 'sync' in self.registration) {
                const registration = await navigator.serviceWorker.ready;
                await registration.sync.register('sync-data');
            }

            this.showNotification('Synchronisation réussie', 'success');
            this.updateSyncStatus();

        } catch (error) {
            console.error('[Sync] Erreur:', error);
            this.showNotification('Erreur de synchronisation', 'error');
        } finally {
            this.syncInProgress = false;
            this.updateSyncButton(false);
        }
    }

    /**
     * Récupérer les données depuis l'API
     */
    async fetchData(endpoint) {
        const response = await fetch(endpoint, {
            headers: {
                'Accept': 'application/json'
            }
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        return await response.json();
    }

    /**
     * Démarrer la synchronisation automatique
     */
    startAutoSync() {
        if (this.autoSyncTimer) {
            clearInterval(this.autoSyncTimer);
        }

        this.autoSyncTimer = setInterval(() => {
            if (navigator.onLine && !this.syncInProgress) {
                console.log('[Sync] Synchronisation automatique');
                this.syncNow();
            }
        }, this.syncInterval);
    }

    /**
     * Arrêter la synchronisation automatique
     */
    stopAutoSync() {
        if (this.autoSyncTimer) {
            clearInterval(this.autoSyncTimer);
            this.autoSyncTimer = null;
        }
    }

    /**
     * Gérer le retour en ligne
     */
    onOnline() {
        console.log('[Sync] Connexion rétablie');
        this.showNotification('Connexion rétablie', 'success');
        this.updateNetworkStatus(true);
        this.startAutoSync();
        this.syncNow();
    }

    /**
     * Gérer la perte de connexion
     */
    onOffline() {
        console.log('[Sync] Connexion perdue');
        this.showNotification('Mode hors ligne', 'warning');
        this.updateNetworkStatus(false);
        this.stopAutoSync();
    }

    /**
     * Gérer la fin de synchronisation background
     */
    handleSyncComplete(data) {
        console.log('[Sync] Background sync terminé', data);
        if (data) {
            localStorage.setItem('dineroData', JSON.stringify(data));
            localStorage.setItem('lastSync', new Date().toISOString());
            this.updateSyncStatus();
        }
    }

    /**
     * Mettre à jour le bouton de synchronisation
     */
    updateSyncButton(isLoading) {
        const syncButton = document.getElementById('syncButton');
        const syncIcon = document.getElementById('syncIcon');

        if (syncButton) {
            syncButton.disabled = isLoading;
            if (isLoading) {
                syncIcon?.classList.add('animate-spin');
            } else {
                syncIcon?.classList.remove('animate-spin');
            }
        }
    }

    /**
     * Mettre à jour le statut de synchronisation
     */
    updateSyncStatus() {
        const statusElement = document.getElementById('syncStatus');
        if (!statusElement) return;

        if (this.lastSyncTime) {
            const date = new Date(this.lastSyncTime);
            const now = new Date();
            const diff = now - date;
            const minutes = Math.floor(diff / 60000);

            let timeText;
            if (minutes < 1) {
                timeText = 'À l\'instant';
            } else if (minutes < 60) {
                timeText = `Il y a ${minutes} min`;
            } else {
                const hours = Math.floor(minutes / 60);
                timeText = `Il y a ${hours}h`;
            }

            statusElement.textContent = `Dernière sync: ${timeText}`;
        } else {
            statusElement.textContent = 'Jamais synchronisé';
        }
    }

    /**
     * Mettre à jour le statut réseau
     */
    updateNetworkStatus(isOnline) {
        const statusElement = document.getElementById('networkStatus');
        if (!statusElement) return;

        if (isOnline) {
            statusElement.className = 'network-status online';
            statusElement.innerHTML = '<span class="status-dot"></span> En ligne';
        } else {
            statusElement.className = 'network-status offline';
            statusElement.innerHTML = '<span class="status-dot"></span> Hors ligne';
        }
    }

    /**
     * Afficher une notification
     */
    showNotification(message, type = 'info') {
        // Créer l'élément de notification
        const notification = document.createElement('div');
        notification.className = `sync-notification ${type}`;
        notification.textContent = message;

        // Ajouter au DOM
        document.body.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => notification.classList.add('show'), 10);

        // Retirer après 3 secondes
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

    /**
     * Obtenir les données en cache
     */
    getCachedData() {
        const data = localStorage.getItem('dineroData');
        return data ? JSON.parse(data) : null;
    }

    /**
     * Vider le cache
     */
    clearCache() {
        localStorage.removeItem('dineroData');
        localStorage.removeItem('lastSync');
        this.lastSyncTime = null;
        this.updateSyncStatus();
        this.showNotification('Cache vidé', 'success');
    }
}

// Initialiser le gestionnaire de synchronisation
const syncManager = new SyncManager();

// Exposer globalement pour utilisation dans les templates
window.syncManager = syncManager;
