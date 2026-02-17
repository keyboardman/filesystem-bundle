<?php

declare(strict_types=1);

namespace App;

use Keyboardman\FilesystemBundle\KeyboardmanFilesystemBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

final class Kernel extends BaseKernel
{
    public function registerBundles(): array
    {
        return [
            new FrameworkBundle(),
            new KeyboardmanFilesystemBundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load($this->getProjectDir() . '/config/' . $this->environment . '.yaml');
    }

    public function getProjectDir(): string
    {
        return \dirname(__DIR__);
    }
}
