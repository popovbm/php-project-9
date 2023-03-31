<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;

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
            $res = $client->request('GET', $this->name);
            return $res->getStatusCode();
        } catch (ClientException $e) {
            return 'ClientException';
        } catch (RequestException $e) {
            return 'RequestException';
        }
    }
}
