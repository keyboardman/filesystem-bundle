# Docker Compose – MinIO (S3)

Ce projet fournit un `docker-compose.yml` avec **MinIO** pour tester le stockage S3 en plus du stockage local.

## Démarrer MinIO

À la racine du projet :

```bash
docker compose up -d
```

- **API S3** : http://localhost:9000  
- **Console web** : http://localhost:9001  
- Identifiants par défaut : `minioadmin` / `minioadmin`

Un bucket `demo` est créé automatiquement au premier démarrage (service `minio-init`).

## Variables d’environnement

Optionnel, dans un fichier `.env` à la racine ou en export :

| Variable | Défaut | Description |
|----------|--------|-------------|
| `MINIO_ROOT_USER` | minioadmin | Utilisateur racine MinIO |
| `MINIO_ROOT_PASSWORD` | minioadmin | Mot de passe MinIO |

## Utiliser S3 dans la démo

1. Lancer MinIO : `docker compose up -d`
2. Depuis `demo/` : `composer install` (inclut `league/flysystem-aws-s3-v3`), puis lancer le serveur
3. Ouvrir la page démo et choisir **S3 (MinIO)** dans le sélecteur « Stockage »

Les variables MinIO pour la démo sont dans `demo/.env` (`MINIO_ENDPOINT`, `MINIO_ACCESS_KEY`, `MINIO_SECRET_KEY`, `MINIO_BUCKET`).

## Arrêter

```bash
docker compose down
```

Les données MinIO sont conservées dans le volume `minio_data` (supprimer avec `docker compose down -v` pour repartir de zéro).
