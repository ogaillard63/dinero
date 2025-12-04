# Dinero

Application de gestion bancaire en PHP/MySQL/Twig avec TailwindCSS.

## Installation

1.  Assurez-vous d'avoir PHP et Composer installés.
2.  Installez les dépendances :
    ```bash
    composer install
    ```
3.  Configurez votre base de données :
    - Créez une base de données MySQL (ex: `dinero`).
    - Importez le fichier `database.sql` pour créer les tables et l'utilisateur par défaut.
    - Copiez `.env` et ajustez les paramètres de connexion si nécessaire.

## Utilisation

1.  Accédez à l'application via votre serveur web (ex: `http://dinero.test` si vous utilisez Laragon).
2.  Connectez-vous avec les identifiants par défaut :
    - Utilisateur : `admin`
    - Mot de passe : `password`

## Fonctionnalités

-   **Dashboard** : Vue d'ensemble du solde et des dernières transactions.
-   **Banques** : Liste des banques et de leurs comptes.
-   **Comptes** : Détail des mouvements pour un compte spécifique.
-   **Import** : Page pour importer des transactions (fonctionnalité à implémenter).

## Structure

-   `src/` : Code source PHP (Contrôleurs, Modèles).
-   `templates/` : Vues Twig.
-   `public/` : Point d'entrée web (`index.php`).
-   `config/` : Configuration.
