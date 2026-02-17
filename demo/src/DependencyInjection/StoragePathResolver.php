<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * Résout le chemin de stockage en gérant les variables d'environnement et les valeurs par défaut.
 */
final class StoragePathResolver
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag,
    ) {
    }

    public function resolve(): string
    {
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        
        // Essayer de récupérer STORAGE_PATH depuis les variables d'environnement
        $storagePath = $_ENV['STORAGE_PATH'] ?? $_SERVER['STORAGE_PATH'] ?? null;
        
        // Si STORAGE_PATH n'est pas définie, utiliser la valeur par défaut
        if ($storagePath === null || $storagePath === '') {
            $storagePath = '%kernel.project_dir%/var/storage';
        }
        
        // Remplacer %kernel.project_dir% par le chemin réel
        return str_replace('%kernel.project_dir%', $projectDir, $storagePath);
    }
}
