# Design: API File Storage avec Gaufrette et cache

## Context

- Bundle Symfony réutilisable.
- Abstraction filesystem via [Gaufrette](https://knplabs.github.io/Gaufrette/installation.html).
- Besoin d’upload, rename, move, delete et de support multi-filesystems + cache optionnel (documenté par Gaufrette).

## Goals / Non-Goals

- **Goals:** API REST/HTTP claire, multi-filesystems, cache optionnel type Gaufrette (decorator cache + source), tests, démo, doc.
- **Non-Goals:** Gestion des permissions métier, versioning de fichiers, recherche full-text.

## Decisions

### Librairie : Gaufrette

- Utiliser `knplabs/gaufrette` pour l’abstraction filesystem.
- Les opérations (read, write, delete, list, etc.) passent par l’API Gaufrette ; le bundle expose une API HTTP au-dessus.

### Plusieurs filesystems

- Chaque filesystem est nommé (ex. `default`, `documents`, `uploads`).
- Configuration Symfony (YAML/PHP) : mapping nom → adapter Gaufrette (local, S3, FTP, etc.).

### Cache (pattern Gaufrette)

- Gaufrette fournit un **cache sous forme de decorator** : un adapter de cache + un adapter source.
- Le bundle SHALL permettre de déclarer un filesystem comme “cached” en config : adapter cache (ex. local ou APC) + adapter source (ex. FTP). Référence : [Gaufrette – Caching](https://knplabs.github.io/Gaufrette/caching.html).
- Implémentation : utiliser le mécanisme natif Gaufrette (ex. `CacheAdapter` / decorator) pour les filesystems marqués en cache dans la config ; pas d’invention d’un autre système.

### API exposée

- Endpoints REST pour : upload (un/plusieurs), rename, move, delete.
- Identification des fichiers par filesystem name + path (ou key).
- Réponses JSON ; codes HTTP standards (201, 204, 400, 404, etc.).

### Tests, démo, doc

- Tests automatisés de l’API (functional/HTTP).
- Une page de démonstration (ex. dans une route dédiée ou un contrôleur démo).
- Documentation (README et/ou doc dédiée) expliquant configuration, multi-filesystems et cache.

## Risks / Trade-offs

- **Cache invalidation** : dépend du comportement du decorator Gaufrette ; documenter les limites (TTL, invalidation manuelle si disponible).
- **Sécurité** : l’API doit rester protégée (firewall, auth) ; la doc doit le rappeler (non implémenté dans ce change, mais à documenter).

## Open Questions

- Aucun bloquant pour la proposition actuelle.
