<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\Company;
use Google\AdsApi\AdManager\v202008\CompanyService;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;
use Google\AdsApi\AdManager\v202008\TextValue;

class Advertisers
{
    /**
     * @var CompanyService
     */
    protected $_service = null;

    /**
     * Advertisers constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createCompanyService($session);
    }

    /**
     * @param string $name
     *
     * @return Company|null
     * @throws ApiException
     */
    public function getAdvertiserByName(string $name): ?Company
    {
        $companies = $this->_service->getCompaniesByStatement(new Statement('WHERE name = :name', [(new String_ValueMapEntry('name', new TextValue($name)))]));
        foreach ($companies->getResults() ?: [] as $companie) {
            if ($companie->getName() == $name) {
                return $companie;
            }
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return Company|null
     * @throws ApiException
     */
    public function create(string $name): ?Company
    {
        $companies = $this->_service->createCompanies([(new Company())->setName($name)->setType('AD_NETWORK')]);
        foreach ($companies as $companie) {
            if ($companie->getName() == $name) {
                return $companie;
            }
        }

        return null;
    }
}