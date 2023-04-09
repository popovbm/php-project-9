<?php

namespace Hexlet\Code;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use DiDom\Document;

class GetHttpInfo
{
    public string $name = '';
    public Client $client;
    public Client $res;

    public function __construct(string $name)
    {
        $this->name = $name;
        $this->client = new Client();
    }

    /**
     * @return mixed
     */
    public function get()
    {
        try {
            $res = $this->client->get($this->name);
        } catch (RequestException $e) {
            $res = $e->getResponse();
        } catch (ConnectException $e) {
            return 'ConnectError';
        }
        $htmlBody = !is_null($res) ? $res->getBody() : '';
        $document = new Document((string) $htmlBody);
        $status_code = !is_null($res) ? $res->getStatusCode() : null;
        $h1 = optional($document->first('h1'))->text() ?? '';
        $title = optional($document->first('title'))->text() ?? '';
        $description = optional($document->first('meta[name="description"]'))->getAttribute('content') ?? '';

        $result = [
            'status_code' => $status_code,
            'h1' => $h1,
            'title' => $title,
            'description' => $description
        ];

        return $result;
    }
}
