<?php
declare(strict_types = 1);

namespace App\Command;

use Google\AdsApi\AdManager\v202008\ApiException;
use Google\AdsApi\AdManager\v202008\Company;
use Google\AdsApi\AdManager\v202008\CustomTargetingKey;
use Google\AdsApi\AdManager\v202008\CustomTargetingValue;
use Google\AdsApi\AdManager\v202008\Order;
use Google\AdsApi\AdManager\v202008\Size;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Yaml\Yaml;

use App\Service\GoogleAdManager;
use App\Service\Sape;

use Exception;

class CreateItemsCommand extends Command
{
    protected static $defaultName = 'create-items';

    /**
     * @return void
     */
    public function configure()
    {
        $this->setDescription('Create ad items in google ad manager');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int
     * @throws ApiException
     * @throws GuzzleException
     * @throws Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('');
        $output->writeln('Validate the settings and ask for confirmation from the user. Then,');
        $output->writeln('start all necessary GAM tasks.');
        $output->writeln('');

        $config = Yaml::parseFile(dirname(dirname(__DIR__)) . '/config/app.yaml');
        $gam    = new GoogleAdManager(dirname(dirname(__DIR__)), $config);

        if (empty($config['app']['order_name'])) {
            throw new Exception('Not set "app.order_name".');
        }

        if (empty($config['app']['advertiser_name'])) {
            throw new Exception('Not set "app.advertiser_name".');
        }

        if (empty($config['app']['bidder'])) {
            throw new Exception('Not set "app.bidder".');
        }

        if (empty($config['app']['sizes'])) {
            throw new Exception('Not set "app.sizes".');
        }

        $prices     = [];
        $placements = [];
        $adUnits    = [];
        $adUnitsMap = [];

        $startCpm = max(0, $config['app']['price_buckets']['min']);
        while ($startCpm <= $config['app']['price_buckets']['max']) {
            $prices[] = $startCpm;
            $startCpm = round($startCpm + $config['app']['price_buckets']['increment'], 2);
        }

        if (!empty($config['app']['targeted_placement_names'])) {
            $placements = $gam->getPlacementsService()->getPlacementsByName((array)$config['app']['targeted_placement_names']);
        }

        if (!empty($config['app']['targeted_ad_unit_names'])) {
            $adUnits = $gam->getAdUnitsService()->getAdUnitsByName((array)$config['app']['targeted_ad_unit_names']);
        }

        if ($placements) {
            $adUnitsByPlacements = [];
            foreach ($placements as $placement) {
                foreach ($placement->getTargetedAdUnitIds() as $adUnitId) {
                    if (!isset($adUnitsMap[$adUnitId])) {
                        $adUnitsMap[$adUnitId] = [];
                    }
                    $adUnitsMap[$adUnitId][] = $placement->getId();
                }
                $adUnitsByPlacements = array_unique(array_merge($placement->getTargetedAdUnitIds()));
            }
            if ($adUnits) {
                foreach (array_keys($adUnits) as $adUnitId) {
                    if (!in_array($adUnitId, $adUnitsByPlacements)) {
                        unset($adUnits[$adUnitId]);
                    }
                }
            } else {
                $adUnits = $gam->getAdUnitsService()->getAdUnitsById($adUnitsByPlacements);
            }
        }

        if (empty($adUnits)) {
            throw new Exception('AdUnits not found.');
        }

        $sizes = [];
        foreach ($config['app']['sizes'] as $size) {
            $sizes[$size['width'] . 'x' . $size['height']] = (new Size())->setWidth($size['width'])->setHeight($size['height']);
        }

        $ordersService      = $gam->getOrdersService();
        $advertisersService = $gam->getAdvertisersService();

        $order      = $ordersService->getOrderByName($config['app']['order_name']);
        $advertiser = $advertisersService->getAdvertiserByName($config['app']['advertiser_name']);

        if ($order && (empty($advertiser) || $advertiser->getId() != $order->getAdvertiserId())) {
            throw new Exception('Bad "app.advertiser_name".');
        }

        $output->writeln('');
        $output->writeln('Going to create <options=bold>' . count($prices) * count($sizes) * count($adUnits) . '</> new line items.');
        $output->writeln('  <options=bold>Order</>: <comment>' . $config['app']['order_name'] . '</comment>' . (empty($order) ? ' <fg=red>[new]</>' : ''));
        $output->writeln('  <options=bold>Advertiser</>: <comment>' . $config['app']['advertiser_name'] . '</comment>' . (empty($advertiser) ? ' <fg=red>[new]</>' : ''));
        $output->writeln('');
        $output->writeln('Line items will have targeting:');
        $output->writeln('  <options=bold>cpm</> = [' . implode(', ', $this->formatPrice($prices)) . ']');
        $output->writeln('  <options=bold>bidder</> = <comment>' . (isset($config['app']['targeting']['price']) ? $config['app']['bidder'] : '') . '</comment>');
        $output->writeln('  <options=bold>sizes</> = [' . implode(', ', $this->formatList(array_keys($sizes))) . ']');
        $output->writeln('  <options=bold>placements</> = [' . implode(', ', $this->formatObjects($placements)) . ']');
        $output->writeln('  <options=bold>ad units</> = [' . implode(', ', $this->formatObjects($adUnits)) . ']');
        $output->writeln('');
        $output->writeln('');

        if ($this->getHelper('question')->ask($input, $output, new ConfirmationQuestion('Is this correct? (y/n) ', false)) == false) {
            return Command::SUCCESS;
        }

        $output->writeln('');
        $output->writeln('Call all necessary GAM tasks for a RtbSape setup.');
        $output->writeln('');

        if (empty($advertiser)) {
            $advertiser = $gam->getAdvertisersService()->create($config['app']['advertiser_name']);
            if (empty($advertiser)) {
                throw new Exception('Can\'t create advertiser with name "' . $config['app']['advertiser_name'] . '".');
            }
            $output->writeln('Created an advertiser with name "' . $advertiser->getName() . '" and type "' . $advertiser->getType() . '".');
        }

        if ($order === null) {
            $order = $this->addOrder($gam, $config, $output, $advertiser);
        }

        $sape       = new Sape($config['sape']);
        $sapePlaces = $sape->getPlaces($prices, $adUnits, array_keys($sizes));

        $creativesService         = $gam->getCreativesService();
        $lineItemsService         = $gam->getLineItemsService();
        $lineItemCreativesService = $gam->getLineItemCreativesService();

        $targetingPriceKey    = isset($config['app']['targeting']['price']) ? $this->getTargetingKey($gam, $output, $config['app']['targeting']['price']) : null;
        $targetingBidderKey   = isset($config['app']['targeting']['bidder']) ? $this->getTargetingKey($gam, $output, $config['app']['targeting']['bidder']) : null;
        $targetingBidderValue = $targetingBidderKey ? $this->getTargetingValue($gam, $output, $targetingBidderKey, $config['app']['bidder']) : null;

        foreach ($prices as $price) {
            $targetingPriceValue = $targetingPriceKey ? $this->getTargetingValue($gam, $output, $targetingPriceKey, number_format($price, 2, '.', '')) : null;
            foreach ($adUnits as $adUnit) {
                foreach ($sizes as $size) {
                    $sizeKey = $sape->getSize($size->getWidth(), $size->getHeight());
                    if (!isset($sapePlaces[$adUnit->getId()][$sizeKey][$price])) {
                        $place = $sape->createPlace($adUnit, $size->getWidth(), $size->getHeight(), $price);
                        if (empty($place)) {
                            throw new Exception('Can\'t create new place ' . $sizeKey . ' and cpm = ' . $price . '.');
                        }

                        $output->writeln('Created an sape.place with name "' . $place['name'] . '".');
                        $sapePlaces[$adUnit->getId()][$sizeKey][$price] = $place['id'];
                    }

                    $creativeName = $config['app']['bidder'] . ': ' . $sapePlaces[$adUnit->getId()][$sizeKey][$price];
                    $creative     = $creativesService->getCreativeByName($creativeName);

                    if (empty($creative)) {
                        $creative = $creativesService->create($creativeName, $advertiser->getId(), $sape->getHtmlCode((int)$sapePlaces[$adUnit->getId()][$sizeKey][$price]), $size);
                        if (empty($order)) {
                            throw new Exception('Can\'t create creative  with name "' . $creativeName . '".');
                        }
                        $output->writeln('Created an creative with name "' . $creative->getName() . '".');
                    }

                    $lineName = $config['app']['bidder'] . ': ' . $sizeKey . ' ' . number_format($price, 2, '.', '') . ' RUB';
                    $lineItem = $lineItemsService->getLineItemByName($lineName);

                    if ($lineItem === null) {
                        $lineItem = $lineItemsService->create($lineName, $size, $adUnit->getId(), $adUnitsMap[$adUnit->getId()] ?? [], $order, $price, $targetingBidderKey, $targetingBidderValue, $targetingPriceKey, $targetingPriceValue);
                        $output->writeln('Created an lineItem with name "' . $lineItem->getName() . '".');
                    }

                    $lineItemCreative = $lineItemCreativesService->getLineItemCreativeByIds($lineItem->getId(), $creative->getId());
                    if ($lineItemCreative === null) {
                        $lineItemCreativesService->create($lineItem->getId(), $creative->getId(), $size);
                        $output->writeln('Created association for lineItem "' . $lineItem->getId() . '" and creative "' . $creative->getId() . '".');
                    }
                }
            }
        }

        $output->writeln('');
        $output->writeln('Done! Please review your order, line items, and creatives to');
        $output->writeln('make sure they are correct. Then, approve the order in GAM.');
        $output->writeln('');

        return Command::SUCCESS;
    }

