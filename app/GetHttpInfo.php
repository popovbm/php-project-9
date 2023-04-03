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
        try {
            $res = $this->client->request('GET', $this->name);
            $htmlBody = $res->getBody();
            $document = new Document((string) $htmlBody);

            $status_code = $res->getStatusCode();
            $h1 = optional($document->first('h1'))->text();
            $title = optional($document->first('title'))->text();
            $description = optional($document->first('meta[name="description"]'))->getAttribute('content');

            $result = [
                'status_code' => $status_code,
                'h1' => $h1,
                'title' => $title,
                'description' => $description
            ];

            return $result;
        } catch (RequestException $e) {
            $result['error'] = 'RequestError';
            return $result;
        } catch (ConnectException $e) {
            return 'ConnectError';
        }
    }
}
