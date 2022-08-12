<?php

declare(strict_types=1);

namespace EveSrp\Controller;

use EveSrp\Controller\Traits\TwigResponse;
use EveSrp\Model\EsiType;
use EveSrp\Model\Request;
use EveSrp\Repository\EsiTypeRepository;
use EveSrp\Repository\RequestRepository;
use EveSrp\Service\ApiService;
use EveSrp\Service\UserService;
use Doctrine\ORM\EntityManagerInterface;
use EveSrp\Settings;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class RequestController
{
    use TwigResponse;

    private const CARGO = 'Cargo';
    private const DRONE_BAY = 'Drone Bay';
    private const FIGHTER_BAY = 'Fighter Bay';
    private const HIGH_POWER_SLOT = 'High power slot';
    private const MEDIUM_POWER_SLOT = 'Medium power slot';
    private const LOW_POWER_SLOT = 'Low power slot';
    private const RIG_POWER_SLOT = 'Rig power slot';
    private const STRUCTURE_SERVICE_SLOT = 'Structure service slot';
    private const SUB_SYSTEM_SLOT = 'Sub system slot';
    private const IMPLANT = 'Implant';
    private const SHIP_HANGAR = 'Ship Hangar';
    private const FLEET_HANGAR = 'Fleet Hangar';
    private const SPECIALIZED_FUEL_BAY = 'Specialized Fuel Bay';
    private const SPECIALIZED_ORE_HOLD = 'Specialized Ore Hold';
    private const SPECIALIZED_MINERAL_HOLD = 'Specialized Mineral Hold';
    private const SPECIALIZED_AMMO_HOLD = 'Specialized Ammo Hold';
    private const SPECIALIZED_PLANETARY_COMMODITIES_HOLD = 'Specialized Planetary Commodities Hold';

    private string $esiBaseUrl;

    private string $killboardBaseUrl;

    private array $slotGroups = [
        // see invFlags.yaml from SDE https://developers.eveonline.com/resource/resources
        5 => self::CARGO, # Cargo
        87 => self::DRONE_BAY, # DroneBay
        158 => self::FIGHTER_BAY, # FighterBay
        89 => self::IMPLANT, # Implant
        90 => self::SHIP_HANGAR, # ShipHangar
        155 => self::FLEET_HANGAR, # FleetHangar

        27 => self::HIGH_POWER_SLOT, # HiSlot0
        28 => self::HIGH_POWER_SLOT, # HiSlot1
        29 => self::HIGH_POWER_SLOT, # HiSlot2
        30 => self::HIGH_POWER_SLOT, # HiSlot3
        31 => self::HIGH_POWER_SLOT, # HiSlot4
        32 => self::HIGH_POWER_SLOT, # HiSlot5
        33 => self::HIGH_POWER_SLOT, # HiSlot6
        34 => self::HIGH_POWER_SLOT, # HiSlot7

        19 => self::MEDIUM_POWER_SLOT, # MedSlot0
        20 => self::MEDIUM_POWER_SLOT, # MedSlot1
        21 => self::MEDIUM_POWER_SLOT, # MedSlot2
        22 => self::MEDIUM_POWER_SLOT, # MedSlot3
        23 => self::MEDIUM_POWER_SLOT, # MedSlot4
        24 => self::MEDIUM_POWER_SLOT, # MedSlot5
        25 => self::MEDIUM_POWER_SLOT, # MedSlot6
        26 => self::MEDIUM_POWER_SLOT, # MedSlot7

        11 => self::LOW_POWER_SLOT, # LoSlot0
        12 => self::LOW_POWER_SLOT, # LoSlot1
        13 => self::LOW_POWER_SLOT, # LoSlot2
        14 => self::LOW_POWER_SLOT, # LoSlot3
        15 => self::LOW_POWER_SLOT, # LoSlot4
        16 => self::LOW_POWER_SLOT, # LoSlot5
        17 => self::LOW_POWER_SLOT, # LoSlot6
        18 => self::LOW_POWER_SLOT, # LoSlot7

        92 => self::RIG_POWER_SLOT, # RigSlot0
        93 => self::RIG_POWER_SLOT, # RigSlot1
        94 => self::RIG_POWER_SLOT, # RigSlot2

        164 => self::STRUCTURE_SERVICE_SLOT, # StructureServiceSlot0
        165 => self::STRUCTURE_SERVICE_SLOT, # StructureServiceSlot1
        166 => self::STRUCTURE_SERVICE_SLOT, # StructureServiceSlot2
        167 => self::STRUCTURE_SERVICE_SLOT, # StructureServiceSlot3
        168 => self::STRUCTURE_SERVICE_SLOT, # StructureServiceSlot4
        169 => self::STRUCTURE_SERVICE_SLOT, # StructureServiceSlot5
        170 => self::STRUCTURE_SERVICE_SLOT, # StructureServiceSlot6
        171 => self::STRUCTURE_SERVICE_SLOT, # StructureServiceSlot7

        125 => self::SUB_SYSTEM_SLOT, # SubSystem0
        126 => self::SUB_SYSTEM_SLOT, # SubSystem1
        127 => self::SUB_SYSTEM_SLOT, # SubSystem2
        128 => self::SUB_SYSTEM_SLOT, # SubSystem3

        133 => self::SPECIALIZED_FUEL_BAY, # SpecializedFuelBay
        134 => self::SPECIALIZED_ORE_HOLD, # SpecializedOreHold
        136 => self::SPECIALIZED_MINERAL_HOLD, # SpecializedMineralHold
        #138 => self::SPECIALIZED_SHIP_HOLD, # SpecializedShipHold
        #142 => self::SPECIALIZED_INDUSTRIAL_SHIP_HOLD, # SpecializedIndustrialShipHold
        143 => self::SPECIALIZED_AMMO_HOLD, # SpecializedAmmoHold
        149 => self::SPECIALIZED_PLANETARY_COMMODITIES_HOLD, # SpecializedPlanetaryCommoditiesHold
    ];

    private array $slotSort = [
        self::HIGH_POWER_SLOT,
        self::MEDIUM_POWER_SLOT,
        self::LOW_POWER_SLOT,
        self::RIG_POWER_SLOT,
        self::SUB_SYSTEM_SLOT,
        self::CARGO,
        self::DRONE_BAY,
        self::FIGHTER_BAY,
        self::SHIP_HANGAR,
        self::FLEET_HANGAR,
        self::SPECIALIZED_FUEL_BAY,
        self::SPECIALIZED_ORE_HOLD,
        self::SPECIALIZED_MINERAL_HOLD,
        self::SPECIALIZED_AMMO_HOLD,
        self::SPECIALIZED_PLANETARY_COMMODITIES_HOLD,
        self::STRUCTURE_SERVICE_SLOT,
        self::IMPLANT,
    ];

    private array $multiSlots = [
        27 => 'HiSlot0',
        28 => 'HiSlot1',
        29 => 'HiSlot2',
        30 => 'HiSlot3',
        31 => 'HiSlot4',
        32 => 'HiSlot5',
        33 => 'HiSlot6',
        34 => 'HiSlot7',

        19 => 'MedSlot0',
        20 => 'MedSlot1',
        21 => 'MedSlot2',
        22 => 'MedSlot3',
        23 => 'MedSlot4',
        24 => 'MedSlot5',
        25 => 'MedSlot6',
        26 => 'MedSlot7',

        11 => 'LoSlot0',
        12 => 'LoSlot1',
        13 => 'LoSlot2',
        14 => 'LoSlot3',
        15 => 'LoSlot4',
        16 => 'LoSlot5',
        17 => 'LoSlot6',
        18 => 'LoSlot7',
    ];

    public function __construct(
        private UserService $userService,
        private ApiService $apiService,
        private EntityManagerInterface $entityManager,
        private RequestRepository $requestRepository,
        private EsiTypeRepository $esiTypeRepository,
        private ClientInterface $httpClient,
        Settings $settings,
        Environment $environment
    ) {
        $this->esiBaseUrl = $settings['ESI_BASE_URL'];
        $this->killboardBaseUrl = $settings['ZKILLBOARD_BASE_URL'];

        $this->twigResponse($environment);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # page for submitter
        return $this->showPage($response, $args['id']);
    }

    /**
     * @noinspection PhpUnusedParameterInspection
     */
    public function process(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        # page review or pay
        return $this->showPage($response, $args['id']);
    }

    private function showPage($response, $id): ResponseInterface
    {
        $srpRequest = $this->requestRepository->find($id);
        $error = null;

        if (! $srpRequest) {
            $error = 'Request not found.';
        } elseif (! $this->userService->maySee($srpRequest)) {
            $srpRequest = null;
            $error = 'Not authorized to view this request.';
        }

        $this->addMissingURLs($srpRequest);
        $killMail = $this->getKillMail($srpRequest->getEsiLink());

        return $this->render($response, 'pages/request.twig', [
            'request' => $srpRequest,
            'error' => $error,
            'items' => $killMail ? $this->sortItems($killMail->victim->items) : null,
        ]);
    }

    /**
     * Add missing URLs to zKillboard or ESI
     */
    private function addMissingURLs(Request $srpRequest): void
    {
        if (! $srpRequest->getEsiLink() && $srpRequest->getKillboardUrl()) {
            $esiLink = $this->apiService->getEsiUrlFromKillboard($srpRequest->getKillboardUrl());
            if ($esiLink) {
                $srpRequest->setEsiLink($esiLink);
                $this->entityManager->flush();
            }
        } elseif (! $srpRequest->getKillboardUrl() && $srpRequest->getEsiLink()) {
            $urlParts = explode('/', rtrim($srpRequest->getEsiLink(), '/'));
            array_pop($urlParts);
            $killId = end($urlParts);
            if (is_numeric($killId)) {
                $srpRequest->setKillboardUrl("{$this->killboardBaseUrl}kill/$killId/");
                $this->entityManager->flush();
            }
        }
    }

    private function getKillMail(?string $esiLink)
    {
        if (! $esiLink) {
            return null;
        }

        try {
            $result = $this->httpClient->request('GET', $esiLink);
        } catch (GuzzleException $e) {
            error_log('getKillMail(): ' . $e->getMessage());
            return null;
        }

        return json_decode($result->getBody()->__toString());
    }

    private function sortItems($items): array
    {
        $itemGroups = [];
        $unknown = [];
        foreach ($items as $item) {
            $groupName = $this->slotGroups[$item->flag] ?? null;
            $multiSlotName = $this->multiSlots[$item->flag] ?? null;
            if ($groupName && $multiSlotName) {
                // several items in the same slot, e.g. turret with ammo
                $itemGroups[$groupName][$multiSlotName][] = [
                    'item_type_id' => $item->item_type_id,
                    'item_type_name' => $this->getEsiTypeName($item->item_type_id),
                ];
            } elseif ($groupName) {
                // only one item per slot or hangars etc.
                $itemGroups[$groupName][][0] = [
                    'item_type_id' => $item->item_type_id,
                    'item_type_name' => $this->getEsiTypeName($item->item_type_id),
                ];
            } else {
                error_log('sortItems: Unknown flag ' . $item->flag);
                $unknown[$item->flag][][0] = [
                    'item_type_id' => $item->item_type_id,
                    'item_type_name' => $this->getEsiTypeName($item->item_type_id),
                ];
            }
        }

        $result = [];
        foreach ($this->slotSort as $sortValue) {
            foreach ($itemGroups as $groupName => $groupContent) {
                if ($sortValue === $groupName) {
                    $result[$groupName] = $groupContent;
                }
            }
        }
        if (count($result) !== count($itemGroups)) {
            error_log('sortItems: Missing an item group.');
        }

        return $result + $unknown;
    }

    private function getEsiTypeName(int $id): string
    {
        $type = $this->esiTypeRepository->find($id);

        if ($type === null) {
            $data = $this->apiService->getJsonData("{$this->esiBaseUrl}latest/universe/types/$id");
            if ($data) {
                $type = new EsiType();
                $type->setId($id)->setName($data->name);
                $this->entityManager->persist($type);
                $this->entityManager->flush();
            } else {
                return (string) $id;
            }
        }

        return $type->getName();
    }
}
