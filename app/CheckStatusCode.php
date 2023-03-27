<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\MessageInterface;

class CheckStatusCode
{
    public string $name = '';

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function check(): mixed
    {
        $client = new Client();
        $res = 0;
        try {
            $res = $client->request('GET', $this->name);
        } catch (ClientException | RequestException $e) {
            echo Psr7\Message::toString($e->getRequest());
            echo Psr7\Message::toString($e->getResponse());
        }

        return $res->getStatusCode();
    }
}
