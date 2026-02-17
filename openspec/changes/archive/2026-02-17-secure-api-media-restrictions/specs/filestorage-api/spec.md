## MODIFIED Requirements

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

### Requirement: Upload multiple

L’API SHALL exposer un endpoint (ou accepter une requête) permettant d’uploader plusieurs fichiers en une fois vers un filesystem nommé. Tous les fichiers doivent avoir un type autorisé et une taille conforme à max_upload_size ; sinon la requête entière est rejetée.

#### Scenario: Upload multiple réussi

- **WHEN** le client envoie plusieurs fichiers pour un même filesystem valide, tous conformes (type autorisé, taille ≤ max_upload_size)
- **THEN** tous les fichiers sont stockés via Flysystem et l’API retourne une réponse de succès avec les identifiants (paths/keys) des fichiers créés

#### Scenario: Upload multiple avec au moins un fichier invalide

- **WHEN** le client envoie plusieurs fichiers dont au moins un a un type non autorisé ou une taille dépassant max_upload_size
- **THEN** l’API retourne une erreur 400 et ne stocke aucun des fichiers de la requête

## ADDED Requirements

### Requirement: Configuration des restrictions d’upload

Le bundle SHALL permettre de configurer les restrictions d’upload via les paramètres `allowed_types` (types de fichiers autorisés : audio, vidéo, image par défaut) et `max_upload_size` (taille maximale par fichier en bytes ou notation lisible ex. 10M).

#### Scenario: Restrictions par défaut

- **WHEN** le bundle est configuré sans paramètres api ou avec api par défaut
- **THEN** seuls les types audio, vidéo et image sont autorisés, et max_upload_size a une valeur par défaut (ex. 10 MiB)

#### Scenario: Configuration personnalisée

- **WHEN** l’application configure api.allowed_types et/ou api.max_upload_size
- **THEN** les uploads sont validés selon ces valeurs avant stockage
