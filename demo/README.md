# Démo Keyboardman Filesystem Bundle

Projet Symfony minimal qui utilise le bundle (via dépendance path). Aucun ajout de `league/flysystem` dans ce projet : le bundle fournit la dépendance.

## Installation

```bash
composer install
```

## Lancer l’application

```bash
php -S localhost:8000 -t public
# ou : symfony server:start
```

Puis ouvrir :

- **Page démo** : http://localhost:8000/api/filesystem/demo (formulaires upload, list, delete, etc.)
- **API list** : http://localhost:8000/api/filesystem/list?filesystem=default

Le stockage par défaut utilise un répertoire local : `var/storage/`.
