<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ClientException;
use DiDom\Document;

class GetHttpInfo
{
    public string $name = '';
    public $client;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->client = new Client();
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        $res = 0;
        try {
            $res = $this->client->request('GET', $this->name);
            return $res->getStatusCode();
        } catch (RequestException | ClientException $e) {
            return 'error';
        }
    }

    /**
     * @return mixed
     */
    public function getHtmlData()
    {
        try {
            $res = $this->client->request('GET', $this->name);
            $htmlBody = $res->getBody();

            $document = new Document((string) $htmlBody);
            $h1 = optional($document->first('h1'))->text();
            $title = optional($document->first('title'))->text();
            $description = optional($document->first('meta[name="description"]'))->getAttribute('content');

            $result = [
                'h1' => $h1,
                'title' => $title,
                'description' => $description
            ];
            return $result;
        } catch (RequestException | ClientException $e) {
            return 'error';
        }
    }
}
