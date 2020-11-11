<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\Placement;
use Google\AdsApi\AdManager\v202008\PlacementService;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;
use Google\AdsApi\AdManager\v202008\TextValue;

use Exception;

class Placements
{
    /**
     * @var PlacementService
     */
    protected $_service = null;

    /**
     * Placements constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createPlacementService($session);
    }

    /**
     * @param string $name
     *
     * @return Placement|null
     * @throws ApiException
     */
    public function getPlacementByName(string $name): ?Placement
    {
        $placements = $this->_service->getPlacementsByStatement(new Statement('WHERE name = :name', [(new String_ValueMapEntry('name', new TextValue($name)))]));
        foreach ($placements->getResults() ?: [] as $placement) {
            if ($placement->getName() == $name) {
                return $placement;
            }
        }

        return null;
    }

    /**
     * @param string[] $name
     *
     * @return Placement[]
     * @throws ApiException
     * @throws Exception
     */
    public function getPlacementsByName(array $name): array
    {
        $result = [];

        foreach ($name as $row) {
            $placement = $this->getPlacementByName($row);
            if ($placement === null) {
                throw new Exception('Placement with name "' . $row. '" not found');
            }
            $result[$placement->getId()] = $placement;
        }

        return $result;
    }
}