# Design: Migration Gaufrette → Flysystem et projet démo

## Context

Le bundle repose aujourd’hui sur Gaufrette (FilesystemMap, Filesystem, Adapter) pour l’abstraction filesystem. Gaufrette est peu maintenu ; Flysystem (League) est la norme actuelle, avec une API claire (FilesystemOperator / FilesystemAdapter), une bonne documentation et des adapters officiels (Local, S3, FTP, etc.). L’objectif est de migrer vers Flysystem tout en gardant l’API métier du bundle (FileStorage, contrôleur HTTP) et en fournissant la dépendance Flysystem via le bundle pour éviter aux applications d’importer une seconde fois l’API.

## Goals / Non-Goals

- **Goals:** Utiliser Flysystem comme seule abstraction filesystem ; le bundle requiert `league/flysystem` et construit les instances ; conserver les cas d’usage actuels (upload, rename, move, delete, list, createDirectory) ; fournir un projet démo Symfony dans `demo/`.
- **Non-Goals:** Ne pas casser l’API HTTP publique du bundle ; ne pas imposer de changement de contrat au service `FileStorage` (signatures publiques stables si possible). Cache optionnel : documenter ou aligner sur les mécanismes Flysystem v3 (pas le legacy cached-adapter v1).

## Decisions

- **Librairie : League Flysystem ^3.0**  
  Utiliser `league/flysystem` en version 3. Les opérations passent par `League\Flysystem\FilesystemOperator` (interface principale) construite à partir d’un `League\Flysystem\FilesystemAdapter`. Le bundle instancie un `League\Flysystem\Filesystem` par filesystem nommé et l’injecte dans une structure de type “map” (nom → FilesystemOperator) pour `FileStorage`.

- **Dépendance fournie par le bundle**  
  Le bundle déclare `league/flysystem` en `require` dans son `composer.json`. Les projets qui installent le bundle reçoivent Flysystem par transitivité ; ils n’ont pas besoin d’ajouter `league/flysystem` à leur propre `composer.json` sauf s’ils veulent des adapters additionnels (ex. `league/flysystem-aws-s3-v3`), qui restent optionnels côté projet.

- **Configuration**  
  Chaque entrée sous `keyboardman_filesystem.filesystems` garde une clé `adapter` qui pointe vers un **service Symfony** dont la valeur est une instance de `League\Flysystem\FilesystemAdapter` (ex. Local, InMemory, ou S3 via un package optionnel). La clé `cache` actuelle (pattern Gaufrette : source + cache) est soit retirée soit remplacée par une approche Flysystem (ex. décorateur ou doc) ; à préciser en implémentation.

- **Mapping d’API (Gaufrette → Flysystem)**  
  - `write(key, content, overwrite)` → `FilesystemOperator::write($path, $contents, $config)` (overwrite par défaut en v3).  
  - `read(key)` → `FilesystemOperator::read($path)`.  
  - `has(key)` → `FilesystemOperator::fileExists($path)` ou `has($path)` si disponible.  
  - `rename(source, target)` → `FilesystemOperator::move($source, $destination)`.  
  - `delete(key)` → `FilesystemOperator::delete($path)` ; pour les “répertoires” (.keep), supprimer le fichier `.keep` ou utiliser `deleteDirectory` si l’adapter le supporte.  
  - `listKeys(prefix)` → `FilesystemOperator::listContents($path, false)` puis adapter le format (chemins, exclusion des masqués, .keep → répertoires).  
  - Création de répertoire : actuellement via écriture d’un fichier `.keep` ; conserver ce comportement portable avec `write($path.'/.keep', '')` ou utiliser `createDirectory` Flysystem si l’adapter le supporte (Local oui, S3 peut ne pas créer de “dossier”).

- **Projet démo `demo/`**  
  Un répertoire `demo/` à la racine du dépôt contient un **projet Symfony minimal** (application qui dépend du bundle via path ou composer). Il inclut : `composer.json` (require du bundle), configuration du bundle (au moins un filesystem avec adapter Local ou InMemory), routes, et une page ou commande illustrant l’usage (ex. upload ou list). Objectif : validation manuelle et doc par l’exemple.

## Risks / Trade-offs

- **BREAKING** : Les projets existants qui référencent des adapters Gaufrette devront remplacer par des adapters Flysystem (services différents). Documenter la migration (exemples avant/après) dans le README.
- **Cache** : Gaufrette fournissait un decorator cache/source ; Flysystem v3 n’a pas le même package. On peut soit laisser le cache hors scope initial et le documenter comme “à venir”, soit proposer un décorateur custom ou un adapter “cached” tiers si disponible pour v3.

## Migration Plan

1. Remplacer `knplabs/gaufrette` par `league/flysystem` dans `composer.json` du bundle.  
2. Adapter l’extension DI : construire pour chaque filesystem un `League\Flysystem\Filesystem` à partir du service adapter (FilesystemAdapter) et l’enregistrer dans une map (nom → FilesystemOperator).  
3. Adapter `FileStorage` pour accepter cette map et appeler l’API Flysystem (write, read, move, delete, listContents, etc.) avec la logique métier actuelle (fichiers masqués, .keep, filtres type, tri).  
4. Mettre à jour la configuration (Configuration.php) : `adapter` = id de service FlysystemAdapter ; retirer ou adapter l’option `cache`.  
5. Mettre à jour les tests (config, mocks) pour utiliser des adapters Flysystem (ex. InMemory).  
6. Mettre à jour le README (installation, config, exemples avec Flysystem, migration depuis Gaufrette).  
7. Créer `demo/` : nouveau projet Symfony avec le bundle configuré et un exemple d’utilisation.

## Open Questions

- Cache : proposer une option cache (ex. décorateur sur l’adapter) dans cette même change ou dans un suivi ?  
- Démo : le bundle est-il chargé via `path` (../) ou via un dépôt/package name dans le `composer.json` du projet démo ?
