# Change: Ajouter le renommage de répertoires

## Why
Actuellement, le bundle permet de créer des répertoires via l'endpoint `create-directory`, mais il n'est pas possible de les renommer. Cette fonctionnalité manquante limite l'utilisabilité du bundle pour la gestion complète de la structure de fichiers.

## What Changes
- Ajout de la capacité de renommer des répertoires créés via `createDirectory()` dans le service `FileStorage`
- Mise à jour de l'endpoint `/rename` pour supporter les répertoires en plus des fichiers
- Mise à jour de l'endpoint `/move` pour supporter les répertoires en plus des fichiers
- Les répertoires sont identifiés par la présence du fichier `.keep` (convention existante)

## Impact
- Affected specs: `filestorage-api`
- Affected code: 
  - `src/Service/FileStorage.php` (méthode `rename()`)
  - `src/Controller/FileStorageController.php` (endpoints `rename` et `move`)
