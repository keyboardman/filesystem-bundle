# Change: Ajouter le type MIME et la taille aux entrées de la liste de fichiers

## Why

L’endpoint GET list retourne aujourd’hui uniquement une liste de clés/paths (strings). Pour afficher correctement les fichiers (icônes, tri par taille, filtres côté client), le client a besoin du type MIME et du poids (taille en octets) en plus du nom.

## What Changes

- La réponse de l’endpoint GET list inclut, pour chaque entrée (fichier ou dossier), des métadonnées : au minimum le nom/path, et pour les fichiers le type MIME et la taille en octets. Les dossiers n’ont pas de MIME ni de taille (ou valeurs null/absentes).
- Le format de réponse évolue : au lieu d’un tableau `paths` de strings, la réponse contient une structure par entrée (ex. `items` avec `path`, `mimeType`, `size` pour les fichiers).
- **BREAKING** : les clients qui consomment uniquement `paths` (liste de strings) devront s’adapter au nouveau format ou une option pour conserver l’ancien format pourra être proposée (ex. paramètre `format=legacy`).

## Impact

- Affected specs: filestorage-api
- Affected code: `src/Service/FileStorage.php` (méthode `list`), `src/Controller/FileStorageController.php` (action list), `demo/src/Controller/DemoController.php` (affichage liste), `tests/Functional/FileStorageApiTest.php`
