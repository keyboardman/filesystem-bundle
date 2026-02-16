## 1. Service et listage

- [x] 1.1 Exposer dans `FileStorage` une méthode de listage (ex. `list(string $filesystem, ?string $type = null)`) s’appuyant sur Gaufrette (`listKeys`/`keys`), avec exclusion des clés « masquées » (ex. préfixe `.` ou convention documentée).
- [x] 1.2 Définir la convention « fichier masqué » (nom commence par `.` par défaut) et l’appliquer avant de retourner les clés.
- [x] 1.3 Implémenter le filtrage par type `audio`, `video`, `image` (extensions ou MIME) côté service ou contrôleur ; documenter les extensions/MIME associés.
- [x] 1.4 Implémenter le tri par nom : `asc` (ordre alphabétique croissant) et `desc` (ordre alphabétique décroissant) sur la clé/path du fichier.

## 2. API HTTP

- [x] 2.1 Ajouter une route **GET** (ex. `/list`) avec paramètre `filesystem` (requis ou défaut `default`), paramètre optionnel `type` (`audio` | `video` | `image`) et paramètre optionnel `sort` (`asc` | `desc`).
- [x] 2.2 Réponse JSON : liste de chemins/clés (et éventuellement métadonnées minimales) ; codes HTTP cohérents (200, 400 pour filesystem inconnu).

## 3. Tests

- [x] 3.1 Tests fonctionnels/HTTP : list sans filtre, list avec `type=image` (et audio/video si pertinent), list avec `sort=asc` et `sort=desc`, list ne retourne pas les fichiers masqués.
- [x] 3.2 Test ou scénario pour filesystem inconnu (erreur attendue).

## 4. Documentation

- [x] 4.1 Mettre à jour la doc (README ou équivalent) : route list, paramètres (type, sort), exemples, convention des fichiers masqués et types supportés.
