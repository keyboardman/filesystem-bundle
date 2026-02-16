## 1. Fondations

- [x] 1.1 Créer le bundle Symfony (structure, extension, configuration)
- [x] 1.2 Ajouter la dépendance `knplabs/gaufrette` et configurer au moins un adapter (ex. local)
- [x] 1.3 Définir le service(s) qui expose(nt) les opérations Gaufrette (read, write, delete, list, rename/move) par filesystem nommé

## 2. Configuration multi-filesystems et cache

- [x] 2.1 Permettre la déclaration de plusieurs filesystems nommés dans la config du bundle
- [x] 2.2 Permettre l’option cache pour un filesystem : adapter cache + adapter source (pattern decorator Gaufrette)

## 3. API HTTP

- [x] 3.1 Endpoint upload (un fichier)
- [x] 3.2 Endpoint upload multiple (plusieurs fichiers)
- [x] 3.3 Endpoint renommer un fichier
- [x] 3.4 Endpoint déplacer un fichier
- [x] 3.5 Endpoint supprimer un fichier
- [x] 3.6 Réponses JSON et codes HTTP cohérents ; identification par filesystem + path/key

## 4. Tests

- [x] 4.1 Tests fonctionnels / HTTP de l’API (upload, rename, move, delete, multi-filesystems)
- [x] 4.2 Tests ou scénario vérifiant l’usage d’un filesystem avec cache (comportement attendu)

## 5. Démo et documentation

- [x] 5.1 Page de démonstration (formulaire ou liens pour appeler l’API)
- [x] 5.2 Documentation : installation, configuration, multi-filesystems, configuration du cache (référence Gaufrette), exemples d’appels API
