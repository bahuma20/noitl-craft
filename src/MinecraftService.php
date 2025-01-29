<?php

namespace App;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class MinecraftService
{
    private HttpClientInterface $k8s;

    public function __construct(HttpClientInterface $client)
    {
        $tokenFile = '/var/run/secrets/kubernetes.io/serviceaccount/token';

        if (file_exists($tokenFile)) {
            $token = file_get_contents($tokenFile);
        } else {
            $token = null;
        }

        $this->k8s = $client
            ->withOptions([
                'capath' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
                'headers' => [
                    'Authorization' => 'Bearer ' . $token,
                ],
                'base_uri' => 'https://kubernetes.default.svc',
            ]);
    }

    public function start()
    {
        $namespace = $_ENV['APP_NAMESPACE'];
        $statefulset = $_ENV['APP_STATEFULSET'];

        $response = $this->k8s->request('PATCH', '/apis/apps/v1/namespaces/' . $namespace . '/statefulsets/' . $statefulset, [
            'headers' => [
                'Content-Type' => ' application/strategic-merge-patch+json',
            ]
        ]);

        $success = $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;

        if (!$success) {
            print 'There was an error starting the server\n\n';
            print 'Status code: ' . $response->getStatusCode() . '\n\n';
            print 'Body: ' . $response->getContent() . '\n\n';
            die();
        }
    }

    public function getStatus(): ServerStatus
    {
        return new ServerStatus(ServerState::STOPPED, 0);
    }
}