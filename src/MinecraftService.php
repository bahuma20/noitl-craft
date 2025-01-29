<?php

namespace App;

use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
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
//                'capath' => '/var/run/secrets/kubernetes.io/serviceaccount/ca.crt',
                'verify_peer' => false,
                'verify_host' => false,
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

        try {

            $this->k8s->request('PATCH', '/apis/apps/v1/namespaces/' . $namespace . '/statefulsets/' . $statefulset, [
                'headers' => [
                    'Content-Type' => ' application/strategic-merge-patch+json',
                ]
            ]);
        } catch (TransportExceptionInterface|ClientException $e) {
            print 'There was an error starting the server\n\n';
            print 'Exception: ' . $e->getMessage() . '\n\n';
            print 'Status code: ' . $e->getStatusCode() . '\n\n';
            $content = $e->getResponse()->getContent(false);
            print 'Body: ' . $content . '\n\n';
            print 'Trace' . $e->getTraceAsString();
            die();
        }
    }

    public function getStatus(): ServerStatus
    {
        return new ServerStatus(ServerState::STOPPED, 0);
    }
}