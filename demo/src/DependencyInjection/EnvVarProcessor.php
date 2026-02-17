<?php

declare(strict_types=1);

namespace App\DependencyInjection;

use Symfony\Component\DependencyInjection\EnvVarProcessorInterface;

/**
 * Processeur d'environnement pour résoudre %kernel.project_dir% dans STORAGE_PATH.
 */
final class EnvVarProcessor implements EnvVarProcessorInterface
{
    public function __construct(
        private readonly string $projectDir,
    ) {
    }

    public function getEnv(string $prefix, string $name, \Closure $getEnv): mixed
    {
        try {
            $env = $getEnv($name);
        } catch (\Symfony\Component\DependencyInjection\Exception\EnvNotFoundException $e) {
            // Si la variable n'existe pas, retourner 'default' pour que le resolver utilise la valeur par défaut
            return 'default';
        }
        
        // Si la variable contient %kernel.project_dir%, la remplacer
        if (\is_string($env) && str_contains($env, '%kernel.project_dir%')) {
            return str_replace('%kernel.project_dir%', $this->projectDir, $env);
        }
        
        return $env;
    }

    public static function getProvidedTypes(): array
    {
        return [
            'default' => 'string',
        ];
    }
}
