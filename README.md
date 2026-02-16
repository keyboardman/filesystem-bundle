# Keyboardman Filesystem Bundle

Bundle Symfony réutilisable exposant une **API File Storage** (upload, renommer, déplacer, supprimer) avec [Gaufrette](https://knplabs.github.io/Gaufrette/installation.html), support multi-filesystems et option de **cache** (pattern decorator Gaufrette).

## Installation

Ce bundle n’est pas publié sur Packagist. Installez-le depuis GitHub.

**1. Déclarez le dépôt dans le `composer.json` de votre projet :**

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/keyboardman/filesystem-bundle"
        }
    ]
}
```

**2. Installez le bundle :**

```bash
composer require keyboardman/filesystem-bundle
```

Pour une branche ou un tag précis (ex. `dev-main`) :

```bash
composer require keyboardman/filesystem-bundle:dev-main
```

**3. Enregistrez le bundle dans `config/bundles.php` :**

```php
return [
    // ...
    Keyboardman\FilesystemBundle\KeyboardmanFilesystemBundle::class => ['all' => true],
];
```

## Configuration

### Plusieurs filesystems

Chaque filesystem est **nommé** et mappé à un adapter Gaufrette (service Symfony).

Exemple avec un adapter local (à définir en service) :

```yaml
# config/packages/keyboardman_filesystem.yaml
keyboardman_filesystem:
    filesystems:
        default:
            adapter: my_app.gaufrette_adapter.local
        documents:
            adapter: my_app.gaufrette_adapter.documents
```

Vous devez définir les services adapters (ex. `Gaufrette\Adapter\Local`, `Gaufrette\Adapter\InMemory`, ou un adapter S3/FTP, etc.) et référencer leur id dans `adapter`.

Exemple de définition d’un adapter local :

```yaml
services:
    my_app.gaufrette_adapter.local:
        class: Gaufrette\Adapter\Local
        arguments:
            - '%kernel.project_dir%/var/storage'
```

### Exemple : Amazon S3

Installez le SDK AWS pour PHP :

```bash
composer require aws/aws-sdk-php
```

Puis définissez le client S3 et l’adapter Gaufrette (utilisez des variables d’environnement pour les identifiants) :

```yaml
# config/packages/keyboardman_filesystem.yaml
keyboardman_filesystem:
    filesystems:
        s3_uploads:
            adapter: my_app.gaufrette_adapter.s3

services:
    my_app.s3_client:
        class: Aws\S3\S3Client
        arguments:
            -
                version: 'latest'
                region: '%env(AWS_REGION)%'
                credentials:
                    key: '%env(AWS_ACCESS_KEY_ID)%'
                    secret: '%env(AWS_SECRET_ACCESS_KEY)%'

    my_app.gaufrette_adapter.s3:
        class: Gaufrette\Adapter\AwsS3
        arguments:
            - '@my_app.s3_client'
            - '%env(AWS_S3_BUCKET)%'
            - { directory: 'uploads', acl: 'private' }
            - true   # detectContentType
```

### Exemple : FTP

L’adapter FTP utilise l’extension PHP `ftp`. Définissez un service avec le répertoire distant, l’hôte et les options (port, utilisateur, mot de passe, etc.) :

```yaml
# config/packages/keyboardman_filesystem.yaml
keyboardman_filesystem:
    filesystems:
        ftp_storage:
            adapter: my_app.gaufrette_adapter.ftp

services:
    my_app.gaufrette_adapter.ftp:
        class: Gaufrette\Adapter\Ftp
        arguments:
            - '/remote/directory'   # répertoire sur le serveur FTP
            - '%env(FTP_HOST)%'
            - { username: '%env(FTP_USER)%', password: '%env(FTP_PASSWORD)%', port: 21, passive: true, create: true }
```

Options utiles pour `Ftp` : `port`, `username`, `password`, `passive`, `create` (créer les répertoires si besoin), `ssl` (connexion FTPS), `timeout`, `utf8`.

### Restrictions d’upload (api)

Par défaut, l’API n’accepte que les fichiers **audio**, **vidéo** et **image** (validation par extension), et applique une **taille maximale par fichier** (10 MiB par défaut).

```yaml
keyboardman_filesystem:
    api:
        allowed_types: ['image', 'audio', 'video']   # optionnel, défaut : image, audio, video
        max_upload_size: 10_485_760                   # bytes, ou notation : 10M, 50M
```

- **allowed_types** : types autorisés (`image`, `audio`, `video`). Extensions alignées avec le filtrage de la route list.
- **max_upload_size** : taille max en bytes, ou en notation lisible (`10M`, `50M`, `1G`).
- En cas de non-conformité, l’API retourne **400** avec `{"error": "File type not allowed"}` ou `{"error": "File size exceeds limit"}`.

### Option cache (pattern Gaufrette)

Pour un filesystem lent (ex. FTP), vous pouvez activer le **cache** en utilisant le mécanisme Gaufrette : un adapter de **cache** + un adapter **source**. Voir [Gaufrette – Caching](https://knplabs.github.io/Gaufrette/caching.html).

```yaml
keyboardman_filesystem:
    filesystems:
        ftp_cached:
            adapter: placeholder   # ignoré quand cache est activé
            cache:
                enabled: true
                source: my_app.ftp_adapter
                cache: my_app.cache_adapter
