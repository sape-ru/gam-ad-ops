<?php
declare(strict_types = 1);

namespace App\Service;

use Google\AdsApi\AdManager\v202008\AdUnit;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

use Exception;

class Sape
{
    protected const API_URL  = 'https://api.sape.ru/xmlrpc/';
    public const    ADAPTIVE = 'ADAPTIVE';

    /**
     * @var Client
     */
    protected $apiClient = null;
    protected $apiLogin  = null;
    protected $apiToken  = null;

    protected $siteId = null;
    protected $sizeId = [
        '240x400'  => 1,
        '728x90'   => 2,
        '300x250'  => 3,
        '468x60'   => 4,
        '160x600'  => 5,
        '120x600'  => 6,
        '300x600'  => 7,
        '970x90'   => 8,
        '600x340'  => 9,
        '240x120'  => 10,
        '640x480'  => 11,
        '192x160'  => 12,
        '320x50'   => 13,
        '320x100'  => 16,
        '970x250'  => 17,
        'ADAPTIVE' => 99,
    ];

    /**
     * Sape constructor.
     *
     * @param array $config
     *
     * @throws Exception|GuzzleException
     */
    public function __construct(array $config)
    {
        $this->apiClient = new Client(['cookies' => true]);

        $this->apiLogin = $config['login'] ?? '';
        $this->apiToken = $config['token'] ?? '';
        $this->siteId   = $config['site_id'] ?? 0;

        $this->login();
    }

    /**
     * @param array    $prices
     * @param AdUnit[] $adUnits
     * @param array   $sizes
     *
     * @return array
     * @throws GuzzleException
     */
    public function getPlaces(array $prices, array $adUnits, array $sizes): array
    {
        $result = [];

        $places = $this->call('rtb.get_places', [$this->siteId]);
        foreach ($places as $place) {
            if (mb_strpos($place['name'], 'GAM ') === 0) {
                foreach ($sizes as $size) {
                    if (mb_strpos($place['name'], ' ' . $size) !== false) {
                        foreach ($prices as $price) {
                            if (mb_strpos($place['name'], ' ' . number_format($price, 2, '.', '')) !== false) {
                                foreach ($adUnits as $adUnit) {
                                    if (mb_strpos($place['name'], ' ' . $adUnit->getId() . ' ') === 3) {
                                        $result[$adUnit->getId()][$size][$price] = $place['id'];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param int $width
     * @param int $height
     *
     * @return string
     * @throws Exception
     */
    public function getSize(int $width, int $height)
    {
        $size = $width ? ($width . 'x' . $height) : self::ADAPTIVE;
        if (!isset($this->sizeId[$size])) {
            throw new Exception('Not found place size "' . $size . '".');
        }

        return $size;
    }

    /**
     * @param int $placeId
     *
     * @return string
     */
    public function getHtmlCode(int $placeId): string
    {
        return '<script async="async" src="//cdn-rtb.sape.rurtb-b/js/' . sprintf('%03d', $this->siteId % 1000) . '/2/' . $this->siteId . '.js" type="text/javascript"></script>' . PHP_EOL . '<div id="SRTB_' . $placeId . '"></div>';
    }

    /**
     * @param AdUnit $adUnit
     * @param int    $width
     * @param int    $height
     * @param float  $price
     *
     * @return mixed
     * @throws GuzzleException
     * @throws Exception
     */
    public function createPlace(AdUnit $adUnit, int $width, int $height, float $price)
    {
        $size = $this->getSize($width, $height);
        return $this->call('rtb.banner_place_add', [$this->siteId, 'GAM ' . $adUnit->getId() . ' ' . $this->getSize($width, $height) . ' ' . number_format($price, 2, '.', ''), 0, $this->sizeId[$size], $price]);
    }

    /**
     * @return int
     * @throws Exception|GuzzleException
     */
    protected function login(): int
    {
        return (int)$this->call('sape.login', [$this->apiLogin, $this->apiToken], true);
    }

    /**
     * @param string $method
     * @param array  $params
     * @param bool   $force
     *
     * @return mixed
     * @throws Exception
     * @throws GuzzleException
     */
    protected function call(string $method, $params = [], $force = false)
    {
        $url = self::API_URL;
        if (mb_strpos($method, 'rtb.') === 0) {
            $url .= '?rtb=1';
        }

        $response = $this->apiClient->request('POST', $url, [
            'body'    => xmlrpc_encode_request($method, $params),
            'headers' => [
                'User-Agent'   => 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 7.1; Trident/5.0)',
                'Content-Type' => 'text/xml'
            ]
        ]);

        $response = xmlrpc_decode_request($response->getBody()->getContents(), $method);
        if ($response['faultCode'] ?? false) {
            if ($response['faultCode'] == 667 && $force == false) {
                if ($this->login()) {
                    return $this->call($method, $params, true);
                }
            }

            throw new Exception($response['faultString'] ?? '', $response['faultCode']);
        }

        return $response;
    }
}