    /**
     * @param GoogleAdManager $gam
     * @param array           $config
     * @param OutputInterface $output
     * @param Company         $advertiser
     *
     * @return Order
     * @throws ApiException
     * @throws Exception
     */
    protected function addOrder(GoogleAdManager $gam, array $config, OutputInterface $output, Company $advertiser): Order
    {
        $user = $gam->getUsersService()->getUserByEmail($config['app']['user_email']);
        if (empty($user)) {
            throw new Exception('User with email "' . $config['app']['user_email'] . '" not found.');
        }

        $order = $gam->getOrdersService()->create($config['app']['order_name'], $advertiser->getId(), $user->getId());
        if (empty($order)) {
            throw new Exception('Can\'t create order with name "' . $config['app']['order_name'] . '".');
        }
        $output->writeln('Created an order with name "' . $order->getName() . '".');

        return $order;
    }

    /**
     * @param GoogleAdManager $gam
     * @param OutputInterface $output
     * @param string          $name
     *
     * @return CustomTargetingKey|null
     * @throws ApiException
     */
    protected function getTargetingKey(GoogleAdManager $gam, OutputInterface $output, string $name): ?CustomTargetingKey
    {
        $targetingsService = $gam->getTargetingsService();
        $targetingKey      = $targetingsService->getKeysByName($name);

        if ($targetingKey === null) {
            $targetingKey = $targetingsService->createKey($name);
            $output->writeln('Created a custom targeting key with name "' . $name . '".');
        }

        return $targetingKey;
    }

