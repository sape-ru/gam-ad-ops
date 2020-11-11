<?php
declare(strict_types = 1);

namespace App\Service;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\AdManagerSessionBuilder;
use Google\AdsApi\Common\ConfigurationLoader;
use Google\AdsApi\Common\OAuth2TokenBuilder;

use App\Service\GoogleAdManager\Users;
use App\Service\GoogleAdManager\Orders;
use App\Service\GoogleAdManager\Placements;
use App\Service\GoogleAdManager\AdUnits;
use App\Service\GoogleAdManager\Advertisers;
use App\Service\GoogleAdManager\Creatives;
use App\Service\GoogleAdManager\Targetings;
use App\Service\GoogleAdManager\LineItems;
use App\Service\GoogleAdManager\LineItemCreatives;

class GoogleAdManager
{
    /**
     * @var AdManagerSession
     */
    protected $_session = null;

    protected $_config = [];

    /**
     * Bridge constructor.
     *
     * @param string $path
     * @param array  $config
     */
    public function __construct(string $path, array $config)
    {
        $this->_config  = $config['app'] ?? [];
        $this->_session = (new AdManagerSessionBuilder())
            ->from((new ConfigurationLoader())->fromString('[AD_MANAGER]' . PHP_EOL . 'networkCode = "' . $config['ad_manager']['network_code'] . '"' . PHP_EOL . 'applicationName = "' . $config['ad_manager']['application_name'] . '"' . PHP_EOL . '[LOGGING]' . PHP_EOL . 'soapLogLevel = "NOTICE"'))
            ->withOAuth2Credential((new OAuth2TokenBuilder())
                ->from((new ConfigurationLoader())->fromString('[OAUTH2]' . PHP_EOL . 'jsonKeyFilePath = "' . $path . '/config/key.json"' . PHP_EOL . 'scopes = "https://www.googleapis.com/auth/dfp"'))
                ->build()
            )
            ->build();
    }

    /**
     * @return Users
     */
    public function getUsersService(): Users
    {
        return new Users($this->_session);
    }

    /**
     * @return Orders
     */
    public function getOrdersService(): Orders
    {
        return new Orders($this->_session);
    }

    /**
     * @return Placements
     */
    public function getPlacementsService(): Placements
    {
        return new Placements($this->_session);
    }

    /**
     * @return AdUnits
     */
    public function getAdUnitsService(): AdUnits
    {
        return new AdUnits($this->_session);
    }

    /**
     * @return Advertisers
     */
    public function getAdvertisersService(): Advertisers
    {
        return new Advertisers($this->_session);
    }

    /**
     * @return Creatives
     */
    public function getCreativesService(): Creatives
    {
        return new Creatives($this->_session);
    }

    /**
     * @return Targetings
     */
    public function getTargetingsService(): Targetings
    {
        return new Targetings($this->_session);
    }

    /**
     * @return LineItems
     */
    public function getLineItemsService(): LineItems
    {
        return new LineItems($this->_session);
    }

    /**
     * @return LineItemCreatives
     */
    public function getLineItemCreativesService(): LineItemCreatives
    {
        return new LineItemCreatives($this->_session);
    }
}