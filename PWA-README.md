# 📱 Dinero PWA - Documentation

## Vue d'ensemble

Dinero est maintenant une **Progressive Web App (PWA)** complète, offrant :
- ✅ Installation sur mobile et desktop
- ✅ Fonctionnement hors-ligne
- ✅ Synchronisation automatique des données
- ✅ Notifications et mises à jour automatiques
- ✅ Performance optimisée avec mise en cache intelligente

---

## 🚀 Fonctionnalités PWA

### 1. Installation

L'application peut être installée sur :
- **Android** : Via Chrome, Edge, Samsung Internet
- **iOS** : Via Safari (Ajouter à l'écran d'accueil)
- **Desktop** : Via Chrome, Edge (Windows/Mac/Linux)

**Prompt d'installation automatique** :
- Apparaît après quelques visites
- Peut être déclenché manuellement via le menu du navigateur

### 2. Mode Hors-Ligne

**Stratégies de cache** :
- **Assets statiques** (CSS, JS, images) : Cache First
- **Pages HTML** : Stale While Revalidate
- **API** : Network First avec fallback sur cache

**Page offline personnalisée** :
- Affiche les données en cache
- Indique la dernière synchronisation
- Détecte automatiquement le retour en ligne

### 3. Synchronisation

**Automatique** :
- Toutes les 5 minutes quand en ligne
- Au retour de connexion
- Via Background Sync API

**Manuelle** :
- Bouton "Synchroniser" dans la sidebar
- Indicateur visuel de progression
- Notifications de succès/erreur

**Données synchronisées** :
- Comptes et soldes
- Opérations récentes (100 dernières)
- Banques
- Statistiques dashboard

---

## 📁 Structure des Fichiers

```
public/
├── manifest.json           # Configuration PWA
├── sw.js                   # Service Worker
├── offline.html            # Page hors-ligne
├── css/
│   ├── styles.css          # Styles principaux
│   └── pwa.css             # Styles PWA (notifications, statuts)
├── js/
│   ├── pwa-init.js         # Initialisation PWA
│   └── sync.js             # Gestionnaire de synchronisation
└── icons/
    ├── icon-*.png          # Icônes PWA (toutes tailles)
    └── README.md           # Guide génération icônes

src/Controllers/
└── ApiController.php       # Endpoints API REST

templates/
└── layout.twig             # Template principal (avec meta PWA)
```

---

## 🔧 Configuration

### Manifest (manifest.json)

```json
{
  "name": "Dinero - Gestion Financière",
  "short_name": "Dinero",
  "theme_color": "#0B508C",
  "background_color": "#ffffff",
  "display": "standalone",
  "scope": "/",
  "start_url": "/"
}
```

**Personnalisation** :
- `theme_color` : Couleur de la barre d'état (actuellement bleu #0B508C)
- `background_color` : Couleur du splash screen
- `shortcuts` : Raccourcis vers Dashboard, Opérations, Banques

### Service Worker (sw.js)

**Version du cache** :
```javascript
const CACHE_VERSION = 'dinero-v1.0.0';
```

⚠️ **Important** : Incrémentez cette version après chaque modification majeure pour forcer la mise à jour du cache.

**Assets en cache** :
```javascript
const STATIC_ASSETS = [
    '/',
    '/dashboard',
    '/operations',
    '/banks',
    '/css/styles.css',
    '/offline.html',
    // CDN externes
];
```

---

## 🌐 API Endpoints

Tous les endpoints retournent du JSON avec la structure :
```json
{
  "success": true,
  "data": { ... },
  "timestamp": "2025-12-07T09:00:00+01:00"
}
```

### GET /api/accounts
Retourne tous les comptes actifs avec leurs soldes.

### GET /api/operations
Retourne les opérations récentes.
- **Paramètres** : `limit` (défaut: 100), `offset` (défaut: 0)

### GET /api/banks
Retourne toutes les banques avec leurs comptes.

### GET /api/dashboard
Retourne les statistiques du dashboard.

### GET /api/sync
Endpoint de synchronisation complète (tous les données en une requête).

---

## 💾 Stockage Local

### localStorage

**Clés utilisées** :
- `dineroData` : Données complètes (JSON)
- `lastSync` : Timestamp de la dernière synchronisation

**Structure de dineroData** :
```json
{
  "accounts": [...],
  "operations": [...],
  "banks": [...],
  "dashboard": {...},
  "timestamp": "2025-12-07T09:00:00+01:00"
}
```

### Cache API

**Caches créés** :
- `dinero-v1.0.0-static` : Assets statiques
- `dinero-v1.0.0-dynamic` : Pages HTML
- `dinero-v1.0.0-api` : Réponses API

---

## 🎨 Interface Utilisateur

### Indicateurs PWA

**Statut réseau** :
- 🟢 En ligne : Badge vert
- 🔴 Hors ligne : Badge rouge
- Animation pulse sur le point de statut

**Bouton de synchronisation** :
- Icône de rafraîchissement
- Animation de rotation pendant la sync
- Désactivé pendant la synchronisation

**Notifications** :
- Succès : Vert
- Erreur : Rouge
- Avertissement : Orange
- Info : Bleu

**Prompt d'installation** :
- Apparaît en bas de page
- Boutons "Installer" et "Fermer"
- Disparaît après installation

**Badge de mise à jour** :
- Apparaît en haut à droite
- "Mise à jour disponible - Cliquez pour actualiser"
- Recharge la page après clic

