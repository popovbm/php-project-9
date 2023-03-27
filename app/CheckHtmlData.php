<?php

namespace Hexlet\Code;

use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\RequestException;

class CheckHtmlData
{
    public string $url = '';

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return array<mixed>
     */
    public function getHtmlData(): array
    {
        $client = new Client();
        try {
            $res = $client->request('GET', $this->url);
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
        } catch (ClientException | RequestException $e) {
            echo $e->getRequest();
            echo $e->getResponse();
        }
    }
}
