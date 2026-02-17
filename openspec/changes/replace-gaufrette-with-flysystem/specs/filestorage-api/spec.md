## ADDED Requirements

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

L’API SHALL exposer un endpoint permettant d’uploader un seul fichier vers un filesystem nommé. Le stockage SHALL s’effectuer via Flysystem.

#### Scenario: Upload réussi

- **WHEN** le client envoie une requête d’upload avec un fichier et un filesystem valide (et optionnellement une clé/path)
- **THEN** le fichier est stocké via Flysystem sur le filesystem désigné et l’API retourne une réponse de succès (ex. 201) avec les informations utiles (path/key, filesystem)

### Requirement: Renommer et déplacer un fichier (via Flysystem)

L’API SHALL exposer des endpoints pour renommer et déplacer un fichier existant sur un filesystem nommé, en s’appuyant sur Flysystem (move).

#### Scenario: Rename / move réussi

- **WHEN** le client demande le renommage ou le déplacement d’un fichier existant vers une clé valide sur le même filesystem
- **THEN** le fichier est déplacé via Flysystem et l’API retourne un succès

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
