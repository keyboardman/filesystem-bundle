# Change: Bundle Symfony réutilisable – API File Storage

## Why

Offrir une API de stockage de fichiers réutilisable pour des applications Symfony, avec abstraction des backends (local, S3, FTP, etc.) via Gaufrette, et option de cache pour les filesystems lents.

## What Changes

- Nouveau bundle Symfony exposant une API file storage (upload un/plusieurs fichiers, renommer, déplacer, supprimer).
- Support de plusieurs filesystems nommés configurables (Gaufrette).
- Possibilité d’envelopper un filesystem avec un cache (pattern decorator Gaufrette : adapter cache + adapter source).
- Tests de l’API, page de démonstration et documentation.

## Impact

- Affected specs: filestorage-api (nouvelle capacité).
- Affected code: nouveau bundle (services, contrôleurs, config, routes, tests, démo, doc).
