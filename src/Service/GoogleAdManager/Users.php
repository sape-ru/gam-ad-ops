<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\User;
use Google\AdsApi\AdManager\v202008\UserService;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;
use Google\AdsApi\AdManager\v202008\TextValue;

class Users
{
    /**
     * @var UserService
     */
    protected $_service = null;

    /**
     * Users constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createUserService($session);
    }

    /**
     * @param string $email
     *
     * @return User|null
     * @throws ApiException
     */
    public function getUserByEmail(string $email): ?User
    {
        $users = $this->_service->getUsersByStatement(new Statement('WHERE email = :email', [(new String_ValueMapEntry('email', new TextValue($email)))]));
        foreach ($users->getResults() ?: [] as $user) {
            if ($user->getEmail() == $email) {
                return $user;
            }
        }

        return null;
    }
}