<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\BadResponseException;

class CheckStatusCode
{
    public $name;

    public function __construct($name)
    {
        $this->name = $name;
    }

    public function check()
    {
        $client = new Client();
        try {
            $res = $client->request('GET', $this->name);
        } catch (ClientException | RequestException $e) {
            echo Psr7\Message::toString($e->getRequest());
            echo Psr7\Message::toString($e->getResponse());
        }

        return $res->getStatusCode();
    }
}
