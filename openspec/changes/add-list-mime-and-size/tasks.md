## 1. Spécification et design

- [x] 1.1 Décider du format de réponse (structure par entrée : path, type dir/file, mimeType, size) et d’une éventuelle rétrocompatibilité (paramètre `format=legacy` pour garder `paths` en strings)

## 2. Implémentation backend

- [x] 2.1 Adapter `FileStorage::list()` pour retourner des tableaux d’entrées avec path, type (file/dir), mimeType et size (pour les fichiers) en s’appuyant sur Flysystem `FileAttributes` (fileSize, mimeType)
- [x] 2.2 Adapter le contrôleur list pour exposer le nouveau format (ex. `items`) et optionnellement `format=legacy` si décidé
- [x] 2.3 Gérer les adapters qui ne fournissent pas mimeType/fileSize (valeurs null ou détection par extension / lecture si nécessaire)

## 3. Tests

- [x] 3.1 Mettre à jour les tests fonctionnels list pour vérifier la présence de `mimeType` et `size` sur les fichiers
- [x] 3.2 Ajouter un scénario pour un fichier connu (ex. .jpg) et vérifier mimeType et size dans la réponse

## 4. Démo

- [x] 4.1 Mettre à jour la page démo pour afficher le type MIME et la taille dans la liste des fichiers (et conserver le comportement pour les dossiers)
