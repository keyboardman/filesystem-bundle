## 1. Configuration

- [x] 1.1 Ajouter les nœuds `api.allowed_types` et `api.max_upload_size` dans `Configuration.php`
- [x] 1.2 Documenter les valeurs par défaut (types alignés avec FileStorage::TYPE_EXTENSIONS, max_upload_size 10 MiB)
- [x] 1.3 Injecter les paramètres dans le service de validation ou le contrôleur (extension DI)

## 2. Validation

- [x] 2.1 Créer un service ou une méthode de validation (allowed extensions, max size)
- [x] 2.2 Intégrer la validation dans `FileStorageController::upload` avant écriture
- [x] 2.3 Intégrer la validation dans `FileStorageController::uploadMultiple` avant écriture ; rejeter la requête entière si un fichier est invalide

## 3. Réponses d’erreur

- [x] 3.1 Retourner 400 avec message explicite pour type non autorisé
- [x] 3.2 Retourner 400 avec message explicite pour taille dépassée

## 4. Tests

- [x] 4.1 Test : upload d’un fichier image/audio/vidéo valide → succès
- [x] 4.2 Test : upload d’un fichier avec extension non autorisée → 400
- [x] 4.3 Test : upload d’un fichier dépassant max_upload_size → 400
- [x] 4.4 Test : upload-multiple avec un fichier invalide → 400, aucun fichier stocké
- [x] 4.5 Test : configuration custom (allowed_types, max_upload_size) prise en compte
