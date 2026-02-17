# Keyboardman Filesystem Bundle

Bundle Symfony réutilisable exposant une **API File Storage** (upload, renommer, déplacer, supprimer) avec [Flysystem](https://flysystem.thephpleague.com/docs/) (The PHP League). Le bundle déclare `league/flysystem` en dépendance : votre projet n'a pas besoin de l'ajouter à son `composer.json`. Support multi-filesystems.

## Installation

Ce bundle n'est pas publié sur Packagist. Installez-le depuis GitHub.

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

Chaque filesystem est **nommé** et mappé à un **adapter Flysystem** (service Symfony implémentant `League\Flysystem\FilesystemAdapter`).

Exemple avec l'adapter Local (fourni par `league/flysystem`, dépendance du bundle) :

```yaml
# config/packages/keyboardman_filesystem.yaml
keyboardman_filesystem:
    filesystems:
        default:
            adapter: my_app.flysystem_adapter.local
        documents:
            adapter: my_app.flysystem_adapter.documents

services:
    my_app.flysystem_adapter.local:
        class: League\Flysystem\Local\LocalFilesystemAdapter
        arguments:
            - '%kernel.project_dir%/var/storage'
```

Pour un stockage en mémoire (tests, démo) :

```yaml
# Nécessite league/flysystem-memory (optionnel, souvent en require-dev)
services:
    my_app.flysystem_adapter.memory:
        class: League\Flysystem\InMemory\InMemoryFilesystemAdapter
```

### Exemple : Amazon S3

Installez l'adapter S3 pour Flysystem :

```bash
composer require league/flysystem-aws-s3-v3
```

Puis définissez le filesystem (utilisez des variables d'environnement pour les identifiants) :

```yaml
keyboardman_filesystem:
    filesystems:
        s3_uploads:
            adapter: my_app.flysystem_adapter.s3

services:
    my_app.flysystem_adapter.s3:
        class: League\Flysystem\AwsS3V3\AwsS3V3Adapter
        arguments:
            - '@my_app.s3_client'
            - '%env(AWS_S3_BUCKET)%'
            - 'uploads/'   # prefix optionnel

    my_app.s3_client:
        class: Aws\S3\S3Client
        arguments:
            -
                version: 'latest'
                region: '%env(AWS_REGION)%'
                credentials:
                    key: '%env(AWS_ACCESS_KEY_ID)%'
                    secret: '%env(AWS_SECRET_ACCESS_KEY)%'
```

### Exemple : FTP

Installez l'adapter FTP : `composer require league/flysystem-ftp`. Puis définissez un service dont la classe implémente `League\Flysystem\FilesystemAdapter` (voir la [doc Flysystem](https://flysystem.thephpleague.com/docs/adapter/ftp/)).

### Restrictions d'upload (api)

Par défaut, l'API n'accepte que les fichiers **audio**, **vidéo** et **image** (validation par extension), et applique une **taille maximale par fichier** (10 MiB par défaut).

```yaml
keyboardman_filesystem:
    api:
        allowed_types: ['image', 'audio', 'video']   # optionnel, défaut : image, audio, video
        max_upload_size: 10_485_760                   # bytes, ou notation : 10M, 50M
```

- **allowed_types** : types autorisés (`image`, `audio`, `video`). Extensions alignées avec le filtrage de la route list.
- **max_upload_size** : taille max en bytes, ou en notation lisible (`10M`, `50M`, `1G`).
- En cas de non-conformité, l'API retourne **400** avec `{"error": "File type not allowed"}` ou `{"error": "File size exceeds limit"}`.

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
| POST | `/api/filesystem/upload` | Upload d'un fichier (`file`, `filesystem`, optionnel `key`) |
| POST | `/api/filesystem/upload-multiple` | Upload de plusieurs fichiers (`files[]`, `filesystem`) |
| POST | `/api/filesystem/rename` | Renommer un fichier ou un dossier (`filesystem`, `source`, `target`) — les dossiers créés via create-directory sont déplacés avec tout leur contenu |
| POST | `/api/filesystem/move` | Déplacer un fichier ou un dossier (`filesystem`, `source`, `target`) — idem pour les dossiers |
| POST | `/api/filesystem/create-directory` | Créer un dossier (`filesystem`, `path`) — à la racine ou plus loin (ex. `parent/enfant`) |
| POST | `/api/filesystem/delete` | Supprimer un fichier ou un dossier vide (`filesystem`, `path`) |

Réponses : JSON ; codes HTTP standards (200, 201, 204, 400, 404, 409).

#### Route delete (POST)

- **Paramètres** : `filesystem` (défaut : `default`), `path` (fichier ou dossier à supprimer).
- Supprime un **fichier** ou un **dossier** (créé via create-directory). Un dossier n'est supprimable que s'il est **vide** (aucun fichier ni sous-dossier).
- **204** No Content en cas de succès. **404** si fichier ou dossier introuvable. **409** Conflict si le dossier n'est pas vide (`{"error": "Directory is not empty"}`).

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

## Sécurité

En production, protégez les routes de l'API (firewall Symfony, authentification). Le bundle ne gère pas les permissions métier.

## Tests

```bash
./vendor/bin/phpunit
```

## Docker

Construire l'image (à la racine du dépôt) :

```bash
docker build -t keyboardman/filesystem-bundle .
```

**Lancer la démo** (serveur sur le port 8000) :

```bash
docker run --rm -p 8000:8000 keyboardman/filesystem-bundle
```

**Lancer les tests** :

```bash
docker run --rm keyboardman/filesystem-bundle ./vendor/bin/phpunit
```

## Projet démo (demo/)

Le répertoire **demo/** à la racine du dépôt contient un projet Symfony minimal qui utilise le bundle. Il permet de valider l'installation et de tester l'API sans ajouter `league/flysystem` dans le projet (le bundle fournit la dépendance).

**Lancer la démo :**

```bash
cd demo
composer install
symfony server:start
# ou : php -S localhost:8000 -t public
```

Le projet démo expose les endpoints de l'API pour tester les fonctionnalités (upload, list, delete, etc.).

## Migration depuis Gaufrette

Si vous migriez depuis une version du bundle basée sur Gaufrette :

- **Configuration** : la clé `adapter` pointe désormais vers un service **Flysystem** (`League\Flysystem\FilesystemAdapter`), plus vers un adapter Gaufrette. Remplacez par exemple `Gaufrette\Adapter\Local` par `League\Flysystem\Local\LocalFilesystemAdapter`, et `Gaufrette\Adapter\InMemory` par `League\Flysystem\InMemory\InMemoryFilesystemAdapter` (package `league/flysystem-memory`).
- **Cache** : l'option de configuration `cache` (source + cache) a été retirée. Pour un cache sur un filesystem lent, envisagez un décorateur ou une solution alignée sur [Flysystem v3](https://flysystem.thephpleague.com/docs/).
- **API HTTP et service FileStorage** : les signatures et réponses restent identiques.

## Licence

MIT.
