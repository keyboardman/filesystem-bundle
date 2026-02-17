# filestorage-api Specification

## Purpose
TBD - created by archiving change replace-gaufrette-with-flysystem. Update Purpose after archive.
## Requirements
### Requirement: Abstraction Flysystem

Le bundle SHALL utiliser [Flysystem](https://flysystem.thephpleague.com/docs/) (League) comme abstraction de stockage de fichiers. Toutes les opérations de lecture, écriture, suppression, déplacement et listage SHALL s’appuyer sur l’API Flysystem (FilesystemOperator / FilesystemAdapter).

#### Scenario: Opérations exécutées via Flysystem

- **WHEN** le service FileStorage effectue une opération (write, read, delete, move, list, createDirectory)
- **THEN** l’opération est déléguée à une instance League\Flysystem\FilesystemOperator associée au filesystem nommé

### Requirement: Dépendance Flysystem fournie par le bundle

Le bundle SHALL déclarer `league/flysystem` en dépendance requise. Un projet qui utilise le bundle ne SHALL pas avoir besoin d’ajouter `league/flysystem` à son propre `composer.json` pour utiliser les fonctionnalités du bundle (les adapters optionnels comme S3 peuvent rester à la charge du projet).

#### Scenario: Utilisation sans require explicite de Flysystem

- **WHEN** un projet Symfony requiert uniquement le bundle (keyboardman/filesystem-bundle) et configure au moins un filesystem avec un adapter fourni par le bundle ou par league/flysystem
- **THEN** le projet peut utiliser l’API du bundle sans ajouter `league/flysystem` à son composer.json

### Requirement: Upload d’un fichier (via Flysystem)

L’API SHALL exposer un endpoint permettant d’uploader un seul fichier vers un filesystem nommé. Le stockage SHALL s’effectuer via Flysystem. Seuls les fichiers dont le type est autorisé (audio, vidéo, image) et dont la taille n’excède pas la limite configurée sont acceptés.

#### Scenario: Upload réussi

- **WHEN** le client envoie une requête d’upload avec un fichier et un filesystem valide (et optionnellement une clé/path), et que le fichier a un type autorisé et une taille inférieure ou égale à max_upload_size
- **THEN** le fichier est stocké via Flysystem sur le filesystem désigné et l’API retourne une réponse de succès (ex. 201) avec les informations utiles (path/key, filesystem)

#### Scenario: Upload avec filesystem invalide

- **WHEN** le client envoie un upload vers un filesystem non configuré ou inconnu
- **THEN** l’API retourne une erreur (ex. 400 ou 404) et ne stocke pas le fichier

#### Scenario: Upload avec type de fichier non autorisé

- **WHEN** le client envoie un fichier dont le type (déterminé par extension) n’est pas dans la liste des types autorisés (audio, vidéo, image)
- **THEN** l’API retourne une erreur 400 avec un message explicite et ne stocke pas le fichier

#### Scenario: Upload avec taille dépassant max_upload_size

- **WHEN** le client envoie un fichier dont la taille dépasse la limite max_upload_size configurée
- **THEN** l’API retourne une erreur 400 avec un message explicite et ne stocke pas le fichier

### Requirement: Renommer et déplacer un fichier (via Flysystem)

L’API SHALL exposer des endpoints pour renommer et déplacer un fichier existant ou un répertoire existant sur un filesystem nommé, en s’appuyant sur Flysystem (move). Pour les répertoires créés via `createDirectory()`, le renommage SHALL déplacer tous les fichiers et sous-répertoires du répertoire source vers le répertoire cible, créer le fichier `.keep` dans le nouveau répertoire, et supprimer l’ancien fichier `.keep`.

#### Scenario: Rename / move réussi (fichier)

- **WHEN** le client demande le renommage ou le déplacement d’un fichier existant vers une clé valide sur le même filesystem
- **THEN** le fichier est déplacé via Flysystem et l’API retourne un succès

#### Scenario: Rename / move réussi (répertoire vide)

- **WHEN** le client demande le renommage ou le déplacement d’un répertoire vide (créé via `createDirectory()`) vers un chemin valide sur le même filesystem
- **THEN** le fichier `.keep` est déplacé vers le nouveau chemin, le nouveau répertoire est créé avec son fichier `.keep`, l’ancien fichier `.keep` est supprimé, et l’API retourne un succès

#### Scenario: Rename / move réussi (répertoire avec contenu)

- **WHEN** le client demande le renommage ou le déplacement d’un répertoire contenant des fichiers et/ou sous-répertoires vers un chemin valide sur le même filesystem
- **THEN** tous les fichiers et sous-répertoires sont déplacés vers le nouveau chemin, le nouveau répertoire est créé avec son fichier `.keep`, l’ancien fichier `.keep` est supprimé, et l’API retourne un succès

#### Scenario: Rename / move échoue si la cible existe déjà

- **WHEN** le client demande le renommage ou le déplacement d’un fichier ou répertoire vers un chemin où un fichier ou répertoire existe déjà
- **THEN** l’API retourne une erreur (ex. 409 Conflict) et ne modifie pas la ressource source

#### Scenario: Rename / move échoue si la source n’existe pas

- **WHEN** le client demande le renommage ou le déplacement d’un fichier ou répertoire qui n’existe pas
- **THEN** l’API retourne une erreur (ex. 404 Not Found)

### Requirement: Supprimer un fichier (via Flysystem)

L’API SHALL exposer un endpoint pour supprimer un fichier ou un répertoire (vide) désigné par filesystem et path/key, en s’appuyant sur Flysystem.

#### Scenario: Suppression réussie

- **WHEN** le client demande la suppression d’un fichier existant ou d’un répertoire vide (convention .keep)
- **THEN** la ressource est supprimée via Flysystem et l’API retourne un succès (ex. 204)

### Requirement: Plusieurs filesystems nommés (adapters Flysystem)

Le bundle SHALL permettre de configurer plusieurs filesystems nommés, chacun mappé à un adapter Flysystem (service Symfony implémentant League\Flysystem\FilesystemAdapter).

#### Scenario: Utilisation d’un filesystem nommé

- **WHEN** une opération API spécifie un nom de filesystem configuré
- **THEN** l’opération est exécutée sur l’instance Flysystem (FilesystemOperator) associée à ce nom

#### Scenario: Filesystem inconnu

- **WHEN** une opération API spécifie un nom de filesystem non déclaré dans la configuration
- **THEN** l’API rejette la requête avec une erreur (ex. 400 ou 404)

### Requirement: Projet démo

Un répertoire **demo/** à la racine du dépôt SHALL contenir un projet Symfony minimal qui utilise le bundle. Ce projet SHALL permettre de valider l’installation, la configuration et un exemple d’utilisation (ex. upload ou list) sans avoir à importer Flysystem dans le projet démo.

#### Scenario: Démo utilisable

- **WHEN** un développeur ouvre le répertoire demo/ et installe les dépendances (composer install) puis lance l’application
- **THEN** le bundle est configuré et une fonctionnalité d’exemple (page, route ou commande) illustre l’usage du stockage de fichiers via le bundle

### Requirement: Upload d’un fichier

L’API SHALL exposer un endpoint permettant d’uploader un seul fichier vers un filesystem nommé.

#### Scenario: Upload réussi

- **WHEN** le client envoie une requête d’upload avec un fichier et un filesystem valide (et optionnellement une clé/path)
- **THEN** le fichier est stocké via Gaufrette sur le filesystem désigné et l’API retourne une réponse de succès (ex. 201) avec les informations utiles (path/key, filesystem)

#### Scenario: Upload avec filesystem invalide

- **WHEN** le client envoie un upload vers un filesystem non configuré ou inconnu
- **THEN** l’API retourne une erreur (ex. 400 ou 404) et ne stocke pas le fichier

### Requirement: Upload multiple

L’API SHALL exposer un endpoint (ou accepter une requête) permettant d’uploader plusieurs fichiers en une fois vers un filesystem nommé. Tous les fichiers doivent avoir un type autorisé et une taille conforme à max_upload_size ; sinon la requête entière est rejetée.

#### Scenario: Upload multiple réussi

- **WHEN** le client envoie plusieurs fichiers pour un même filesystem valide, tous conformes (type autorisé, taille ≤ max_upload_size)
- **THEN** tous les fichiers sont stockés via Flysystem et l’API retourne une réponse de succès avec les identifiants (paths/keys) des fichiers créés

#### Scenario: Upload multiple avec au moins un fichier invalide

- **WHEN** le client envoie plusieurs fichiers dont au moins un a un type non autorisé ou une taille dépassant max_upload_size
- **THEN** l’API retourne une erreur 400 et ne stocke aucun des fichiers de la requête

### Requirement: Renommer un fichier

L’API SHALL exposer un endpoint pour renommer un fichier existant sur un filesystem nommé (même répertoire, changement de nom/clé).

#### Scenario: Rename réussi

- **WHEN** le client demande le renommage d’un fichier existant avec une nouvelle clé valide sur le même filesystem
- **THEN** le fichier est renommé via Gaufrette et l’API retourne un succès (ex. 200 ou 204)

#### Scenario: Rename sur fichier inexistant

- **WHEN** le client demande le renommage d’une clé qui n’existe pas
- **THEN** l’API retourne une erreur (ex. 404)

### Requirement: Déplacer un fichier

L’API SHALL exposer un endpoint pour déplacer un fichier (changement de path/key, éventuellement entre répertoires ou filesystems si le design le permet).

#### Scenario: Move réussi (même filesystem)

- **WHEN** le client demande le déplacement d’un fichier vers une nouvelle clé/path sur le même filesystem
- **THEN** le fichier est déplacé via Gaufrette et l’API retourne un succès

#### Scenario: Move vers fichier déjà existant ou cible invalide

- **WHEN** la cible existe déjà ou est invalide selon les règles du bundle
- **THEN** l’API retourne une erreur (ex. 409 ou 400) et ne modifie pas le fichier source

### Requirement: Supprimer un fichier

L’API SHALL exposer un endpoint pour supprimer un fichier désigné par filesystem et path/key.

#### Scenario: Suppression réussi

- **WHEN** le client demande la suppression d’un fichier existant
- **THEN** le fichier est supprimé via Gaufrette et l’API retourne un succès (ex. 204)

#### Scenario: Suppression sur fichier inexistant

- **WHEN** le client demande la suppression d’une clé qui n’existe pas
- **THEN** l’API retourne une erreur (ex. 404)

### Requirement: Plusieurs filesystems nommés

Le bundle SHALL permettre de configurer plusieurs filesystems nommés, chacun mappé à un adapter Gaufrette (local, S3, FTP, etc.).

#### Scenario: Utilisation d’un filesystem nommé

- **WHEN** une opération API spécifie un nom de filesystem configuré
- **THEN** l’opération est exécutée sur l’adapter Gaufrette associé à ce nom

#### Scenario: Filesystem inconnu

- **WHEN** une opération API spécifie un nom de filesystem non déclaré dans la configuration
- **THEN** l’API rejette la requête avec une erreur (ex. 400 ou 404)

### Requirement: Cache optionnel (pattern Gaufrette)

Le bundle SHALL permettre de définir un filesystem comme “cached” en utilisant le mécanisme de cache Gaufrette : un adapter decorator composé d’un adapter de cache et d’un adapter source (référence : [Gaufrette – Caching](https://knplabs.github.io/Gaufrette/caching.html)).

#### Scenario: Filesystem avec cache configuré

- **WHEN** un filesystem est configuré avec une option cache (adapter cache + adapter source)
- **THEN** les opérations de lecture/écriture pour ce filesystem passent par le decorator de cache Gaufrette (cache + source)

#### Scenario: Comportement lecture avec cache

- **WHEN** une lecture est effectuée sur un filesystem ayant le cache activé et que la donnée est présente dans le cache
- **THEN** la réponse peut être servie depuis le cache selon le comportement du decorator Gaufrette, sans nécessairement interroger la source

### Requirement: Lister les fichiers d’un filesystem

L’API SHALL exposer un endpoint (GET) permettant de lister les fichiers d’un filesystem nommé. Seuls les fichiers non masqués sont inclus. Le client peut optionnellement filtrer par type de média (audio, vidéo, image) et trier la liste par nom en ordre ascendant ou descendant.

#### Scenario: List sans filtre

- **WHEN** le client envoie une requête GET list avec un filesystem valide et sans paramètre de type
- **THEN** l’API retourne une réponse de succès (200) contenant la liste des clés/paths des fichiers du filesystem, à l’exclusion des fichiers masqués

#### Scenario: List avec filtre par type (audio, video, image)

- **WHEN** le client envoie une requête GET list avec un filesystem valide et un paramètre `type` égal à `audio`, `video` ou `image`
- **THEN** l’API retourne une réponse de succès (200) contenant uniquement les clés/paths des fichiers dont le type correspond (déterminé par extension ou MIME selon l’implémentation)

#### Scenario: List triée par nom (asc)

- **WHEN** le client envoie une requête GET list avec un filesystem valide et un paramètre `sort=asc`
- **THEN** l’API retourne une réponse de succès (200) contenant la liste des clés/paths triées par nom en ordre alphabétique croissant

#### Scenario: List triée par nom (desc)

- **WHEN** le client envoie une requête GET list avec un filesystem valide et un paramètre `sort=desc`
- **THEN** l’API retourne une réponse de succès (200) contenant la liste des clés/paths triées par nom en ordre alphabétique décroissant

#### Scenario: Fichiers masqués exclus

- **WHEN** le filesystem contient des fichiers dont le nom est considéré comme masqué (ex. nom commençant par `.` ou convention documentée)
- **THEN** ces fichiers ne figurent jamais dans la réponse de l’endpoint list, avec ou sans filtre par type

#### Scenario: List avec filesystem invalide

- **WHEN** le client envoie une requête GET list vers un filesystem non configuré ou inconnu
- **THEN** l’API retourne une erreur (ex. 400 ou 404) et ne retourne pas de liste

### Requirement: Configuration des restrictions d’upload

Le bundle SHALL permettre de configurer les restrictions d’upload via les paramètres `allowed_types` (types de fichiers autorisés : audio, vidéo, image par défaut) et `max_upload_size` (taille maximale par fichier en bytes ou notation lisible ex. 10M).

#### Scenario: Restrictions par défaut

- **WHEN** le bundle est configuré sans paramètres api ou avec api par défaut
- **THEN** seuls les types audio, vidéo et image sont autorisés, et max_upload_size a une valeur par défaut (ex. 10 MiB)

#### Scenario: Configuration personnalisée

- **WHEN** l’application configure api.allowed_types et/ou api.max_upload_size
- **THEN** les uploads sont validés selon ces valeurs avant stockage

