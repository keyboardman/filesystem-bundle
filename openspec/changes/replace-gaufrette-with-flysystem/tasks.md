## 1. Dépendances et configuration

- [x] 1.1 Remplacer `knplabs/gaufrette` par `league/flysystem` (^3.0) dans `composer.json` du bundle
- [x] 1.2 Adapter `Configuration.php` : `adapter` = id de service FlysystemAdapter ; retirer ou adapter l’option `cache` (Gaufrette)
- [x] 1.3 Mettre à jour `KeyboardmanFilesystemExtension` : construire une map nom → `League\Flysystem\FilesystemOperator` à partir des adapters Flysystem (pas de Gaufrette)

## 2. Service FileStorage

- [x] 2.1 Modifier `FileStorage` pour injecter la map de FilesystemOperator (au lieu de Gaufrette FilesystemMap)
- [x] 2.2 Implémenter write/read/has/rename/delete/createDirectory/list en s’appuyant sur l’API Flysystem (write, read, fileExists/has, move, delete, listContents, et .keep ou createDirectory selon besoin)
- [x] 2.3 Conserver la même logique métier (fichiers masqués, exclusion, filtres type, tri, répertoires via .keep)

## 3. Tests

- [x] 3.1 Remplacer les adapters Gaufrette (InMemory) par des adapters Flysystem (ex. League\Flysystem\InMemory\InMemoryFilesystemAdapter) dans les configs de test (test.yaml, dev.yaml)
- [x] 3.2 Adapter les tests unitaires et fonctionnels pour les nouvelles dépendances et appels Flysystem
- [x] 3.3 Exécuter la suite de tests et corriger les régressions

## 4. Documentation et API

- [x] 4.1 Mettre à jour le README : installation, configuration avec Flysystem (adapter = service id), exemples (Local, InMemory, S3 si pertinent), section migration depuis Gaufrette
- [x] 4.2 Vérifier que l’API HTTP (contrôleur) et les réponses restent inchangées côté contrat

## 5. Projet démo

- [x] 5.1 Créer le répertoire `demo/` à la racine du dépôt
- [x] 5.2 Initialiser un projet Symfony minimal dans `demo/` (composer create-project ou structure manuelle) avec dépendance au bundle (path ou name)
- [x] 5.3 Configurer le bundle dans `demo/` (au moins un filesystem, ex. Local ou InMemory)
- [x] 5.4 Ajouter une page ou une route démo qui utilise le bundle (ex. formulaire d’upload ou liste de fichiers) pour valider l’usage
- [x] 5.5 Documenter dans le README principal comment lancer et utiliser le projet démo

## 6. Validation

- [x] 6.1 Exécuter `openspec validate replace-gaufrette-with-flysystem --strict` et corriger les éventuels problèmes
- [x] 6.2 Vérifier qu’un projet externe qui requiert uniquement le bundle peut utiliser le stockage sans ajouter `league/flysystem` à son composer (sauf pour adapters optionnels)
