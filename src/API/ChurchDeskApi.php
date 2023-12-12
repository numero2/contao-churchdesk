<?php

/**
 * ChurchDesk Bundle for Contao Open Source CMS
 *
 * @author    Benny Born <benny.born@numero2.de>
 * @author    Michael Bösherz <michael.boesherz@numero2.de>
 * @license   Commercial
 * @copyright Copyright (c) 2023, numero2 - Agentur für digitales Marketing GbR
 */


namespace numero2\ChurchDeskBundle\API;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class ChurchDeskApi {


    /**
     * @var string
     */
    const BASE_URI = "https://api.churchdesk.com/v3.0.0";

    /**
     * @var int
     */
    const PAGE_SIZE = 100;

    /**
     * Internal value to prevent endless loops
     * @var int
     */
    const MAX_PAGES = 25;

    /**
     * @var string
     */
    const CACHE_PREFIX = 'contao.churchdesk.api.';


    /**
     * @var Symfony\Contracts\HttpClient\HttpClientInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $organizationId;

    /**
     * @var string
     */
    protected $partnerToken;

    /**
     * @var Psr\Log\LoggerInterface
     */
    protected $logger;


    public function __construct( HttpClientInterface $client, CacheItemPoolInterface $cache, string $organizationId, string $partnerToken, LoggerInterface $logger ) {

        $this->client = $client;
        $this->cache = $cache;
        $this->organizationId = $organizationId;
        $this->partnerToken = $partnerToken;
        $this->logger = $logger;
    }


    /**
     * Get blog entries based on categories
     *
     * @param array $categories
     * @param int $page
     *
     * @return array
     */
    public function getBlogs( array $categories, int $page=0 ): array {

        $params = [];

        foreach( $categories as $category ) {
            $params[] = 'cid[]='.$category;
        }
        $params[] = 'imageFormat=16-9';

        $params[] = 'limit='.self::PAGE_SIZE;
        if( $page ) {
            $params[] = 'offset='.($page*self::PAGE_SIZE);
        }

        $result = $this->send('GET', '/blogs'.'?'.implode('&', $params));

        if( $result['status'] === 200 ) {

            return $result['body'];
        }

        return [];
    }


    /**
     * Get all blog categories. Call is cached.
     *
     * @return array
     */
    public function getBlogCategories(): array {

        $cacheKey = $this->generateCacheKey('/blogs/categories', []);
        $cacheValue = $this->getCacheResult($cacheKey);

        if( $cacheValue ) {
            return $cacheValue;
        }

        $result = $this->send('GET', '/blogs/categories');

        if( $result['status'] === 200 ) {

            $this->setCacheResult($cacheKey, $result['body']);
            return $result['body'];
        }

        return [];
    }


    /**
     * Get events based on categories
     *
     * @param array $categories
     * @param int $page
     *
     * @return array
     */
    public function getEvents( array $categories, int $page=0 ): array {

        $params = [];

        if( !empty($categories) ) {
            $params[] = 'cid='.implode(',', $categories);
        }
        $params[] = 'imageFormat=16-9';

        $params[] = 'itemsNumber='.self::PAGE_SIZE;
        if( $page ) {
            $params[] = 'pageMarker='.$page;
        }

        $result = $this->send('GET', '/events'.'?'.implode('&', $params));

        if( $result['status'] === 200 ) {
            return $result['body'];
        }

        return [];
    }


    /**
     * Get all event categoires. Call is cached.
     *
     * @return array
     */
    public function getEventsCategories(): array {

        $cacheKey = $this->generateCacheKey('/events/categories', []);
        $cacheValue = $this->getCacheResult($cacheKey);

        if( $cacheValue ) {
            return $cacheValue;
        }

        $result = $this->send('GET', '/events/categories');

        if( $result['status'] === 200 ) {

            $this->setCacheResult($cacheKey, $result['body']);
            return $result['body'];
        }

        return [];
    }


    /**
     * Get all event parishes from the event endpoint. Call is cached.
     *
     * @return array
     */
    public function getEventsParishes(): array {

        $cacheKey = $this->generateCacheKey('/events/parishes', []);
        $cacheValue = $this->getCacheResult($cacheKey);

        if( $cacheValue ) {
            return $cacheValue;
        }

        $parishes = [];
        $ids = [];

        for( $i=0; $i < self::MAX_PAGES; $i+=1 ) {

            $events = $this->getEvents([], $i);

            foreach( $events as $event ) {

                if( !empty($event['parishes']) ) {
                    foreach( $event['parishes'] as $parish ) {

                        if( !in_array($parish['id'], $ids) ) {

                            $ids[] = $parish['id'];
                            $parishes[] = [
                                'id' => $parish['id'],
                                'name' => $parish['title'],
                            ];
                        }
                    }
                }
            }

            if( count($events) < self::PAGE_SIZE ) {
                break;
            }
        }

        if( $parishes ) {

            usort($parishes, function($a, $b) {
                return $a['id'] <=> $b['id'];
            });

            $this->setCacheResult($cacheKey, $parishes);
            return $parishes;
        }

        return [];
    }


    /**
     * Generate a cache Key for the given data
     *
     * @param string $path
     * @param array $data
     *
     * @return string
     */
    private function generateCacheKey( string $path, array $data ): string {

        $d = [
            'orgId' => $this->organizationId,
            'pToken' => $this->partnerToken,
            'path' => $path,
            'data' => $data,
        ];

        return self::CACHE_PREFIX.md5(serialize($d));
    }


    /**
     * Try getting a result from the cache
     *
     * @param string $key
     *
     * @return array|null
     */
    private function getCacheResult( string $key ): ?array {

        $item = $this->cache->getItem($key);

        if( $item->isHit() ) {

            return $item->get();
        }

        return null;
    }


    /**
     * Saves a result in the cache
     *
     * @param string $key
     * @param array $result
     */
    private function setCacheResult( string $key, array $result ): void {

        $item = $this->cache->getItem($key);

        $item->set($result);
        $item->expiresAfter(600);

        $this->cache->save($item);
    }


    /**
     * Send request to ChurchDesk API
     *
     * @param string $method
     * @param string $url
     * @param array $data
     *
     * @return array
     */
    private function send( string $method, string $url, ?array $data=null ): array {

        $oOptions = new HttpOptions();
        $oOptions->setHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);
        $oOptions->setQuery([
            'organizationId' => $this->organizationId,
            'partnerToken' => $this->partnerToken,
        ]);

        if( !empty($data) ) {
            $content = json_encode($data);
            $oOptions->setBody($content);
        }

        $aOptions = [];
        $aOptions = $oOptions->toArray();

        $response = null;
        $response = $this->client->request($method, self::BASE_URI.$url, $aOptions);

        $return = [
            'url' => self::BASE_URI.$url
        ,   'method' => $method
        ,   'status' => $response->getStatusCode()
        ];

        $return['body'] = json_decode($response->getContent(false), true);

        if( !in_array($return['status'], [200, 201]) ) {
            $this->logger->error('ChurchDesk API returned error: '. json_encode($return));
        }

        return $return;
    }
}
