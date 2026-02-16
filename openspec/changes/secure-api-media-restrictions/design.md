# Design: Restrictions d’upload média

## Context

L’API File Storage accepte actuellement tout fichier lors de l’upload. Pour limiter les risques (malware, scripts, abus) et aligner avec les cas d’usage média (galerie, catalogue), il faut restreindre les types autorisés et la taille maximale.

## Goals / Non-Goals

- **Goals** : Restreindre les uploads aux fichiers audio, vidéo et image ; appliquer une limite de taille configurable ; rejeter explicitement les requêtes non conformes.
- **Non-Goals** : Validation MIME côté serveur (peut être ajoutée ultérieurement) ; limites par filesystem (une config globale suffit pour l’instant).

## Decisions

### 1. Types autorisés par extension

- Utiliser une liste d’extensions alignée avec `TYPE_EXTENSIONS` du service `FileStorage` (utilisé pour le filtrage list).
- Validation par extension uniquement (simple, cohérent avec le reste du bundle). La validation MIME peut être ajoutée plus tard si besoin.
- Extensions par défaut : image (jpg, jpeg, png, gif, webp, svg, bmp, ico), audio (mp3, wav, ogg, m4a, aac, flac), video (mp4, webm, avi, mov, mkv, m4v).

### 2. Taille maximale configurable

- Paramètre `max_upload_size` en bytes (ou avec support de suffixes : 10M, 50M pour lisibilité).
- Valeur par défaut proposée : 10 MiB (10_485_760 bytes) pour éviter les uploads disproportionnés.
- La validation SHALL intervenir avant l’écriture sur Gaufrette (early reject).

### 3. Placement de la configuration

- Nœud racine du bundle : `keyboardman_filesystem.api.allowed_types` (optionnel) et `keyboardman_filesystem.api.max_upload_size` (requis ou défaut).
- Alternative : sous `keyboardman_filesystem.api` pour regrouper les paramètres API.

### 4. Réponses d’erreur

- Type non autorisé : 400 avec message explicite (ex. `{"error": "File type not allowed"}`).
- Taille dépassée : 400 avec message explicite (ex. `{"error": "File size exceeds limit"}`).
- Pour upload-multiple : les fichiers non conformes sont ignorés ou tous rejetés ? → **Rejeter la requête entière** si au moins un fichier est invalide (comportement plus prévisible et sécurisé).

## Risks / Trade-offs

- **Extension vs MIME** : la validation par extension seule peut être contournée (renommage). Pour un usage interne ou protégé, c’est acceptable. MIME peut être ajouté en complément ultérieurement.
- **Upload-multiple** : rejeter toute la requête si un seul fichier est invalide peut sembler strict, mais évite des états partiels et des comportements ambigus.

## Migration Plan

- Paramètres optionnels avec valeurs par défaut : les projets existants continuent de fonctionner sans modification de config (avec restrictions par défaut). **BREAKING** : si la config actuelle uploade des types non média, ces uploads seront rejetés après déploiement. Documenter clairement dans le CHANGELOG et la doc.
