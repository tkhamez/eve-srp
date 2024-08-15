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
        $this->esiBaseUrl = $settings['URLs']['esi'];
        $this->killboardBaseUrl = $settings['URLs']['zkillboard'];
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    public function getJsonData(string $url): array|\stdClass|null
    {
        $this->lastError = '';

        $url = str_starts_with($url, 'http') ? $url : "$this->esiBaseUrl/$url";
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
    public function getEsiUrlFromZKillboardUrl(string $url): ?string
    {
        $urlParts = explode('/', rtrim($url, '/'));
        $killId = end($urlParts);
        if (!is_numeric($killId)) {
            error_log(__METHOD__ . ': Invalid zKillboard URL ' . $url);
            return null;
        }

        $hash = $this->getEsiHashFromZKillboard((int)$killId);

        return $this->getEsiKillUrl((int)$killId, (string)$hash);
    }

    public function getHashFromEsiUrl(string $esiUrl): ?string
    {
        $temp = str_replace($this->esiBaseUrl, '', $esiUrl);
        $parts = explode('/', $temp);
        return !empty($parts[4]) ? $parts[4] : null;
    }

    public function getEsiHashFromZKillboard(int $killId): ?string
    {
        if ($this->killboardBaseUrl) {
            $killboardData = $this->getJsonData("$this->killboardBaseUrl/api/killID/$killId/");
        }

        if (!isset($killboardData[0])) {
            return null;
        }

        return $killboardData[0]->zkb->hash;
    }

    public function getKillIdFromEsiUrl(string $esiUrl): int
    {
        $temp = str_replace($this->esiBaseUrl, '', $esiUrl);
        $parts = explode('/', $temp);
        return !empty($parts[3]) ? (int)$parts[3] : 0;
    }

    public function getEsiKillUrl(int $killId, string $hash): string
    {
        if ($hash) {
            return "{$this->getEsiKillUrlBase()}$killId/$hash/";
        }
        return '';
    }

    public function hasKillboardUrl(): bool
    {
        return !empty($this->killboardBaseUrl);
    }

    public function getKillboardUrl(int $killId): string
    {
        if ($this->killboardBaseUrl) {
            return "$this->killboardBaseUrl/kill/$killId/";
        }
        return '';
    }

    private function getEsiKillUrlBase(): string
    {
        return "$this->esiBaseUrl/latest/killmails/";
    }
}
