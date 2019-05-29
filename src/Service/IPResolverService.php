<?php declare(strict_types=1);

namespace SilverStripe\LoginMonitor\Service;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\LoginMonitor\State\GeoResult;

/**
 * Resolves a given IP address and returns geographic information about it via the geoplugin.net API
 */
class IPResolverService implements IPResolverInterface
{
    use Injectable;

    /**
     * The API endpoint to query, with a {ip} placeholder for the IP address
     *
     * @var string
     */
    const ENDPOINT = 'http://www.geoplugin.net/json.gp?ip={ip}';

    /**
     * @var string
     */
    protected $ip;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @param ClientInterface|null $client
     */
    public function __construct(ClientInterface $client = null)
    {
        if (!$client) {
            $client = new Client();
        }
        $this->setClient($client);
    }

    /**
     * @param string $ip
     * @return GeoResult
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function resolve(string $ip): GeoResult
    {
        $result = $this->client->request('GET', $this->getEndpoint($ip));
        $data = json_decode((string) $result->getBody(), true);
        return GeoResult::create($data);
    }

    /**
     * Replaces {ip} with the provided IP in the endpoint template
     *
     * @param string $ip
     * @return string
     */
    protected function getEndpoint(string $ip): string
    {
        return str_replace('{ip}', (string) $ip, self::ENDPOINT) ?: '';
    }

    /**
     * @param ClientInterface $client
     * @return $this
     */
    public function setClient(ClientInterface $client): IPResolverInterface
    {
        $this->client = $client;
        return $this;
    }
}
