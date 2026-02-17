## MODIFIED Requirements

### Requirement: Lister les fichiers d’un filesystem

L’API SHALL exposer un endpoint (GET) permettant de lister les fichiers et dossiers d’un filesystem nommé. Chaque entrée de la liste SHALL inclure au minimum le path (nom ou clé). Pour chaque fichier (non répertoire), la réponse SHALL inclure en outre le type MIME et la taille en octets. Les répertoires n’ont pas de type MIME ni de taille (champs absents ou null). Seuls les fichiers non masqués sont inclus. Le client peut optionnellement filtrer par type de média (audio, vidéo, image) et trier la liste par nom en ordre ascendant ou descendant.

#### Scenario: List sans filtre

- **WHEN** le client envoie une requête GET list avec un filesystem valide et sans paramètre de type
- **THEN** l’API retourne une réponse de succès (200) contenant une liste d’entrées (ex. `items`) ; chaque entrée a au minimum un champ path ; chaque entrée de type fichier a en outre les champs mimeType et size (taille en octets), à l’exclusion des fichiers masqués

#### Scenario: List avec filtre par type (audio, video, image)

- **WHEN** le client envoie une requête GET list avec un filesystem valide et un paramètre `type` égal à `audio`, `video` ou `image`
- **THEN** l’API retourne une réponse de succès (200) contenant uniquement les entrées des fichiers dont le type correspond (déterminé par extension ou MIME selon l’implémentation), chacune avec path, mimeType et size

#### Scenario: List triée par nom (asc)

- **WHEN** le client envoie une requête GET list avec un filesystem valide et un paramètre `sort=asc`
- **THEN** l’API retourne une réponse de succès (200) contenant la liste des entrées triées par nom (path) en ordre alphabétique croissant, avec mimeType et size pour les fichiers

#### Scenario: List triée par nom (desc)

- **WHEN** le client envoie une requête GET list avec un filesystem valide et un paramètre `sort=desc`
- **THEN** l’API retourne une réponse de succès (200) contenant la liste des entrées triées par nom (path) en ordre alphabétique décroissant, avec mimeType et size pour les fichiers

#### Scenario: Fichiers masqués exclus

- **WHEN** le filesystem contient des fichiers dont le nom est considéré comme masqué (ex. nom commençant par `.` ou convention documentée)
- **THEN** ces fichiers ne figurent jamais dans la réponse de l’endpoint list, avec ou sans filtre par type

#### Scenario: List avec filesystem invalide

- **WHEN** le client envoie une requête GET list vers un filesystem non configuré ou inconnu
- **THEN** l’API retourne une erreur (ex. 400 ou 404) et ne retourne pas de liste

#### Scenario: Entrées fichiers avec mimeType et size

- **WHEN** le client reçoit une réponse de succès de l’endpoint list contenant au moins un fichier (non répertoire)
- **THEN** chaque entrée de type fichier contient un champ mimeType (string, ex. `image/jpeg`) et un champ size (entier, taille en octets) ; si le type MIME ou la taille ne peut pas être déterminé par l’adapter, le champ peut être null ou absent selon la décision d’implémentation
