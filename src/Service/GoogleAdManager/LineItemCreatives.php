<?php
declare(strict_types = 1);

namespace App\Service\GoogleAdManager;

use Google\AdsApi\AdManager\AdManagerSession;
use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\LineItemCreativeAssociation;
use Google\AdsApi\AdManager\v202008\NumberValue;
use Google\AdsApi\AdManager\v202008\LineItemCreativeAssociationService;
use Google\AdsApi\AdManager\v202008\ServiceFactory;
use Google\AdsApi\AdManager\v202008\Size;
use Google\AdsApi\AdManager\v202008\Statement;
use Google\AdsApi\AdManager\v202008\String_ValueMapEntry;

class LineItemCreatives
{
    /**
     * @var LineItemCreativeAssociationService
     */
    protected $_service = null;

    /**
     * LineItemCreatives constructor.
     *
     * @param AdManagerSession $session
     */
    public function __construct(AdManagerSession $session)
    {
        $this->_service = (new ServiceFactory())->createLineItemCreativeAssociationService($session);
    }

    /**
     * @param int $lineItemId
     * @param int $creativeId
     *
     * @return LineItemCreativeAssociation|null
     * @throws ApiException
     */
    public function getLineItemCreativeByIds(int $lineItemId, int $creativeId): ?LineItemCreativeAssociation
    {
        $lineItems = $this->_service->getLineItemCreativeAssociationsByStatement(new Statement('WHERE lineItemId = :lineItemId AND creativeId = :creativeId', [
            (new String_ValueMapEntry('lineItemId', new NumberValue($lineItemId))),
            (new String_ValueMapEntry('creativeId', new NumberValue($creativeId)))
        ]));

        foreach ($lineItems->getResults() ?: [] as $lineItem) {
            return $lineItem;
        }

        return null;
    }

    /**
     * @param int  $lineItemId
     * @param int  $creativeId
     * @param Size $size
     *
     * @return LineItemCreativeAssociation|null
     * @throws ApiException
     */
    public function create(int $lineItemId, int $creativeId, Size $size): ?LineItemCreativeAssociation
    {
        $lineItems = $this->_service->createLineItemCreativeAssociations([(new LineItemCreativeAssociation())
            ->setLineItemId($lineItemId)
            ->setCreativeId($creativeId)
            ->setSizes([$size])
        ]);

        foreach ($lineItems as $lineItem) {
            return $lineItem;
        }

        return null;
    }
}