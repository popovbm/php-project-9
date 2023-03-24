<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;

class CheckUrl
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
        } catch (ClientException $e) {
            echo Psr7\Message::toString($e->getRequest());
            echo Psr7\Message::toString($e->getResponse());
        }

        return $res->getStatusCode();
    }
}
