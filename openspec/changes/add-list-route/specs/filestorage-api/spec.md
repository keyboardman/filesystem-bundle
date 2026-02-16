## ADDED Requirements

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
