<?php
/** @noinspection PhpUnused */

declare(strict_types=1);

namespace Brave\EveSrp\Controller;

use Brave\EveSrp\Controller\Traits\TwigResponse;
use Brave\EveSrp\Model\EsiType;
use Brave\EveSrp\Repository\EsiTypeRepository;
use Brave\EveSrp\Repository\RequestRepository;
use Brave\EveSrp\UserService;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class RequestController
{
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
     * @var RequestRepository
     */
    private $requestRepository;

    /**
     * @var EsiTypeRepository
     */
    private $esiTypeRepository;

    /**
     * @var ClientInterface
     */
    private $httpClient;

    /**
     * @var string
     */
    private $esiBaseUrl;

    /**
     * @var int[]
     */
    private $primarySlots = [
        // see invFlags.yaml from SDE https://developers.eveonline.com/resource/resources
        5 => 'Cargo',
        87 => 'Drone Bay', # DroneBay
        158 => 'Fighter Bay', # FighterBay

        27 => 'High power slot', # HiSlot0
        28 => 'High power slot', # HiSlot1
        29 => 'High power slot', # HiSlot2
        30 => 'High power slot', # HiSlot3
        31 => 'High power slot', # HiSlot4
        32 => 'High power slot', # HiSlot5
        33 => 'High power slot', # HiSlot6
        34 => 'High power slot', # HiSlot7

        19 => 'Medium power slot', # MedSlot0
        20 => 'Medium power slot', # MedSlot1
        21 => 'Medium power slot', # MedSlot2
        22 => 'Medium power slot', # MedSlot3
        23 => 'Medium power slot', # MedSlot4
        24 => 'Medium power slot', # MedSlot5
        25 => 'Medium power slot', # MedSlot6
        26 => 'Medium power slot', # MedSlot7

        11 => 'Low power slot', # LoSlot0
        12 => 'Low power slot', # LoSlot1
        13 => 'Low power slot', # LoSlot2
        14 => 'Low power slot', # LoSlot3
        15 => 'Low power slot', # LoSlot4
        16 => 'Low power slot', # LoSlot5
        17 => 'Low power slot', # LoSlot6
        18 => 'Low power slot', # LoSlot7

        92 => 'Rig power slot', # RigSlot0
        93 => 'Rig power slot', # RigSlot1
        94 => 'Rig power slot', # RigSlot2

        164 => 'Structure service slot', # StructureServiceSlot0
        165 => 'Structure service slot', # StructureServiceSlot1
        166 => 'Structure service slot', # StructureServiceSlot2
        167 => 'Structure service slot', # StructureServiceSlot3
        168 => 'Structure service slot', # StructureServiceSlot4
        169 => 'Structure service slot', # StructureServiceSlot5
        170 => 'Structure service slot', # StructureServiceSlot6
        171 => 'Structure service slot', # StructureServiceSlot7

        125 => 'Sub system slot', # SubSystem0
        126 => 'Sub system slot', # SubSystem1
        127 => 'Sub system slot', # SubSystem2
        128 => 'Sub system slot', # SubSystem3
    ];

    /**
     * @var string[][]
     */
    private $secondarySlots = [
        'High power slot' => [
            27 => 'HiSlot0',
            28 => 'HiSlot1',
            29 => 'HiSlot2',
            30 => 'HiSlot3',
            31 => 'HiSlot4',
            32 => 'HiSlot5',
            33 => 'HiSlot6',
            34 => 'HiSlot7',
        ],
        'Medium power slot' => [
            19 => 'MedSlot0',
            20 => 'MedSlot1',
            21 => 'MedSlot2',
            22 => 'MedSlot3',
            23 => 'MedSlot4',
            24 => 'MedSlot5',
            25 => 'MedSlot6',
            26 => 'MedSlot7',
        ],
        'Low power slot' => [
            11 => 'LoSlot0',
            12 => 'LoSlot1',
            13 => 'LoSlot2',
            14 => 'LoSlot3',
            15 => 'LoSlot4',
            16 => 'LoSlot5',
            17 => 'LoSlot6',
            18 => 'LoSlot7',
        ],
    ];

    public function __construct(ContainerInterface $container)
    {
        $this->userService = $container->get(UserService::class);
        $this->entityManager = $container->get(EntityManagerInterface::class);
        $this->requestRepository = $container->get(RequestRepository::class);
        $this->esiTypeRepository = $container->get(EsiTypeRepository::class);
        $this->httpClient = $container->get(ClientInterface::class);
        $this->esiBaseUrl = $container->get('settings')['ESI_BASE_URL'];

        $this->twigResponse($container->get(Environment::class));
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

    private function showPage($response, $id)
    {
        $srpRequest = $this->requestRepository->find($id);
        $error = null;

        if (! $srpRequest) {
            $error = 'Request not found.';
        } elseif (! $this->userService->maySee($srpRequest)) {
            $srpRequest = null;
            $error = 'Not authorized to view this request.';
        }

        $killMail = $this->getKillMail($srpRequest->getEsiLink());

        return $this->render($response, 'pages/request.twig', [
            'request' => $srpRequest,
            'error' => $error,
            'items' => $killMail ? $this->sortItems($killMail->victim->items) : null,
        ]);
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

    private function sortItems($items)
    {
        $result = [];
        foreach ($items as $item) {
            if (
                isset($this->primarySlots[$item->flag]) &&
                isset($this->secondarySlots[$this->primarySlots[$item->flag]][$item->flag])
            ) {
                $result
                    [$this->primarySlots[$item->flag]]
                    [$this->secondarySlots[$this->primarySlots[$item->flag]][$item->flag]]
                    [] = [
                        'item_type_id' => $item->item_type_id,
                        'item_type_name' => $this->getEsiTypeName($item->item_type_id),
                    ];
            } elseif (isset($this->primarySlots[$item->flag])) {
                $result[$this->primarySlots[$item->flag]][][] = [
                    'item_type_id' => $item->item_type_id,
                    'item_type_name' => $this->getEsiTypeName($item->item_type_id),
                ];
            } else {
                $result[$item->flag][][] = [
                    'item_type_id' => $item->item_type_id,
                    'item_type_name' => $this->getEsiTypeName($item->item_type_id),
                ];
            }
        }
        ksort($result);
        return $result;
    }

    private function getEsiTypeName(int $id): string
    {
        $type = $this->esiTypeRepository->find($id);

        if ($type === null) {
            try {
                $result = $this->httpClient->request('GET', "{$this->esiBaseUrl}latest/universe/types/{$id}");
            } catch (GuzzleException $e) {
                error_log('getEsiTypeName(): ' . $e->getMessage());
                return (string) $id;
            }
            $data = json_decode($result->getBody()->__toString());
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
