## 1. Implémentation

- [x] 1.1 Modifier la méthode `rename()` dans `FileStorage` pour détecter si le chemin source est un répertoire (présence de `path/.keep`)
- [x] 1.2 Implémenter la logique de renommage de répertoire : déplacer tous les fichiers du répertoire source vers le répertoire cible, créer le nouveau `.keep`, supprimer l'ancien `.keep`
- [x] 1.3 Mettre à jour la méthode `has()` ou créer une méthode utilitaire pour détecter si un chemin est un répertoire
- [x] 1.4 Ajouter la validation des chemins (pas de `..`, pas vide, etc.) pour les répertoires dans `rename()`

## 2. Tests

- [x] 2.1 Ajouter un test fonctionnel pour renommer un répertoire vide
- [x] 2.2 Ajouter un test fonctionnel pour renommer un répertoire contenant des fichiers
- [x] 2.3 Ajouter un test fonctionnel pour renommer un répertoire contenant des sous-répertoires
- [x] 2.4 Ajouter un test pour vérifier que le renommage échoue si le répertoire cible existe déjà
- [x] 2.5 Ajouter un test pour vérifier que le renommage échoue si le répertoire source n'existe pas
- [x] 2.6 Ajouter un test pour vérifier que le renommage fonctionne via l'endpoint `/move` également

## 3. Documentation

- [x] 3.1 Mettre à jour le README pour documenter le renommage de répertoires
- [x] 3.2 Mettre à jour la page de démo si nécessaire
