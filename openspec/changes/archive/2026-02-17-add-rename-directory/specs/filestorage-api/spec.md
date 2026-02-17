## MODIFIED Requirements

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
