<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\Creative;
use Google\AdsApi\AdManager\v202008\CreativeService;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Size;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;
use Google\AdsApi\AdManager\v202008\TextValue;
use Google\AdsApi\AdManager\v202008\ThirdPartyCreative;

class Creatives
{
    /**
     * @var CreativeService
     */
    protected $_service = null;

    /**
     * Creatives constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createCreativeService($session);
    }

    /**
     * @param string $name
     *
     * @return Creative|null
     * @throws ApiException
     */
    public function getCreativeByName(string $name): ?Creative
    {
        $creatives = $this->_service->getCreativesByStatement(new Statement('WHERE name = :name', [(new String_ValueMapEntry('name', new TextValue($name)))]));
        foreach ($creatives->getResults() ?: [] as $creative) {
            if ($creative->getName() == $name) {
                return $creative;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param int    $advertiserId
     * @param string $snippet
     * @param Size   $size
     *
     * @return Creative|null
     * @throws ApiException
     */
    public function create(string $name, int $advertiserId, string $snippet, Size $size): ?Creative
    {
        $creatives = $this->_service->createCreatives([(new ThirdPartyCreative())
            ->setName($name)
            ->setAdvertiserId($advertiserId)
            ->setSize($size)
            ->setSnippet($snippet)
            ->setIsSafeFrameCompatible(true)
        ]);

        foreach ($creatives as $creative) {
            if ($creative->getName() == $name) {
                return $creative;
            }
        }

        return null;
    }
}