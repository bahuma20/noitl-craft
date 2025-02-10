<?php

namespace App;

class AppConfiguration
{
    private bool $isDevMode;

    private string $kubernetesNamespace;
    private string $kubernetesStatefulsetName;

    private string $minecraftServerAddress;
    private int $minecraftServerPort;

    public function __construct()
    {
        $this->kubernetesNamespace = $this->getRequiredEnv('APP_KUBERNETES_NAMESPACE');
        $this->kubernetesStatefulsetName = $this->getRequiredEnv('APP_KUBERNETES_STATEFULSET_NAME');
        $this->minecraftServerAddress = $this->getRequiredEnv('APP_MINECRAFT_SERVER_ADDRESS');
        $this->minecraftServerPort = intval($this->getRequiredEnv('APP_MINECRAFT_SERVER_PORT'));

        $this->isDevMode = $this->getRequiredEnv('APP_ENV') == 'dev';
    }

    private function getRequiredEnv(string $name)
    {
        $value = $_ENV[$name];

        if (empty($value)) {
            throw new \RuntimeException('Missing environment variable: ' . $name);
        }

        return $value;
    }

    public function getKubernetesNamespace(): string
    {
        return $this->kubernetesNamespace;
    }

    public function getKubernetesStatefulsetName(): string
    {
        return $this->kubernetesStatefulsetName;
    }

    public function getMinecraftServerAddress(): string
    {
        return $this->minecraftServerAddress;
    }

    public function getMinecraftServerPort(): int
    {
        return $this->minecraftServerPort;
    }

    public function isDevMode(): bool
    {
        return $this->isDevMode;
    }
}