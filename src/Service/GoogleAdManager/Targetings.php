<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\NumberValue;
use Google\AdsApi\AdManager\v202008\CustomTargetingService;
use Google\AdsApi\AdManager\v202008\CustomTargetingKey;
use Google\AdsApi\AdManager\v202008\CustomTargetingValue;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;
use Google\AdsApi\AdManager\v202008\TextValue;

class Targetings
{
    /**
     * @var CustomTargetingService
     */
    protected $_service = null;

    /**
     * TargetingKeys constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createCustomTargetingService($session);
    }

    /**
     * @param string $name
     *
     * @return CustomTargetingKey|null
     * @throws ApiException
     */
    public function getKeysByName(string $name): ?CustomTargetingKey
    {
        $keys = $this->_service->getCustomTargetingKeysByStatement(new Statement('WHERE name = :name', [(new String_ValueMapEntry('name', new TextValue($name)))]));
        foreach ($keys->getResults() ?: [] as $key) {
            if ($key->getName() == $name) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param CustomTargetingKey $key
     * @param string             $name
     *
     * @return CustomTargetingValue|null
     * @throws ApiException
     */
    public function getValuesByName(CustomTargetingKey $key, string $name): ?CustomTargetingValue
    {
        $values = $this->_service->getCustomTargetingValuesByStatement(new Statement('WHERE customTargetingKeyId = :key AND name = :name', [
            (new String_ValueMapEntry('key', new NumberValue($key->getId()))),
            (new String_ValueMapEntry('name', new TextValue($name)))
        ]));
        foreach ($values->getResults() ?: [] as $value) {
            if ($value->getName() == $name) {
                return $value;
            }
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return CustomTargetingKey|null
     * @throws ApiException
     */
    public function createKey(string $name): ?CustomTargetingKey
    {
        $keys = $this->_service->createCustomTargetingKeys([(new CustomTargetingKey())->setName($name)->setType('FREEFORM')]);
        foreach ($keys as $key) {
            if ($key->getName() == $name) {
                return $key;
            }
        }

        return null;
    }

    /**
     * @param CustomTargetingKey $key
     * @param string             $name
     *
     * @return CustomTargetingValue|null
     * @throws ApiException
     */
    public function createValue(CustomTargetingKey $key, string $name): ?CustomTargetingValue
    {
        $values = $this->_service->createCustomTargetingValues([(new CustomTargetingValue())->setName($name)->setDisplayName($name)->setCustomTargetingKeyId($key->getId())]);
        foreach ($values as $value) {
            if ($value->getName() == $name) {
                return $value;
            }
        }

        return null;
    }
}