<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemoController
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    #[Route('/', name: 'demo_home')]
    public function index(): Response
    {
        // Récupérer le chemin de stockage depuis la configuration
        $storagePath = $_ENV['STORAGE_PATH'] ?? '%kernel.project_dir%/var/storage';
        $storagePath = str_replace('%kernel.project_dir%', $this->parameterBag->get('kernel.project_dir'), $storagePath);
        
        $html = <<<'HTML'
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo - Keyboardman Filesystem Bundle</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 2.5em;
        }
        .subtitle {
            color: #666;
            margin-bottom: 40px;
            font-size: 1.1em;
        }
        .storage-info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
        .storage-info strong {
            color: #1976d2;
        }
        .storage-info code {
            background: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
        }
        .section {
            margin-bottom: 40px;
            padding: 30px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s;
        }
        input[type="text"]:focus,
        input[type="file"]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
        }
        button:hover {
            background: #5568d3;
        }
        button:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .file-list {
            margin-top: 20px;
        }
        .file-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            background: white;
            border-radius: 6px;
            margin-bottom: 8px;
            border: 1px solid #e0e0e0;
        }
        .file-item.directory {
            font-weight: 500;
        }
        .file-item .file-meta {
            color: #666;
            font-size: 0.9em;
        }
        .file-item .actions {
            display: flex;
            gap: 8px;
        }
        .file-item .actions button {
            padding: 6px 12px;
            font-size: 12px;
        }
        .file-item .actions button.delete {
            background: #e74c3c;
        }
        .file-item .actions button.delete:hover {
            background: #c0392b;
        }
        .file-item .actions button.rename {
            background: #f39c12;
        }
        .file-item .actions button.rename:hover {
            background: #e67e22;
        }
        .file-item .actions button.open {
            background: #27ae60;
        }
        .file-item .actions button.open:hover {
            background: #219a52;
        }
        .root-link:hover {
            background: #7f8c8d !important;
        }
        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            display: block;
        }
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            display: block;
        }
        .loading {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Demo Filesystem Bundle</h1>
        <p class="subtitle">Testez les fonctionnalités d'upload, renommage et suppression</p>
        
        <div class="storage-info">
            <strong>📂 Chemin de stockage local :</strong> <code>{$storagePath}</code>
        </div>
        <div class="form-group" style="margin-bottom: 16px;">
            <label for="filesystemSelect" style="margin-right: 8px;">Stockage :</label>
            <select id="filesystemSelect" onchange="loadFileList()" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #ddd;">
                <option value="default">Local (default)</option>
                <option value="s3">S3 (MinIO)</option>
            </select>
        </div>

        <div id="message" class="message"></div>

        <!-- Section Upload -->
        <div class="section">
            <h2>📤 Upload de fichier</h2>
            <div class="form-group">
                <label for="fileInput">Sélectionner un fichier (image, audio ou vidéo)</label>
                <input type="file" id="fileInput" accept="image/*,audio/*,video/*">
            </div>
            <div class="form-group">
                <label for="filePath">Chemin de destination (optionnel)</label>
                <input type="text" id="filePath" placeholder="ex: mes-fichiers/mon-image.jpg">
            </div>
            <button onclick="uploadFile()">Uploader</button>
        </div>

        <!-- Section Créer dossier -->
        <div class="section">
            <h2>📁 Créer un dossier</h2>
            <div class="form-group">
                <label for="directoryPath">Nom du dossier</label>
                <input type="text" id="directoryPath" placeholder="ex: mon-dossier">
            </div>
            <button onclick="createDirectory()">Créer le dossier</button>
        </div>

        <!-- Section Liste -->
        <div class="section">
            <h2>📋 Liste des fichiers et dossiers</h2>
            <div class="form-group">
                <label for="listPath">Chemin à lister (optionnel, vide = racine)</label>
                <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                    <input type="text" id="listPath" placeholder="ex: mes-fichiers" style="flex: 1; min-width: 200px;">
                    <button onclick="loadFileList()">Actualiser</button>
                    <button type="button" class="root-link" onclick="document.getElementById('listPath').value=''; loadFileList();" style="background: #95a5a6;">Racine</button>
                </div>
            </div>
            <div class="form-group" style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap; margin-top: 12px;">
                <div style="display: flex; gap: 8px; align-items: center;">
                    <label for="listFilterType" style="margin: 0; white-space: nowrap;">Filtrer par type:</label>
                    <select id="listFilterType" onchange="loadFileList()" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="">Tous</option>
                        <option value="image">Images</option>
                        <option value="audio">Audio</option>
                        <option value="video">Vidéo</option>
                    </select>
                </div>
                <div style="display: flex; gap: 8px; align-items: center;">
                    <label for="listSort" style="margin: 0; white-space: nowrap;">Trier:</label>
                    <select id="listSort" onchange="loadFileList()" style="padding: 6px 12px; border-radius: 4px; border: 1px solid #ddd;">
                        <option value="">Par défaut</option>
                        <option value="asc">A-Z</option>
                        <option value="desc">Z-A</option>
                    </select>
                </div>
            </div>
            <div id="fileList" class="file-list"></div>
        </div>
    </div>

    <script>
        const API_BASE = '/api/filesystem';
        function getFilesystem() {
            const el = document.getElementById('filesystemSelect');
            return el ? el.value : 'default';
        }

        function showMessage(text, type = 'success') {
            const msg = document.getElementById('message');
            msg.textContent = text;
            msg.className = `message ${type}`;
            setTimeout(() => {
                msg.className = 'message';
            }, 5000);
        }

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        }

        async function apiCall(endpoint, options = {}) {
            const response = await fetch(`${API_BASE}${endpoint}`, {
                ...options,
                headers: {
                    ...options.headers,
                },
            });
            if (response.status === 204) {
                return {};
            }
            const contentType = response.headers.get('content-type');
            let data;
            if (contentType && contentType.includes('application/json')) {
                data = await response.json();
            } else {
                const text = await response.text();
                throw new Error(`Erreur serveur (${response.status}): ${text.substring(0, 200)}`);
            }
            if (!response.ok) {
                throw new Error(data.error || `Erreur ${response.status}`);
            }
            return data;
        }

        async function uploadFile() {
            const fileInput = document.getElementById('fileInput');
            const pathInput = document.getElementById('filePath');
            const file = fileInput.files[0];
            
            if (!file) {
                showMessage('Veuillez sélectionner un fichier', 'error');
                return;
            }

            const formData = new FormData();
            formData.append('file', file);
            formData.append('filesystem', getFilesystem());
            if (pathInput.value.trim()) {
                formData.append('key', pathInput.value.trim());
            }

            try {
                const response = await fetch(`${API_BASE}/upload`, {
                    method: 'POST',
                    body: formData,
                });
                
                const contentType = response.headers.get('content-type');
                let data;
                
                if (contentType && contentType.includes('application/json')) {
                    data = await response.json();
                } else {
                    const text = await response.text();
                    throw new Error(`Erreur serveur (${response.status}): ${text.substring(0, 200)}`);
                }
                
                if (!response.ok) {
                    throw new Error(data.error || `Erreur ${response.status}`);
                }
                // Extraire uniquement le nom du fichier du chemin retourné
                const fileName = data.path.split('/').pop();
                showMessage(`Fichier uploadé avec succès : ${data.path}`);
                fileInput.value = '';
                // Afficher uniquement le nom du fichier dans le champ chemin
                pathInput.value = fileName;
                // Actualiser la liste après l'upload
                await loadFileList();
            } catch (error) {
                showMessage(`Erreur lors de l'upload : ${error.message}`, 'error');
            }
        }

        async function createDirectory() {
            const pathInput = document.getElementById('directoryPath');
            const path = pathInput.value.trim();
            
            if (!path) {
                showMessage('Veuillez entrer un nom de dossier', 'error');
                return;
            }

            try {
                await apiCall('/create-directory', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        filesystem: getFilesystem(),
                        path: path,
                    }),
                });
                showMessage(`Dossier créé avec succès : ${path}`);
                pathInput.value = '';
                // Actualiser la liste après la création
                await loadFileList();
            } catch (error) {
                showMessage(`Erreur lors de la création : ${error.message}`, 'error');
            }
        }

        async function loadFileList() {
            const pathInput = document.getElementById('listPath');
            const path = pathInput ? pathInput.value.trim() : '';
            const fileList = document.getElementById('fileList');
            if (!fileList) return;
            fileList.innerHTML = '<div class="loading"></div> Chargement...';

            try {
                const params = new URLSearchParams({ filesystem: getFilesystem() });
                if (path) params.append('path', path);
                const filterType = document.getElementById('listFilterType');
                if (filterType && filterType.value) {
                    params.append('type', filterType.value);
                }
                const sort = document.getElementById('listSort');
                if (sort && sort.value) {
                    params.append('sort', sort.value);
                }
                const data = await apiCall(`/list?${params.toString()}`);
                const items = data.items || [];
                if (items.length === 0) {
                    fileList.innerHTML = '<p style="color: #666;">Aucun fichier ou dossier.</p>';
                    return;
                }
                const pathForAttr = (s) => String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'");
                const formatSize = (bytes) => bytes == null ? '' : (bytes < 1024 ? bytes + ' o' : (bytes < 1024*1024 ? (bytes/1024).toFixed(1) + ' Ko' : (bytes/1024/1024).toFixed(1) + ' Mo'));
                fileList.innerHTML = items.map(item => {
                    const isDirectory = item.type === 'dir';
                    const pathStr = item.path;
                    const displayName = isDirectory ? pathStr.replace(/\/$/, '') : pathStr;
                    const itemClass = isDirectory ? 'file-item directory' : 'file-item';
                    const dirPath = isDirectory ? (path ? path + '/' + displayName : displayName) : '';
                    const openBtn = isDirectory
                        ? `<button class="open" onclick="openDirectory('${pathForAttr(dirPath)}')">Ouvrir</button>`
                        : '';
                    const metaParts = [];
                    if (!isDirectory) {
                        if (item.mimeType) metaParts.push(escapeHtml(item.mimeType));
                        if (item.size != null) metaParts.push(formatSize(item.size));
                    }
                    const meta = metaParts.length ? `<span class="file-meta">${metaParts.join(' · ')}</span>` : '';
                    return `
                        <div class="${itemClass}">
                            <span>${isDirectory ? '📁' : '📄'} ${escapeHtml(displayName)}</span>
                            ${meta}
                            <div class="actions">
                                ${openBtn}
                                <button class="rename" onclick="showRenameDialog('${pathForAttr(pathStr)}')">Renommer</button>
                                <button class="delete" onclick="deleteItem('${pathForAttr(pathStr)}')">Supprimer</button>
                            </div>
                        </div>
                    `;
                }).join('');
            } catch (error) {
                if (path && error.message.includes('not found')) {
                    if (pathInput) pathInput.value = '';
                    setTimeout(() => loadFileList(), 100);
                    return;
                }
                fileList.innerHTML = `<p style="color: #e74c3c;">Erreur : ${escapeHtml(error.message)}</p>`;
            }
        }

        let deleteInProgress = false;
        async function deleteItem(path) {
            if (deleteInProgress) return;
            if (!confirm(`Êtes-vous sûr de vouloir supprimer "${path}" ?`)) return;
            deleteInProgress = true;
            try {
                const normalizedPath = path.replace(/\/$/, '');
                const pathInput = document.getElementById('listPath');
                const currentPath = pathInput ? pathInput.value.trim() : '';
                const isCurrentDir = currentPath === normalizedPath || currentPath === normalizedPath + '/';
                const isParentDir = currentPath.startsWith(normalizedPath + '/');
                await apiCall('/delete', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ filesystem: getFilesystem(), path: normalizedPath }),
                });
                showMessage(`Supprimé avec succès : ${path}`);
                if (isCurrentDir || isParentDir) {
                    if (pathInput) pathInput.value = '';
                }
                await loadFileList();
            } catch (error) {
                showMessage(`Erreur lors de la suppression : ${error.message}`, 'error');
            } finally {
                deleteInProgress = false;
            }
        }

        function openDirectory(dirPath) {
            const pathInput = document.getElementById('listPath');
            if (pathInput) {
                pathInput.value = dirPath;
                loadFileList();
            }
        }

        function showRenameDialog(oldPath) {
            const normalized = oldPath.replace(/\/$/, '');
            const displayName = normalized.split('/').pop() || normalized;
            const newName = prompt(`Nouveau nom pour "${displayName}" :`, displayName);
            if (newName === null) return;
            const trimmed = newName.trim();
            if (trimmed === '' || trimmed === displayName) return;
            const parent = normalized.split('/').slice(0, -1).join('/');
            const targetPath = parent ? parent + '/' + trimmed : trimmed;
            renameItem(normalized, targetPath);
        }

        async function renameItem(oldPath, targetPath) {
            try {
                await apiCall('/rename', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        filesystem: getFilesystem(),
                        source: oldPath,
                        target: targetPath,
                    }),
                });
                showMessage(`Renommé avec succès : ${oldPath} → ${targetPath}`);
                await loadFileList();
            } catch (error) {
                showMessage(`Erreur lors du renommage : ${error.message}`, 'error');
            }
        }

        // Charger la liste au chargement de la page
        window.addEventListener('DOMContentLoaded', () => {
            loadFileList();
        });
    </script>
</body>
</html>
HTML;

        return new Response(str_replace('{$storagePath}', htmlspecialchars($storagePath, \ENT_QUOTES, 'UTF-8'), $html));
    }
}
