# Génération des Icônes PWA pour Dinero

## Instructions

Pour générer toutes les icônes nécessaires à la PWA, vous avez plusieurs options :

### Option 1 : Utiliser un outil en ligne (Recommandé)

1. Visitez [PWA Asset Generator](https://www.pwabuilder.com/imageGenerator)
2. Uploadez votre logo (idéalement 512x512px minimum)
3. Téléchargez le pack d'icônes généré
4. Placez les fichiers dans le dossier `public/icons/`

### Option 2 : Utiliser ImageMagick (Ligne de commande)

Si vous avez ImageMagick installé, placez votre logo source (logo.png) dans ce dossier et exécutez :

```bash
# Icônes standards
convert logo.png -resize 72x72 icon-72x72.png
convert logo.png -resize 96x96 icon-96x96.png
convert logo.png -resize 128x128 icon-128x128.png
convert logo.png -resize 144x144 icon-144x144.png
convert logo.png -resize 152x152 icon-152x152.png
convert logo.png -resize 192x192 icon-192x192.png
convert logo.png -resize 384x384 icon-384x384.png
convert logo.png -resize 512x512 icon-512x512.png

# Icônes maskable (avec padding de 10%)
convert logo.png -resize 173x173 -gravity center -extent 192x192 -background "#0B508C" icon-maskable-192x192.png
convert logo.png -resize 461x461 -gravity center -extent 512x512 -background "#0B508C" icon-maskable-512x512.png

# Favicons
convert logo.png -resize 16x16 icon-16x16.png
convert logo.png -resize 32x32 icon-32x32.png
```

### Option 3 : Créer manuellement avec un éditeur d'images

Créez les fichiers suivants avec votre éditeur préféré (Photoshop, GIMP, Figma, etc.) :

**Icônes standards :**
- icon-72x72.png
- icon-96x96.png
- icon-128x128.png
- icon-144x144.png
- icon-152x152.png
- icon-192x192.png
- icon-384x384.png
- icon-512x512.png

**Icônes maskable :**
- icon-maskable-192x192.png (avec 10% de padding)
- icon-maskable-512x512.png (avec 10% de padding)

**Favicons :**
- icon-16x16.png
- icon-32x32.png

## Recommandations de Design

### Logo Principal
- **Taille minimale** : 512x512px
- **Format** : PNG avec transparence
- **Couleur de fond** : Transparent ou #0B508C (bleu Dinero)
- **Design** : Simple et reconnaissable, même en petit

### Icônes Maskable
Les icônes maskable doivent avoir un padding de 10% pour s'adapter aux différentes formes (cercle, carré arrondi, etc.)

**Zone de sécurité** :
- Pour 192x192 : contenu dans un cercle de 173px de diamètre
- Pour 512x512 : contenu dans un cercle de 461px de diamètre

### Couleurs
- **Primaire** : #0B508C (Bleu foncé)
- **Secondaire** : #44C1F2 (Bleu clair)
- **Fond** : Blanc (#FFFFFF) ou transparent

## Vérification

Une fois les icônes créées, vérifiez qu'elles s'affichent correctement :

1. Ouvrez l'application dans Chrome
2. Ouvrez les DevTools (F12)
3. Allez dans l'onglet "Application"
4. Cliquez sur "Manifest" dans la sidebar
5. Vérifiez que toutes les icônes sont listées et s'affichent

## Icônes Temporaires

Si vous n'avez pas encore de logo, vous pouvez utiliser des icônes temporaires générées avec du texte :

```html
<!-- Créer une icône SVG simple -->
<svg width="512" height="512" xmlns="http://www.w3.org/2000/svg">
  <rect width="512" height="512" fill="#0B508C"/>
  <text x="50%" y="50%" font-size="200" fill="white" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-weight="bold">D</text>
</svg>
```

Sauvegardez ce SVG et convertissez-le en PNG aux différentes tailles nécessaires.
