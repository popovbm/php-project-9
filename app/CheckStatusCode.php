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

    /**
     * @return mixed
     */
    public function check()
    {
        $client = new Client();
        $res = 0;
        try {
            $res = $client->request('GET', $this->name);
        } catch (ClientException | RequestException $e) {
            return $e->getMessage();
        }

        return $res->getStatusCode();
    }
}
