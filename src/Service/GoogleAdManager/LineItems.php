<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\AdUnitTargeting;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\CreativePlaceholder;
use Google\AdsApi\AdManager\v202008\CustomCriteria;
use Google\AdsApi\AdManager\v202008\CustomCriteriaSet;
use Google\AdsApi\AdManager\v202008\CustomTargetingKey;
use Google\AdsApi\AdManager\v202008\CustomTargetingValue;
use Google\AdsApi\AdManager\v202008\Goal;
use Google\AdsApi\AdManager\v202008\InventoryTargeting;
use Google\AdsApi\AdManager\v202008\LineItem;
use Google\AdsApi\AdManager\v202008\Money;
use Google\AdsApi\AdManager\v202008\Order;
use Google\AdsApi\AdManager\v202008\Size;
use Google\AdsApi\AdManager\v202008\Targeting;
use Google\AdsApi\AdManager\v202008\LineItemService;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;
use Google\AdsApi\AdManager\v202008\TextValue;

class LineItems
{
    /**
     * @var LineItemService
     */
    protected $_service = null;

    /**
     * LineItems constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createLineItemService($session);
    }

    /**
     * @param string $name
     *
     * @return LineItem|null
     * @throws ApiException
     */
    public function getLineItemByName(string $name): ?LineItem
    {
        $lineItems = $this->_service->getLineItemsByStatement(new Statement('WHERE name = :name', [(new String_ValueMapEntry('name', new TextValue($name)))]));
        foreach ($lineItems->getResults() ?: [] as $lineItem) {
            if ($lineItem->getName() == $name) {
                return $lineItem;
            }
        }

        return null;
    }

    /**
     * @param string                    $name
     * @param Size                      $size
     * @param string                    $adUnitId
     * @param array                     $placementIds
     * @param Order                     $order
     * @param float                     $price
     * @param CustomTargetingKey|null   $bidderKey
     * @param CustomTargetingValue|null $bidderValue
     * @param CustomTargetingKey|null   $priceKey
     * @param CustomTargetingValue|null $priceValue
     *
     * @return LineItem|null
     * @throws ApiException
     */
    public function create(string $name, Size $size, string $adUnitId, array $placementIds, Order $order, float $price, CustomTargetingKey $bidderKey = null, CustomTargetingValue $bidderValue = null, CustomTargetingKey $priceKey = null, CustomTargetingValue $priceValue = null): ?LineItem
    {
        $customTargetings = [];
        if ($bidderKey && $bidderValue) {
            $customTargetings[] = (new CustomCriteria())->setKeyId($bidderKey->getId())->setOperator('IS')->setValueIds([$bidderValue->getId()]);
        }

        if ($priceKey && $priceValue) {
            $customTargetings[] = (new CustomCriteria())->setKeyId($priceKey->getId())->setOperator('IS')->setValueIds([$priceValue->getId()]);
        }

        $lineItems = $this->_service->createLineItems([(new LineItem())
            ->setName($name)
            ->setOrderId($order->getId())
            ->setLineItemType('PRICE_PRIORITY')
            ->setTargeting((new Targeting())
                ->setInventoryTargeting((new InventoryTargeting())->setTargetedAdUnits([(new AdUnitTargeting())->setAdUnitId($adUnitId)])->setTargetedPlacementIds($placementIds))
                ->setCustomTargeting(count($customTargetings) > 0 ? (new CustomCriteriaSet())->setLogicalOperator('AND')->setChildren($customTargetings) : null)
            )
            ->setStartDateTimeType('IMMEDIATELY')
            ->setUnlimitedEndDateTime(true)
            ->setCostType('CPM')
            ->setCostPerUnit((new Money())->setCurrencyCode('RUB')->setMicroAmount($price * 1000000))
            ->setPrimaryGoal((new Goal())->setGoalType('NONE'))
            ->setCreativeRotationType('EVEN')
            ->setCreativePlaceholders([(new CreativePlaceholder())->setSize($size)])
        ]);

        foreach ($lineItems as $lineItem) {
            if ($lineItem->getName() == $name) {
                return $lineItem;
            }
        }

        return null;
    }
}