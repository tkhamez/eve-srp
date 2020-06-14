<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Controller\Traits\RequestParameter;
use Brave\EveSrp\Controller\Traits\TwigResponse;
use Brave\EveSrp\FlashMessage;
use Brave\EveSrp\Model\Character;
use Brave\EveSrp\Model\Permission;
use Brave\EveSrp\Model\Request;
use Brave\EveSrp\Repository\CharacterRepository;
use Brave\EveSrp\Repository\DivisionRepository;
use Brave\EveSrp\Type;
use Brave\EveSrp\UserService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class SubmitController
{
    use RequestParameter;
    use TwigResponse;

    /**
     * @var UserService
     */
    private $userService;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var DivisionRepository
     */
    private $divisionRepository;

    /**
     * @var CharacterRepository
     */
    private $characterRepository;

    /**
     * @var FlashMessage
     */
    private $flashMessage;

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

    /**
     * @var string|null
     */
    private $inputDivision;

    /**
     * @var string|null
     */
    private $inputUrl;

    /**
     * @var string|null
     */
    private $inputDetails;

    public function __construct(ContainerInterface $container)
    {
        $this->userService = $container->get(UserService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->divisionRepository = $container->get(DivisionRepository::class);
        $this->characterRepository = $container->get(CharacterRepository::class);
        $this->flashMessage = $container->get(FlashMessage::class);
        $this->httpClient = $container->get(ClientInterface::class);
        $this->esiBaseUrl = $container->get('settings')['ESI_BASE_URL'];
        $this->killboardBaseUrl = $container->get('settings')['KILLBOARD_BASE_URL'];

        $this->twigResponse($container->get(Environment::class));
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function showForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->render($response, 'pages/submit.twig', [
            'divisions' => $this->userService->getDivisionsWithRoles([Permission::SUBMIT]),
            'selectedDivision' => $this->inputDivision,
            'url' => $this->inputUrl,
            'details' => $this->inputDetails,
        ]);
    }

    public function submitForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->inputDivision = (int) $this->paramPost($request, 'division');
        $this->inputUrl = (string) $this->paramPost($request, 'url');
        $this->inputDetails = (string) $this->paramPost($request, 'details');

        if (($srpRequest = $this->createSrpRequest()) !== null) {
            return $response->withHeader('Location', "/request/{$srpRequest->getId()}/show");
        } else {
            return $this->showForm($request, $response);
        }
    }

    private function createSrpRequest(): ?Request
    {
        if ($this->inputDivision <= 0 || empty($this->inputUrl) || empty($this->inputDetails)) {
            $this->flashMessage->addMessage('Please fill in all fields.', FlashMessage::TYPE_WARNING);
            return null;
        }

        $user = $this->userService->getAuthenticatedUser();
        if ( ! $user) {
            $this->flashMessage->addMessage('Logged in user not found.', FlashMessage::TYPE_WARNING);
            return null;
        }

        $division = $this->divisionRepository->find($this->inputDivision);
        if (! $division || ! $this->userService->hasDivisionRole($division->getId(), Permission::SUBMIT)) {
            $this->flashMessage->addMessage('Invalid division.', FlashMessage::TYPE_WARNING);
            return null;
        }

        $request = new Request();
        $request
            ->setCreated(new \DateTime())
            ->setStatus(Type::EVALUATING)
            ->setSubmitter($user)
            ->setDivision($division)
            ->setDetails($this->inputDetails);

        if (strpos($this->inputUrl, $this->esiBaseUrl) === 0) {
            $esiUrl = $this->inputUrl;
        } else {
            $request->setKillboardUrl($this->inputUrl);
            $esiUrl = $this->getEsiUrlFromKillboard($this->inputUrl);
        }
        if (! $esiUrl) {
            $this->flashMessage->addMessage('Could not get ESI URL.', FlashMessage::TYPE_WARNING);
            return null;
        }

        if (! $this->setDataFromEsi($request, $esiUrl)) {
            return null;
        }

        $request->setEsiLink($esiUrl);

        $this->entityManager->persist($request);
        $this->entityManager->flush();
        return $request;
    }

    private function getEsiUrlFromKillboard(string $url): ?string
    {
        # e.g. from https://zkillboard.com/kill/82474608/
        # to https://zkillboard.com/api/killID/82474608/
        # to https://esi.evetech.net/latest/killmails/82474608/1db01ff6c95dc8b750e63a51796f18f5cce5b774/

        $killId = end(explode('/', rtrim($url, '/')));

        $killboardData = $this->getApiData("https://zkillboard.com/api/killID/$killId/", 'zKillboard');
        if ($killboardData === null || ! isset($killboardData[0])) {
            return null;
        }

        return "{$this->esiBaseUrl}latest/killmails/$killId/{$killboardData[0]->zkb->hash}/";
    }

    private function setDataFromEsi(Request $request, string $url): bool
    {
        $killMailData = $this->getApiData($url, 'ESI kill mail');
        if ($killMailData === null) {
            return false;
        }

        try {
            $killTime = new \DateTime($killMailData->killmail_time);
        } catch (\Exception $e) {
            $this->flashMessage->addMessage('Could not read kill mail time.', FlashMessage::TYPE_WARNING);
            return false;
        }

        $pilot = $this->getPilot($killMailData->victim->character_id);
        if (! $pilot) {
            $this->flashMessage->addMessage(
                'Invalid victim. You can only submit requests for your own characters',
                FlashMessage::TYPE_WARNING
            );
            return false;
        }

        $shipData = $this->getApiData(
            "latest/universe/types/{$killMailData->victim->ship_type_id}/?language=en-us",
            'ESI ship type'
        );
        if ($shipData === null) {
            return false;
        }

        $systemData = $this->getApiData(
            "latest/universe/systems/{$killMailData->solar_system_id}/?language=en-us",
            'ESI solar system'
        );
        if ($systemData === null) {
            return false;
        }

        $corporationData = $this->getApiData(
            "latest/corporations/{$killMailData->victim->corporation_id}/",
            'ESI corporation'
        );
        if ($corporationData === null) {
            return false;
        }

        $allianceData = null;
        if ($corporationData->alliance_id) {
            $allianceData = $this->getApiData("latest/alliances/{$corporationData->alliance_id}/", 'ESI alliances');
            if ($allianceData === null) {
                return false;
            }
        }

        $request
            ->setPilot($pilot)
            ->setShip($shipData->name)
            ->setKillTime($killTime)
            ->setSolarSystem($systemData->name)
            ->setCorporation($corporationData->name)
            ->setAlliance($allianceData ? $allianceData->name : null);
        if (! $request->getKillboardUrl()) {
            $request->setKillboardUrl("{$this->killboardBaseUrl}kill/{$killMailData->killmail_id}/");
        }

        return true;
    }

    private function getPilot(int $pilotId): ?Character
    {
        $pilot = $this->characterRepository->find($pilotId);
        if (! $pilot) {
            return null;
        }

        foreach ($this->userService->getAuthenticatedUser()->getCharacters() as $character) {
            if ($character->getId() === $pilot->getId()) {
                return $pilot;
            }
        }

        return null;
    }

    /**
     * @return \stdClass|array|null
     */
    private function getApiData(string $url, string $ident)
    {
        $url = strpos($url, 'http') === 0 ? $url : $this->esiBaseUrl . $url;
        try {
            $apiResponse = $this->httpClient->request('GET', $url);
        } catch (GuzzleException $e) {
            error_log('getEsiData: ' . $e->getMessage());
            $this->flashMessage->addMessage("API error ($ident).", FlashMessage::TYPE_WARNING);
            return null;
        }
        $apiData = \json_decode($apiResponse->getBody()->__toString());
        if ($apiData === null) {
            $this->flashMessage->addMessage("Could not parse API data ($ident).", FlashMessage::TYPE_WARNING);
            return null;
        }

        return $apiData;
    }
}
