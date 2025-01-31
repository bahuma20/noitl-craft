<?php

namespace App;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;
use xPaw\MinecraftQuery;

class MinecraftService
{
    protected HttpClientInterface $k8s;
    protected MinecraftQuery $query;

    public function __construct(HttpClientInterface $client, private LoggerInterface $logger, protected AppConfiguration $config)
    {
        if ($this->config->isDevMode()) {
            $token = null;
        } else {
            $tokenFile = '/var/run/secrets/kubernetes.io/serviceaccount/token';
            $token = file_get_contents($tokenFile);
        }

        $this->k8s = $client
            ->withOptions([
//                'capath' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
                'verify_peer' => false,
                'verify_host' => false,
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'base_uri' => 'https://kubernetes.default.svc',
            ]);

        $this->query = new MinecraftQuery();
    }

    public function start()
    {
        $namespace = $this->config->getKubernetesNamespace();
        $statefulset = $this->config->getKubernetesStatefulsetName();

        try {
            $response = $this->k8s->request('PATCH', '/apis/apps/v1/namespaces/' . $namespace . '/statefulsets/' . $statefulset, [
                'headers' => [
                    'Content-Type' => 'application/strategic-merge-patch+json',
                ],
                'body' => '{"spec":{"replicas":1}}'
            ]);
        } catch (TransportExceptionInterface|ClientException $e) {
            http_send_status(500);
            print 'There was an error starting the server\n\n';
            print 'Exception: ' . $e->getMessage() . '\n\n';
            print 'Status code: ' . $e->getResponse()->getStatusCode() . '\n\n';
            $content = $e->getResponse()->getContent(false);
            print 'Body: ' . $content . '\n\n';
            print 'Trace' . $e->getTraceAsString();
            die();
        }
    }

    public function getStatus(): ServerStatus
    {
        $kubernetesStatus = $this->getKubernetesStatus();
        $this->logger->debug('Kubernetes status | ' . $kubernetesStatus->name);

        switch ($kubernetesStatus) {
            case KubernetesState::STOPPING:
            case KubernetesState::STOPPED:
                return new ServerStatus(ServerState::STOPPED, 0);
            case KubernetesState::RUNNING:
                $minecraftStatus = $this->getMinecraftStatus();
                $this->logger->debug('Minecraft status | state: ' . $minecraftStatus->state->name . ' playerCount: ' . $minecraftStatus->playerCount);

                switch ($minecraftStatus->state) {
                    case MinecraftState::AVAILABLE:
                        return new ServerStatus(ServerState::RUNNING, $minecraftStatus->playerCount);
                    case MinecraftState::UNAVAILABLE:
                        return new ServerStatus(ServerState::STARTING, 0);
                }
                break;
            case KubernetesState::STARTING:
                return new ServerStatus(ServerState::STARTING, 0);
        }

        return new ServerStatus(ServerState::STOPPED, 0);
    }

    protected function getKubernetesStatus(): KubernetesState
    {
        if ($this->config->isDevMode()) {
            return KubernetesState::STOPPED;
        }

        $namespace = $this->config->getKubernetesNamespace();
        $statefulset = $this->config->getKubernetesStatefulsetName();

        try {
            $response = $this->k8s->request('GET', '/apis/apps/v1/namespaces/' . $namespace . '/statefulsets/' . $statefulset, [
                'headers' => [
                    'Content-Type' => 'application/strategic-merge-patch+json',
                ],
                'body' => '{"spec":{"replicas":1}}'
            ]);

            $data = json_decode($response->getContent(), false, 512, JSON_THROW_ON_ERROR);

            $desiredReplicas = $data->spec->replicas;
            $availableReplicas = $data->status->availableReplicas;

            $this->logger->debug('Checking kubernetes state | desiredReplicas: ' . $desiredReplicas . ' availableReplicas: ' . $availableReplicas);

            if ($desiredReplicas == 0 && $availableReplicas > 0) {
                return KubernetesState::STOPPING;
            }

            if ($desiredReplicas == 0 && $availableReplicas == 0) {
                return KubernetesState::STOPPED;
            }

            if ($desiredReplicas > 0 && $availableReplicas != $desiredReplicas) {
                return KubernetesState::STARTING;
            }

            if ($desiredReplicas > 0 && $availableReplicas == $desiredReplicas) {
                return KubernetesState::RUNNING;
            }

            throw new \RuntimeException('Invalid state of Kubernetes. Desired replicas: ' . $desiredReplicas . ', available replicas: ' . $availableReplicas);
        } catch (ClientExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|\JsonException $e) {
            $this->logger->error('Error while fetching Kubernetes status: ' . $e->getMessage(), ['exception' => $e]);
            throw new \RuntimeException('Error while fetching Kubernetes status', 0, $e);
        }
    }

    protected function getMinecraftStatus(): MinecraftStatus
    {
        $serverAddress = $this->config->getMinecraftServerAddress();
        $serverPort = $this->config->getMinecraftServerPort();

        try {
            $query = new MinecraftPing($serverAddress, $serverPort);
            $result = $query->Query();

            return new MinecraftStatus(MinecraftState::AVAILABLE, $result['players']['online']);
        } catch (MinecraftPingException $e) {
            $this->logger->warning('Error getting MinecraftStatus: ' . $e->getMessage(), ['exception' => $e]);
            return new MinecraftStatus(MinecraftState::UNAVAILABLE, 0);
        } finally {
            if (isset($query)) {
                $query->close();
            }
        }
    }

    public function getPlayerCount()
    {
        return $this->getMinecraftStatus()->playerCount;
    }
}