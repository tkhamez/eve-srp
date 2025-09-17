<?php

declare(strict_types=1);

namespace EveSrp\Service;

use EveSrp\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;

class ApiService
{
    private const URL_PART_KILL_MAILS = 'killmails';

    private string $esiBaseUrl;

    private string $killboardBaseUrl;

    private string $lastError = '';

    private mixed $esiUserAgent;

    private $esiCompatibilityDate;

    public function __construct(private readonly ClientInterface $httpClient, Settings $settings)
    {
        $this->esiBaseUrl = $settings['URLs']['esi'];
        $this->killboardBaseUrl = $settings['URLs']['zkillboard'];
        $this->esiUserAgent = $settings['HTTP_USER_AGENT'];
        $this->esiCompatibilityDate = $settings['ESI']['compatibility_date'];
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    /**
     * Makes an HTTP request and returns the decoded JSON response.
     *
     * All ESI requests are made with this method.
     *
     * @param string $url Full URL or path only for ESI requests.
     */
    public function getJsonData(string $url): array|\stdClass|null
    {
        $this->lastError = '';

        $options = ['headers' => []];
        if (!str_starts_with($url, 'http')) {
            $url = "$this->esiBaseUrl/$url";
            $options['headers']['X-Compatibility-Date'] = $this->esiCompatibilityDate;
            $options['headers']['User-Agent'] = $this->esiUserAgent;
        }

        try {
            $apiResponse = $this->httpClient->request('GET', $url, $options);
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
        if (($parts[1] ?? null) === self::URL_PART_KILL_MAILS) {
            return $parts[3] ?? null;
        }
        return null;
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

    public function getKillMailEsiUrlWithoutVersion(string $esiUrl): string
    {
        $temp = str_replace($this->esiBaseUrl, '', $esiUrl);
        $parts = explode('/', $temp);
        if (($parts[2] ?? null) === self::URL_PART_KILL_MAILS) {
            // Old URL with a version, remove it.
            unset($parts[1]);
        }
        return $this->esiBaseUrl . implode('/', $parts);
    }

    public function getKillIdFromEsiUrl(string $esiUrl): int
    {
        $temp = str_replace($this->esiBaseUrl, '', $esiUrl);
        $parts = explode('/', $temp);
        if (($parts[1] ?? null) === self::URL_PART_KILL_MAILS) {
            return (int) ($parts[2] ?? 0);
        }
        return 0;
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
        return "$this->esiBaseUrl/" . self::URL_PART_KILL_MAILS . "/";
    }
}
