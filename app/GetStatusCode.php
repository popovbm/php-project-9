<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use Psr\Http\Message\MessageInterface;

class GetStatusCode
{
    public string $name = '';

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return mixed
     */
    public function get()
    {
        $client = new Client();
        $res = 0;
        try {
            $res = $client->request('GET', $this->name, ['http_errors' => true]);
            return $res->getStatusCode();
        } catch (ConnectException | ClientException | RequestException $e) {
            return $e->getMessage();
        }
    }
}
