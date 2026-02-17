# Change: Sécuriser l'API – types autorisés et max-upload

## Why

L’API accepte actuellement tout type de fichier lors de l’upload, ce qui crée un risque de sécurité (exécutables, scripts malveillants) et d’abus de stockage. Il faut restreindre les uploads aux médias prévus (audio, vidéo, image) et définir une limite de taille configurable.

## What Changes

- Restriction des types de fichiers acceptés en upload (upload et upload-multiple) : seuls audio, vidéo et image sont autorisés (par extension, aligné avec le filtrage du list).
- Ajout d’une limite de taille maximale par fichier (`max_upload_size`), configurable au niveau du bundle.
- Nouveaux paramètres de configuration : `allowed_types` (optionnel, par défaut audio/video/image) et `max_upload_size` (obligatoire ou valeur par défaut).
- Retour d’erreur explicite (ex. 400) lorsque le type ou la taille dépasse les limites.

## Impact

- Affected specs: filestorage-api (MODIFIED sur Upload d’un fichier, Upload multiple ; ADDED sur Configuration des restrictions).
- Affected code: `FileStorageController` (upload, uploadMultiple), `FileStorage` ou nouveau service de validation, `Configuration`, `KeyboardmanFilesystemExtension`, tests API.
