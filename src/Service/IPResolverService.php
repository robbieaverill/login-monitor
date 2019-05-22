<?php

namespace SilverStripe\LoginMonitor\Service;

use GuzzleHttp\ClientInterface;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;

/**
 * Resolves a given IP address and returns geographic information about it via the geoplugin.net API
 */
class IPResolverService
{
    use Configurable;
    use Injectable;

    /**
     * The API endpoint to query, with a {ip} placeholder for the IP address
     *
     * @config
     * @var string
     */
    private static $endpoint = 'http://www.geoplugin.net/json.gp?ip={ip}';

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
        if ($client) {
            $this->setClient($client);
        }
    }

    public function resolve($ip)
    {
        $result = $this->client->request('GET', $this->getEndpoint($ip));

        return $result;
    }

    /**
     * Replaces {ip} with the provided IP in the endpoint template
     *
     * @param string $ip
     * @return string
     */
    protected function getEndpoint($ip)
    {
        $template = (string) $this->config()->get('endpoint');
        return str_replace('{ip}', (string) $ip, $template) ?: '';
    }

    /**
     * @param ClientInterface $client
     * @return $this
     */
    public function setClient(ClientInterface $client)
    {
        $this->client = $client;
        return $this;
    }
}