    /**
     * @param GoogleAdManager    $gam
     * @param OutputInterface    $output
     * @param CustomTargetingKey $key
     * @param string             $value
     *
     * @return CustomTargetingValue|null
     * @throws ApiException
     */
    protected function getTargetingValue(GoogleAdManager $gam, OutputInterface $output, CustomTargetingKey $key, string $value): ?CustomTargetingValue
    {
        $targetingsService = $gam->getTargetingsService();
        $targetingValue    = $targetingsService->getValuesByName($key, $value);

        if ($targetingValue === null) {
            $targetingValue = $targetingsService->createValue($key, $value);
            $output->writeln('Created a custom targeting value with name "' . $value . '".');
        }

        return $targetingValue;
    }

    /**
     * @param array $prices
     *
     * @return array
     */
    protected function formatPrice(array $prices): array
    {
        $callback = function ($price) {
            return number_format($price, 2, '.', '');
        };

        return $this->formatList(array_map($callback, $prices));
    }

    /**
     * @param array $items
     *
     * @return array
     */
    protected function formatObjects(array $items): array
    {
        $callback = function ($item) {
            return $item->getName();
        };

        return $this->formatList(array_map($callback, $items));
    }

    /**
     * @param array $items
     *
     * @return array
     */
    protected function formatList(array $items): array
    {
        $result = [];
        foreach ($items as $item) {
            $result[] = '<comment>' . $item . '</comment>';
        }

        if (count($result) > 7) {
            return array_merge(array_slice($result, 0, 3), ['...'], array_slice($result, -3));
        }

        return $result;
    }
}