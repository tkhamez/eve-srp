<?php

declare(strict_types=1);

namespace EveSrp\Service;

use EveSrp\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class ApiService
{
    private string $esiBaseUrl;

    private string $killboardBaseUrl;

    private string $lastError = '';

    public function __construct(private ClientInterface $httpClient, Settings $settings)
    {
        $this->esiBaseUrl = $settings['ESI_BASE_URL'];
        $this->killboardBaseUrl = $settings['ZKILLBOARD_BASE_URL'];
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getJsonData(string $url): array|\stdClass|null
    {
        $this->lastError = '';

        $url = str_starts_with($url, 'http') ? $url : $this->esiBaseUrl . $url;
        try {
            $apiResponse = $this->httpClient->request('GET', $url);
        } catch (GuzzleException $e) {
            $this->lastError = $e->getMessage();
            error_log(__METHOD__ . " request: $this->lastError");
            return null;
        }
        $apiData = \json_decode($apiResponse->getBody()->__toString());
        if ($apiData === null) {
            $this->lastError = json_last_error_msg();
            error_log(__METHOD__ . " json: $this->lastError");
            return null;
        }

        return $apiData;
    }

    /**
     * @param string $url e.g. https://zkillboard.com/kill/82474608/
     * @return string|null
     */
    public function getEsiUrlFromKillboard(string $url): ?string
    {
        $urlParts = explode('/', rtrim($url, '/'));
        $killId = end($urlParts);
        if (!is_numeric($killId)) {
            error_log(__METHOD__ . ': Invalid kill ID: ' . $killId);
            return null;
        }

        if ($this->killboardBaseUrl) {
            $killboardData = $this->getJsonData("$this->killboardBaseUrl/api/killID/$killId/");
        } else {
            // Use domain from $url
            $domain = "$urlParts[0]//$urlParts[2]";
            $killboardData = $this->getJsonData("$domain/api/killID/$killId/");
        }
        if ($killboardData === null || !isset($killboardData[0])) {
            return null;
        }

        return "{$this->esiBaseUrl}latest/killmails/$killId/{$killboardData[0]->zkb->hash}/";
    }
}
