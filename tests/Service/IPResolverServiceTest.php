<?php

namespace SilverStripe\LoginMonitor\Tests\Service;

use GuzzleHttp\ClientInterface;
use PHPUnit_Framework_MockObject_MockObject;
use Psr\Http\Message\ResponseInterface;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\LoginMonitor\Service\IPResolverService;

class IPResolverServiceTest extends SapphireTest
{
    /**
     * @var ClientInterface|PHPUnit_Framework_MockObject_MockObject
     */
    protected $client;

    /**
     * A mock API response from geoplugin.net
     *
     * @var array
     */
    protected $mockData = [
        'geoplugin_request' => '12.1.35.58',
        'geoplugin_status' => 200,
        'geoplugin_city' => 'Fort Lauderdale',
        'geoplugin_region' => 'Florida',
        'geoplugin_regionCode' => 'FL',
        'geoplugin_countryCode' => 'US',
        'geoplugin_countryName' => 'United States',
    ];

    protected function setUp()
    {
        parent::setUp();

        $this->client = $this->createMock(ClientInterface::class);
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getBody')->willReturn(json_encode($this->mockData));
        $this->client->method('request')->willReturn($mockResponse);
    }

    public function testResolve()
    {
        $this->client->expects($this->once())->method('request');
        $service = new IPResolverService($this->client);

        $result = $service->resolve('12.1.35.58');
        $this->assertSame('12.1.35.58', $result->getIp());
        $this->assertSame(200, $result->getStatus());
        $this->assertSame('Fort Lauderdale', $result->getCity());
        $this->assertSame('Florida', $result->getRegion());
        $this->assertSame('FL', $result->getRegionCode());
        $this->assertSame('US', $result->getCountryCode());
        $this->assertSame('United States', $result->getCountryName());
    }
}