```

Vous devez définir les services `source` (l’adapter à mettre en cache) et `cache` (l’adapter qui stocke le cache, ex. local ou APC). Si la classe `Gaufrette\Adapter\Cache` n’est pas disponible dans votre version de Gaufrette, vous pouvez envelopper vous‑même l’adapter source avec un decorator et passer ce service en `adapter` (sans utiliser la clé `cache`).

**Limites** : l’invalidation du cache (TTL, invalidation manuelle) dépend du comportement du decorator Gaufrette ; consultez la doc Gaufrette.

## Routes / API HTTP

Le bundle enregistre les routes sous le préfixe `/api/filesystem` (à inclure dans votre app). Pour charger les routes du bundle :

```yaml
# config/routes.yaml
keyboardman_filesystem_api:
    resource: '@KeyboardmanFilesystemBundle/Resources/config/routes.yaml'
```

Ou en pointant directement vers les contrôleurs :

```yaml
keyboardman_filesystem_api:
    resource: '../vendor/keyboardman/filesystem-bundle/src/Controller/'
    type: attribute
    prefix: /api/filesystem
```

### Endpoints

| Méthode | Chemin | Description |
|--------|--------|-------------|
| GET | `/api/filesystem/list` | Lister fichiers et dossiers du répertoire courant uniquement (`filesystem`, optionnel `path`, `type`, `sort`) |
| POST | `/api/filesystem/upload` | Upload d’un fichier (`file`, `filesystem`, optionnel `key`) |
| POST | `/api/filesystem/upload-multiple` | Upload de plusieurs fichiers (`files[]`, `filesystem`) |
| POST | `/api/filesystem/rename` | Renommer (`filesystem`, `source`, `target`) |
| POST | `/api/filesystem/move` | Déplacer (`filesystem`, `source`, `target`) |
| POST | `/api/filesystem/create-directory` | Créer un dossier (`filesystem`, `path`) — à la racine ou plus loin (ex. `parent/enfant`) |
| POST | `/api/filesystem/delete` | Supprimer un fichier ou un dossier vide (`filesystem`, `path`) |

Réponses : JSON ; codes HTTP standards (200, 201, 204, 400, 404, 409).

#### Route delete (POST)

- **Paramètres** : `filesystem` (défaut : `default`), `path` (fichier ou dossier à supprimer).
- Supprime un **fichier** ou un **dossier** (créé via create-directory). Un dossier n’est supprimable que s’il est **vide** (aucun fichier ni sous-dossier).
- **204** No Content en cas de succès. **404** si fichier ou dossier introuvable. **409** Conflict si le dossier n’est pas vide (`{"error": "Directory is not empty"}`).

#### Route create-directory (POST)

- **Paramètres** : `filesystem` (défaut : `default`), `path` (chemin du dossier, ex. `nouveau` ou `parent/sous-dossier`).
- **Réponse** : `{ "filesystem": "...", "path": "chemin/normalise" }` (201 Created).
- Création à la racine ou en profondeur ; le chemin ne doit pas être vide ni contenir `..`. Si un fichier existe déjà à ce chemin → 409 Conflict.

#### Route list (GET)

- **Paramètres** : `filesystem` (défaut : `default`), `path` (optionnel : répertoire à lister, vide = racine), `type` (optionnel : `audio`, `video`, `image`), `sort` (optionnel : `asc`, `desc`).
- **Réponse** : `{ "filesystem": "...", "paths": ["chemin1", "chemin2", ...] }`.
- **Un seul niveau** : seuls les fichiers et dossiers **directs** du répertoire demandé sont retournés (pas de listing récursif des sous-dossiers), pour de meilleures performances.
- Les **fichiers masqués** (nom commençant par `.`) ne sont jamais inclus.
- **Types par extension** :  
  - `image` : jpg, jpeg, png, gif, webp, svg, bmp, ico  
  - `audio` : mp3, wav, ogg, m4a, aac, flac  
  - `video` : mp4, webm, avi, mov, mkv, m4v  
- **Exemples** : `GET /api/filesystem/list?filesystem=default`, `GET /api/filesystem/list?filesystem=default&path=documents&type=image&sort=asc`

### Page de démonstration

Une route **GET** `/api/filesystem/demo` affiche une page HTML pour tester l’API (formulaires upload, rename, move, delete). À réserver à l’environnement de dev/démo ; protégez l’API en production (firewall, authentification).

## Sécurité

En production, protégez les routes de l’API (firewall Symfony, authentification) et ne publiez pas la page démo. Le bundle ne gère pas les permissions métier.

## Tests

```bash
./vendor/bin/phpunit
```

## Docker

Construire l’image (à la racine du dépôt) :

```bash
docker build -t keyboardman/filesystem-bundle .
```

**Lancer la démo** (serveur sur le port 8000) :

```bash
docker run --rm -p 8000:8000 keyboardman/filesystem-bundle
```

Puis ouvrir http://localhost:8000/api/filesystem/demo dans le navigateur.

**Lancer les tests** :

```bash
docker run --rm keyboardman/filesystem-bundle ./vendor/bin/phpunit
```

## Licence

MIT.
