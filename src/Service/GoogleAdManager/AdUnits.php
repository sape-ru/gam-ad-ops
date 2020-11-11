<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\AdUnit;
use Google\AdsApi\AdManager\v202008\InventoryService;
use Google\AdsApi\AdManager\v202008\NumberValue;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;
use Google\AdsApi\AdManager\v202008\TextValue;

use Exception;

class AdUnits
{
    /**
     * @var InventoryService
     */
    protected $_service = null;

    /**
     * AdUnits constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createInventoryService($session);
    }

    /**
     * @param int $id
     *
     * @return AdUnit|null
     * @throws ApiException
     */
    public function getAdUnitById(int $id): ?AdUnit
    {
        $adUnits = $this->_service->getAdUnitsByStatement(new Statement('WHERE id = :id', [(new String_ValueMapEntry('id', new NumberValue($id)))]));
        foreach ($adUnits->getResults() ?: [] as $adUnit) {
            if ($adUnit->getId() == $id) {
                return $adUnit;
            }
        }

        return null;
    }

    /**
     * @param int[] $id
     *
     * @return AdUnit[]
     * @throws ApiException
     * @throws Exception
     */
    public function getAdUnitsById(array $id): array
    {
        $result = [];

        foreach ($id as $row) {
            $adUnit = $this->getAdUnitById((int)$row);
            if ($adUnit === null) {
                throw new Exception('AdUnit with id "' . $row . '" not found');
            }
            $result[$adUnit->getId()] = $adUnit;
        }

        return $result;
    }

    /**
     * @param string $name
     *
     * @return AdUnit|null
     * @throws ApiException
     */
    public function getAdUnitByName(string $name): ?AdUnit
    {
        $adUnits = $this->_service->getAdUnitsByStatement(new Statement('WHERE name = :name', [(new String_ValueMapEntry('name', new TextValue($name)))]));
        foreach ($adUnits->getResults() ?: [] as $adUnit) {
            if ($adUnit->getName() == $name) {
                return $adUnit;
            }
        }

        return null;
    }

    /**
     * @param string[] $name
     *
     * @return AdUnit[]
     * @throws ApiException
     * @throws Exception
     */
    public function getAdUnitsByName(array $name): array
    {
        $result = [];

        foreach ($name as $row) {
            $adUnit = $this->getAdUnitByName($row);
            if ($adUnit === null) {
                throw new Exception('AdUnit with name "' . $row . '" not found');
            }
            $result[$adUnit->getId()] = $adUnit;
        }

        return $result;
    }
}