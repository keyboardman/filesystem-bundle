# Démo Keyboardman Filesystem Bundle

Projet Symfony complet qui utilise le bundle (via dépendance path). Aucun ajout de `league/flysystem` dans ce projet : le bundle fournit la dépendance.

## Installation

cd**Important :** Vous devez installer les dépendances avant de lancer le serveur :

```bash
cd demo
composer install
```

Si le bundle n'est pas trouvé, assurez-vous que le repository path est correctement configuré dans `composer.json` et que le bundle parent est bien présent dans le répertoire parent (`..`).

## Lancer l'application

**Important :** Le serveur doit être lancé depuis le répertoire `demo/` :

```bash
cd demo
symfony server:start
# ou
php -S localhost:8000 -t public
```

Le serveur sera accessible sur `http://localhost:8000/`

## Configuration

### Variables d'environnement

Le chemin de stockage local est configuré dans le fichier `.env` :

```bash
STORAGE_PATH=%kernel.project_dir%/var/storage
```

Vous pouvez modifier cette valeur pour utiliser un autre répertoire. Par exemple, pour utiliser `/tmp/filesystem-demo-storage` :

```bash
STORAGE_PATH=/tmp/filesystem-demo-storage
```

## Page de test interactive

Une **page de démo interactive** est disponible à la racine pour tester toutes les fonctionnalités :

**http://localhost:8000/**

Cette page permet de :
- 📤 **Uploader** des fichiers (images, audio, vidéo)
- 📁 **Créer** des dossiers
- 📋 **Lister** les fichiers et dossiers
- ✏️ **Renommer** des fichiers ou dossiers
- 🗑️ **Supprimer** des fichiers ou dossiers

## API REST

Vous pouvez aussi tester directement les endpoints de l'API :

- **API list** : http://localhost:8000/api/filesystem/list?filesystem=default
- **Autres endpoints** : upload, rename, move, delete, create-directory (voir la documentation du bundle)

Le stockage par défaut utilise un répertoire local : `var/storage/`.

## Tester S3 (MinIO) en plus du local

À la racine du bundle, un `docker-compose.yml` lance MinIO (stockage S3-compatible). Vous pouvez ainsi tester **Local** et **S3** depuis la même page démo.

1. **Démarrer MinIO** (à la racine du bundle) :
   ```bash
   docker compose up -d
   ```
   - API S3 : http://localhost:9000  
   - Console MinIO : http://localhost:9001 (minioadmin / minioadmin)

2. **Configurer la démo** : les variables `MINIO_*` sont déjà dans `demo/.env`. Ajustez si besoin (endpoint, identifiants, bucket).

3. **Lancer la démo** depuis `demo/` puis ouvrir http://localhost:8000/. Dans le sélecteur **Stockage**, choisir **Local (default)** ou **S3 (MinIO)** pour comparer les deux.
