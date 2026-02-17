# Change: Remplacer Gaufrette par Flysystem (League)

## Why

Gaufrette est peu maintenu ; [Flysystem](https://flysystem.thephpleague.com/docs/) (The PHP League) est la référence actuelle pour l’abstraction de stockage de fichiers en PHP, mieux maintenu et documenté. En plus, le bundle doit fournir lui-même la dépendance Flysystem afin que les projets qui l’utilisent n’aient pas à importer une seconde fois l’API (éviter conflits de versions et simplifier l’intégration). Enfin, un projet démo Symfony dans un répertoire `demo/` permettra de valider et documenter l’usage du bundle.

## What Changes

- Remplacer l’API **Gaufrette** par l’API **Flysystem** (League) pour toutes les opérations filesystem (read, write, delete, move, list, createDirectory, has).
- Le bundle déclare **league/flysystem** en `require` ; l’application consommatrice n’a pas besoin d’ajouter cette dépendance.
- Adapter la configuration : chaque filesystem est mappé à un **adapter Flysystem** (service Symfony implémentant `League\Flysystem\FilesystemAdapter`), au lieu d’un adapter Gaufrette.
- **BREAKING** : la configuration YAML (clé `adapter`, option `cache`) change de sémantique (ids de services Flysystem au lieu de Gaufrette).
- Conserver le même comportement fonctionnel de l’API HTTP et du service `FileStorage` (upload, rename, move, delete, list, createDirectory), en s’appuyant sur Flysystem en interne.
- Option cache : documenter ou implémenter un mécanisme équivalent (Flysystem v3 n’utilise plus le même package que le cached-adapter v1 ; à traiter dans le design).
- Ajouter un répertoire **demo/** : projet Symfony minimal qui utilise le bundle (config, routes, exemple d’appel) pour démo et tests d’intégration.

## Impact

- Affected specs: filestorage-api (comportement inchangé côté API HTTP, abstraction backend remplacée).
- Affected code: `composer.json`, `FileStorage`, `KeyboardmanFilesystemExtension`, `Configuration`, tests (config Gaufrette → Flysystem), README, et nouveau répertoire `demo/`.
