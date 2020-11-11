<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\Order;
use Google\AdsApi\AdManager\v202008\OrderService;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;
use Google\AdsApi\AdManager\v202008\TextValue;

class Orders
{
    /**
     * @var OrderService
     */
    protected $_service = null;

    /**
     * Orders constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createOrderService($session);
    }

    /**
     * @param string $name
     *
     * @return Order|null
     * @throws ApiException
     */
    public function getOrderByName(string $name): ?Order
    {
        $orders = $this->_service->getOrdersByStatement(new Statement('WHERE name = :name', [(new String_ValueMapEntry('name', new TextValue($name)))]));
        foreach ($orders->getResults() ?: [] as $order) {
            if ($order->getName() == $name) {
                return $order;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @param int    $advertiserId
     * @param int    $userId
     *
     * @return Order|null
     * @throws ApiException
     */
    public function create(string $name, int $advertiserId, int $userId): ?Order
    {
        $orders = $this->_service->createOrders([(new Order())->setName($name)->setAdvertiserId($advertiserId)->setTraffickerId($userId)]);
        foreach ($orders as $order) {
            if ($order->getName() == $name) {
                return $order;
            }
        }

        return null;
    }
}