---

## 🔄 Cycle de Mise à Jour

1. **Nouvelle version déployée**
2. Service Worker détecte la mise à jour
3. Badge "Mise à jour disponible" s'affiche
4. Utilisateur clique sur le badge
5. Nouveau SW activé
6. Page rechargée automatiquement

**Forcer une mise à jour** :
```javascript
// Dans la console du navigateur
pwaManager.clearCacheAndReload();
```

---

## 🧪 Tests

### Tester l'installation

1. Ouvrez Chrome DevTools (F12)
2. Application → Manifest
3. Vérifiez que toutes les icônes sont présentes
4. Cliquez sur "Add to home screen"

### Tester le mode hors-ligne

1. DevTools → Network
2. Cochez "Offline"
3. Naviguez dans l'app
4. Vérifiez que la page offline s'affiche pour les pages non cachées

### Tester la synchronisation

1. Ouvrez la console
2. Cliquez sur "Synchroniser"
3. Vérifiez les logs `[Sync]`
4. Inspectez localStorage → `dineroData`

### Tester le Service Worker

1. DevTools → Application → Service Workers
2. Vérifiez le statut "activated and is running"
3. Testez "Update on reload"
4. Testez "Skip waiting"

---

## 🐛 Dépannage

### Le Service Worker ne s'enregistre pas

**Vérifications** :
- HTTPS activé (ou localhost)
- Pas d'erreurs dans la console
- Chemin `/sw.js` accessible

**Solution** :
```javascript
// Vérifier le support
if ('serviceWorker' in navigator) {
    console.log('Service Worker supporté');
} else {
    console.log('Service Worker NON supporté');
}
```

### Les icônes ne s'affichent pas

**Vérifications** :
- Fichiers présents dans `/public/icons/`
- Chemins corrects dans `manifest.json`
- Tailles exactes (192x192, 512x512, etc.)

**Générer les icônes** :
Voir `/public/icons/README.md`

### La synchronisation échoue

**Vérifications** :
- Connexion internet active
- Endpoints API accessibles
- Pas d'erreurs CORS
- Session utilisateur valide

**Debug** :
```javascript
// Tester un endpoint
fetch('/api/accounts')
    .then(r => r.json())
    .then(console.log);
```

### Le cache ne se vide pas

**Solution** :
```javascript
// Vider tous les caches
caches.keys().then(names => {
    names.forEach(name => caches.delete(name));
});

// Ou via l'interface
pwaManager.clearCacheAndReload();
```

---

## 📊 Performance

### Métriques cibles

- **First Contentful Paint** : < 1.5s
- **Time to Interactive** : < 3.5s
- **Lighthouse PWA Score** : > 90

### Optimisations appliquées

✅ Service Worker avec stratégies de cache
✅ Lazy loading des transactions (50 par page)
✅ Compression des assets
✅ CDN pour Tailwind et Chart.js
✅ Préchargement des pages principales

---

## 🔒 Sécurité

### Bonnes pratiques

✅ HTTPS obligatoire en production
✅ Validation côté serveur des requêtes API
✅ Authentification requise pour les endpoints
✅ Pas de données sensibles dans le cache (mots de passe, tokens)
✅ Expiration des données en cache

### Middleware d'authentification

Les routes API sont protégées par le middleware d'authentification existant :
```php
$router->before('GET|POST', '/.*', function() {
    // Vérification session
});
```

---

## 🚀 Déploiement

### Checklist pré-déploiement

- [ ] Générer toutes les icônes
- [ ] Tester sur mobile (Android + iOS)
- [ ] Vérifier HTTPS activé
- [ ] Tester installation
- [ ] Tester mode hors-ligne
- [ ] Vérifier les endpoints API
- [ ] Lighthouse audit > 90
- [ ] Tester sur différents navigateurs

### Mise en production

1. **Incrémenter la version du cache** dans `sw.js`
2. **Commit et push** tous les fichiers PWA
3. **Déployer** sur le serveur
4. **Vérifier** que `/manifest.json` et `/sw.js` sont accessibles
5. **Tester** l'installation sur un appareil réel

---

## 📚 Ressources

- [MDN - Progressive Web Apps](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Google - PWA Checklist](https://web.dev/pwa-checklist/)
- [Service Worker API](https://developer.mozilla.org/en-US/docs/Web/API/Service_Worker_API)
- [Web App Manifest](https://developer.mozilla.org/en-US/docs/Web/Manifest)

---

## 📝 Changelog

### v1.0.0 (2025-12-07)

**Ajouté** :
- ✅ Manifest PWA complet
- ✅ Service Worker avec stratégies de cache
- ✅ Page offline personnalisée
- ✅ Synchronisation automatique et manuelle
- ✅ API REST pour les données
- ✅ Indicateurs visuels (statut réseau, sync)
- ✅ Notifications PWA
- ✅ Gestion des mises à jour
- ✅ Support iOS et Android

**À venir** :
- 🔜 Push notifications
- 🔜 Partage de données
- 🔜 Raccourcis clavier
- 🔜 Mode sombre automatique

---

## 👥 Support

Pour toute question ou problème :
1. Consultez la section Dépannage
2. Vérifiez les logs dans la console
3. Testez avec DevTools
4. Contactez l'équipe de développement

---

**Dinero PWA** - Votre gestion financière, partout, tout le temps ! 💰📱
