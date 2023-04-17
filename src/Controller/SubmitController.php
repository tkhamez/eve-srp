<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Controller\Traits\RequestParameter;
use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\FlashMessage;
use EveSrp\Model\Character;
use EveSrp\Model\Permission;
use EveSrp\Model\Request;
use EveSrp\Repository\CharacterRepository;
use EveSrp\Repository\DivisionRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Service\ApiService;
use EveSrp\Service\UserService;
use EveSrp\Settings;
use EveSrp\Type;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class SubmitController
{
    use RequestParameter;
    use TwigResponse;

    private string $esiBaseUrl;

    private string $killboardBaseUrl;

    private ?int $inputDivision = null;

    private ?string $inputUrl = null;

    private ?string $inputDetails = null;

    public function __construct(
        private UserService $userService,
        private ApiService $apiService,
        private EntityManagerInterface $entityManager,
        private DivisionRepository $divisionRepository,
        private CharacterRepository $characterRepository,
        private RequestRepository $requestRepository,
        private FlashMessage $flashMessage,
        private ClientInterface $httpClient,
        Settings $settings,
        Environment $environment,
    ) {
        $this->esiBaseUrl = $settings['ESI_BASE_URL'];
        $this->killboardBaseUrl = $settings['ZKILLBOARD_BASE_URL'];

        $this->twigResponse($environment);
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
            'killboardUrl' => $this->killboardBaseUrl,
        ]);
    }

    public function submitForm(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->inputDivision = (int) $this->paramPost($request, 'division');
        $this->inputUrl = (string) $this->paramPost($request, 'url');
        $this->inputDetails = (string) $this->paramPost($request, 'details');

        if (($srpRequest = $this->createSrpRequest()) !== null) {
            return $response->withHeader('Location', "/request/{$srpRequest->getId()}");
        } else {
            return $this->showForm($request, $response);
        }
    }

    private function createSrpRequest(): ?Request
    {
        if ($this->inputDivision <= 0 || empty($this->inputUrl) || empty(trim($this->inputDetails))) {
            $this->flashMessage->addMessage('Please fill in all fields.', FlashMessage::TYPE_WARNING);
            return null;
        }

        $user = $this->userService->getAuthenticatedUser();
        if (!$user) {
            $this->flashMessage->addMessage('Logged in user not found.', FlashMessage::TYPE_WARNING);
            return null;
        }

        $division = $this->divisionRepository->find($this->inputDivision);
        if (!$this->userService->hasDivisionRole($division, Permission::SUBMIT)) {
            $this->flashMessage->addMessage('Invalid division.', FlashMessage::TYPE_WARNING);
            return null;
        }

        // Get ESI URL
        if (str_starts_with($this->inputUrl, $this->esiBaseUrl)) {
            $esiUrl = $this->inputUrl;
        } elseif (!empty($this->killboardBaseUrl) && str_starts_with($this->inputUrl, $this->killboardBaseUrl)) {
            $esiUrl = $this->apiService->getEsiUrlFromKillboard($this->inputUrl);
            if (!$esiUrl) {
                $this->flashMessage->addMessage(
                    'Could not get ESI URL from zKillboard URL.',
                    FlashMessage::TYPE_WARNING
                );
                return null;
            }
        } else {
            $this->flashMessage->addMessage('Invalid URL.', FlashMessage::TYPE_WARNING);
            return null;
        }

        // Create request
        $request = new Request();
        $request
            ->setCreated(new \DateTime())
            ->setStatus(Type::EVALUATING)
            ->setUser($user)
            ->setDivision($division)
            ->setDetails($this->inputDetails)
            ->setEsiLink($esiUrl);
        if (!$this->setDataFromEsi($request, $esiUrl)) {
            return null;
        }

        // Check if request exists already. zKill URL check is needed for migrated data where the ESI URL is missing.
        $exists1 = $this->requestRepository->findOneBy(['esiLink' => $esiUrl]);
        $exists2 = null;
        if ($request->getKillboardUrl()) {
            $exists2 = $this->requestRepository->findOneBy(['killboardUrl' => $request->getKillboardUrl()]);
        }
        if ($exists1 || $exists2) {
            $this->flashMessage->addMessage('This request already exists.', FlashMessage::TYPE_WARNING);
            return null;
        }

        // persist and return
        $this->entityManager->persist($request);
        $this->entityManager->flush();
        return $request;
    }

    private function setDataFromEsi(Request $request, string $url): bool
    {
        $killMailData = $this->apiService->getJsonData($url);
        if ($killMailData === null) {
            $this->flashMessage->addMessage("API error (ESI kill mail).", FlashMessage::TYPE_WARNING);
            return false;
        }

        try {
            $killTime = new \DateTime($killMailData->killmail_time);
        } catch (\Exception) {
            $this->flashMessage->addMessage('Could not read kill mail time.', FlashMessage::TYPE_WARNING);
            return false;
        }

        $pilot = $this->getPilot($killMailData->victim->character_id ?? 0);
        if (!$pilot) {
            $this->flashMessage->addMessage(
                'Invalid victim. You can only submit requests for your own characters.',
                FlashMessage::TYPE_WARNING
            );
            return false;
        }

        $shipData = $this->apiService->getJsonData(
            "latest/universe/types/{$killMailData->victim->ship_type_id}/?language=en-us"
        );
        if ($shipData === null) {
            $this->flashMessage->addMessage("API error (ESI ship type).", FlashMessage::TYPE_WARNING);
            return false;
        }

        $systemData = $this->apiService->getJsonData(
            "latest/universe/systems/$killMailData->solar_system_id/?language=en-us"
        );
        if ($systemData === null) {
            $this->flashMessage->addMessage("API error (ESI solar system).", FlashMessage::TYPE_WARNING);
            return false;
        }

        $corporationData = $this->apiService->getJsonData(
            "latest/corporations/{$killMailData->victim->corporation_id}/"
        );
        if ($corporationData === null) {
            $this->flashMessage->addMessage("API error (ESI corporation).", FlashMessage::TYPE_WARNING);
            return false;
        }

        $allianceData = null;
        if (isset($corporationData->alliance_id)) {
            $allianceData = $this->apiService->getJsonData("latest/alliances/$corporationData->alliance_id/");
            if ($allianceData === null) {
                $this->flashMessage->addMessage("API error (ESI alliances).", FlashMessage::TYPE_WARNING);
                return false;
            }
        }

        $request
            ->setCharacter($pilot)
            ->setShip($shipData->name)
            ->setKillTime($killTime)
            ->setSolarSystem($systemData->name)
            ->setCorporationId($killMailData->victim->corporation_id)
            ->setCorporationName($corporationData->name)
            ->setAllianceId($corporationData->alliance_id ?? null)
            ->setAllianceName($allianceData?->name);
        if ($this->killboardBaseUrl) {
            $request->setKillboardUrl("$this->killboardBaseUrl/kill/$killMailData->killmail_id/");
        }

        return true;
    }

    private function getPilot(int $pilotId): ?Character
    {
        $pilot = $this->characterRepository->find($pilotId);
        if (!$pilot) {
            return null;
        }

        foreach ($this->userService->getAuthenticatedUser()->getCharacters() as $character) {
            if ($character->getId() === $pilot->getId()) {
                return $pilot;
            }
        }

        return null;
    }
}
