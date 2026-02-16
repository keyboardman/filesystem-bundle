# Change: Route list pour lister les fichiers avec filtres par type

## Why

Permettre aux clients de l’API de lister les fichiers d’un filesystem nommé, avec filtrage optionnel par type (audio, vidéo, image) et exclusion des fichiers masqués, pour des cas d’usage type galerie ou catalogue média.

## What Changes

- Nouvel endpoint **GET** (route list) exposant la liste des clés/paths des fichiers d’un filesystem.
- Paramètre de requête optionnel pour filtrer par type : `audio`, `video`, `image` (déterminé par extension ou MIME selon le design).
- Paramètre de requête optionnel pour trier par nom : `sort=asc` ou `sort=desc` (ordre alphabétique sur le nom/chemin du fichier).
- Les fichiers considérés comme « masqués » (ex. nom commençant par `.` ou convention configurable) ne sont jamais inclus dans la réponse.
- Le service `FileStorage` (ou couche adaptée) expose une opération de listage s’appuyant sur Gaufrette (`listKeys` / `keys`), avec filtrage type, exclusion des masqués et tri optionnel par nom (asc/desc).

## Impact

- Affected specs: filestorage-api (delta ADDED sur la capacité existante du change add-filestorage-api).
- Affected code: `FileStorageController` (nouvelle action list), `FileStorage` (méthode list/listKeys + filtres), tests API, éventuellement doc/README.
