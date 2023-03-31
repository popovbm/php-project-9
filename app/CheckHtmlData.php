<?php

namespace Hexlet\Code;

use DiDom\Document;
use GuzzleHttp\Client;
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
     * @return mixed
     */
    public function getHtmlData()
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
        } catch (ClientException $e) {
            return 'ClientException';
        } catch (RequestException $e) {
            return 'RequestException';
        }
    }
}
