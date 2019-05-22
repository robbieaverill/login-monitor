<?php

namespace SilverStripe\LoginMonitor\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\LoginMonitor\Monitor;
use SilverStripe\LoginMonitor\State\GeoResult;
use SilverStripe\Security\LoginAttempt;
use SilverStripe\Security\Member;

class MonitorTest extends SapphireTest
{
    protected static $fixture_file = 'MonitorTest.yml';

    public function testProcess()
    {
        $memberId = $this->idFromFixture(Member::class, 'jo_bloggs');

        $attempts = LoginAttempt::get();
        $monitor = new Monitor();
        $result = $monitor->process($attempts);

        $this->assertArrayHasKey($memberId, $result);
        $memberResult = $result[$memberId];

        $this->assertSame(5, $memberResult['total_success']);
        $this->assertSame(2, $memberResult['total_failure']);

        $this->assertArrayHasKey('14.1.34.123', $memberResult['ips']);
        $this->assertArrayHasKey('12.1.34.255', $memberResult['ips']);

        $this->assertSame(1, $memberResult['ips']['14.1.34.123']['success']);
        $this->assertSame(0, $memberResult['ips']['14.1.34.123']['failure']);
        $this->assertInstanceOf(GeoResult::class, $memberResult['ips']['14.1.34.123']['geo_information']);
        $this->assertSame('New Zealand', $memberResult['ips']['14.1.34.123']['geo_information']->getCountryName());

        $this->assertSame('United States', $memberResult['ips']['12.1.34.255']['geo_information']->getCountryName());

        $this->assertArrayHasKey('outliers', $memberResult);
        $this->assertArrayHasKey('12.1.34.255', $memberResult['outliers']);
        $this->assertSame('NZ', $memberResult['default_country_code']);
        $this->assertSame('US', $memberResult['outliers']['12.1.34.255']['geo_information']->getCountryCode());
    }
}
