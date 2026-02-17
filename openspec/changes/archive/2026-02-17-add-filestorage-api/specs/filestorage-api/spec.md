## ADDED Requirements

### Requirement: Upload d’un fichier

L’API SHALL exposer un endpoint permettant d’uploader un seul fichier vers un filesystem nommé.

#### Scenario: Upload réussi

- **WHEN** le client envoie une requête d’upload avec un fichier et un filesystem valide (et optionnellement une clé/path)
- **THEN** le fichier est stocké via Gaufrette sur le filesystem désigné et l’API retourne une réponse de succès (ex. 201) avec les informations utiles (path/key, filesystem)

#### Scenario: Upload avec filesystem invalide

- **WHEN** le client envoie un upload vers un filesystem non configuré ou inconnu
- **THEN** l’API retourne une erreur (ex. 400 ou 404) et ne stocke pas le fichier

### Requirement: Upload multiple

L’API SHALL exposer un endpoint (ou accepter une requête) permettant d’uploader plusieurs fichiers en une fois vers un filesystem nommé.

#### Scenario: Upload multiple réussi

- **WHEN** le client envoie plusieurs fichiers pour un même filesystem valide
- **THEN** tous les fichiers sont stockés et l’API retourne une réponse de succès avec les identifiants (paths/keys) des fichiers créés

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
