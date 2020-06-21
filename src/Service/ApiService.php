<?php

namespace EveSrp\Service;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Container\ContainerInterface;

class ApiService
{
    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $esiBaseUrl;

    /**
     * @var string
     */
    private $killboardBaseUrl;

    public function __construct(ContainerInterface $container)
    {
        $this->httpClient = $container->get(ClientInterface::class);
        $this->esiBaseUrl = $container->get('settings')['ESI_BASE_URL'];
        $this->killboardBaseUrl = $container->get('settings')['ZKILLBOARD_BASE_URL'];
    }

    /**
     * @return \stdClass|array|null
     */
    public function getJsonData(string $url)
    {
        $url = strpos($url, 'http') === 0 ? $url : $this->esiBaseUrl . $url;
        try {
            $apiResponse = $this->httpClient->request('GET', $url);
        } catch (GuzzleException $e) {
            error_log('getEsiData() request: ' . $e->getMessage());
            return null;
        }
        $apiData = \json_decode($apiResponse->getBody()->__toString());
        if ($apiData === null) {
            error_log('getEsiData() json: ' . json_last_error_msg());
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
        if (! is_numeric($killId)) {
            error_log('getEsiUrlFromKillboard: Invalid kill ID: ' . $killId);
            return null;
        }

        $killboardData = $this->getJsonData("{$this->killboardBaseUrl}api/killID/$killId/");
        if ($killboardData === null || ! isset($killboardData[0])) {
            return null;
        }

        return "{$this->esiBaseUrl}latest/killmails/$killId/{$killboardData[0]->zkb->hash}/";
    }
}
