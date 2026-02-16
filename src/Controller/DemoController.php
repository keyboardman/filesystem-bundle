<?php

declare(strict_types=1);

namespace Keyboardman\FilesystemBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DemoController
{
    #[Route('/demo', name: 'keyboardman_filesystem_demo', methods: ['GET'])]
    public function __invoke(Request $request): Response
    {
        $baseUrl = $request->getSchemeAndHttpHost() . $request->getBasePath();
        $api = rtrim($baseUrl, '/') . '/api/filesystem';

        $html = <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>File Storage API – Démo</title>
    <style>
        body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem; }
        h1 { font-size: 1.5rem; }
        section { margin: 1.5rem 0; }
        label { display: block; margin-top: 0.5rem; }
        input[type="text"], input[type="file"] { width: 100%; margin-top: 0.25rem; }
        button { margin-top: 0.5rem; padding: 0.5rem 1rem; cursor: pointer; }
        pre { background: #f5f5f5; padding: 1rem; overflow: auto; font-size: 0.875rem; }
        .endpoint { font-weight: bold; color: #055; }
    </style>
</head>
<body>
    <h1>File Storage API – Démo</h1>
    <p>Cette page permet de tester les endpoints de l’API (upload, rename, move, delete).</p>

    <section>
        <h2>Upload (un fichier)</h2>
        <form id="form-upload" enctype="multipart/form-data">
            <label>Filesystem <input type="text" name="filesystem" value="default" /></label>
            <label>Clé (optionnel) <input type="text" name="key" placeholder="chemin/fichier.txt" /></label>
            <label>Fichier <input type="file" name="file" required /></label>
            <button type="submit">Envoyer</button>
        </form>
        <pre id="out-upload"></pre>
    </section>

    <section>
        <h2>Upload multiple</h2>
        <form id="form-upload-multiple" enctype="multipart/form-data">
            <label>Filesystem <input type="text" name="filesystem" value="default" /></label>
            <label>Fichiers <input type="file" name="files[]" multiple required /></label>
            <button type="submit">Envoyer</button>
        </form>
        <pre id="out-upload-multiple"></pre>
    </section>

    <section>
        <h2>Renommer</h2>
        <form id="form-rename">
            <label>Filesystem <input type="text" name="filesystem" value="default" /></label>
            <label>Source <input type="text" name="source" required placeholder="ancien.txt" /></label>
            <label>Cible <input type="text" name="target" required placeholder="nouveau.txt" /></label>
            <button type="submit">Renommer</button>
        </form>
        <pre id="out-rename"></pre>
    </section>

    <section>
        <h2>Déplacer</h2>
        <form id="form-move">
            <label>Filesystem <input type="text" name="filesystem" value="default" /></label>
            <label>Source <input type="text" name="source" required /></label>
            <label>Cible <input type="text" name="target" required /></label>
            <button type="submit">Déplacer</button>
        </form>
        <pre id="out-move"></pre>
    </section>

    <section>
        <h2>Supprimer</h2>
        <form id="form-delete">
            <label>Filesystem <input type="text" name="filesystem" value="default" /></label>
            <label>Path <input type="text" name="path" required placeholder="fichier.txt" /></label>
            <button type="submit">Supprimer</button>
        </form>
        <pre id="out-delete"></pre>
    </section>

    <section>
        <h2>Endpoints</h2>
        <ul>
            <li class="endpoint">POST {$api}/upload</li>
            <li class="endpoint">POST {$api}/upload-multiple</li>
            <li class="endpoint">POST {$api}/rename</li>
            <li class="endpoint">POST {$api}/move</li>
            <li class="endpoint">POST {$api}/delete</li>
        </ul>
    </section>

    <script>
        const api = '{$api}';
        function postForm(url, form, outId) {
            const fd = new FormData(form);
            fetch(url, { method: 'POST', body: fd })
                .then(r => r.text())
                .then(t => { document.getElementById(outId).textContent = t || '(empty)'; })
                .catch(e => { document.getElementById(outId).textContent = 'Error: ' + e; });
        }
        function postJson(url, data, outId) {
            const body = new URLSearchParams(data);
            fetch(url, { method: 'POST', body, headers: { 'Content-Type': 'application/x-www-form-urlencoded' } })
                .then(r => r.text())
                .then(t => { document.getElementById(outId).textContent = t || '(empty)'; })
                .catch(e => { document.getElementById(outId).textContent = 'Error: ' + e; });
        }
        document.getElementById('form-upload').onsubmit = e => { e.preventDefault(); postForm(api + '/upload', e.target, 'out-upload'); };
        document.getElementById('form-upload-multiple').onsubmit = e => { e.preventDefault(); const f = e.target; const fd = new FormData(f); fetch(api + '/upload-multiple', { method: 'POST', body: fd }).then(r => r.text()).then(t => document.getElementById('out-upload-multiple').textContent = t || '(empty)'); };
        document.getElementById('form-rename').onsubmit = e => { e.preventDefault(); postJson(api + '/rename', new FormData(e.target), 'out-rename'); };
        document.getElementById('form-move').onsubmit = e => { e.preventDefault(); postJson(api + '/move', new FormData(e.target), 'out-move'); };
        document.getElementById('form-delete').onsubmit = e => { e.preventDefault(); postJson(api + '/delete', new FormData(e.target), 'out-delete'); };
    </script>
</body>
</html>
HTML;

        return new Response($html, Response::HTTP_OK, ['Content-Type' => 'text/html; charset=UTF-8']);
    }
}
