<?php

namespace SilverStripe\LoginMonitor\Tests\Service;

require_once __DIR__ . '/../../src/Service/IPResolverService.php';


use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LoginMonitor\Service\IPResolverService;

class IPResolverServiceTest extends SapphireTest
{
    /**
     * @var ClientInterface
     */
    protected $client;

    protected function setUp()
    {
        parent::setUp();

        $this->client = new Client();
    }

    public function testResolve()
    {
        $service = new IPResolverService($this->client);
        $service->resolve('12.1.35.58');
    }
}
