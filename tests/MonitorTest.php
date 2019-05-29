<?php

namespace SilverStripe\LoginMonitor\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\LoginMonitor\Monitor;
use SilverStripe\LoginMonitor\State\MemberLoginAttemptCollection;
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

        /** @var MemberLoginAttemptCollection $attempts */
        $attempts = $memberResult['attempts'];
        $this->assertInstanceOf(MemberLoginAttemptCollection::class, $attempts);
        $this->assertSame(5, $attempts->getAttemptCollection()->getSuccessful());
        $this->assertSame(2, $attempts->getAttemptCollection()->getFailed());

        $this->assertNotEmpty($attempts->forIP('14.1.34.123'));
        $this->assertNotEmpty($attempts->forIP('12.1.34.255'));

        $firstIP = $attempts->forIP('14.1.34.123');
        $this->assertSame(5, $firstIP->getSuccessful());
        $this->assertSame(1, $firstIP->getFailed());
        $this->assertSame('New Zealand', $firstIP->getAttempts()[0]->getGeoResult()->getCountryName());

        $secondIP = $attempts->forIP('12.1.34.255');
        $this->assertSame(0, $secondIP->getSuccessful());
        $this->assertSame(1, $secondIP->getFailed());
        $this->assertSame('United States', $secondIP->getAttempts()[0]->getGeoResult()->getCountryName());

        $this->assertArrayHasKey('outliers', $memberResult);
        $this->assertArrayHasKey('12.1.34.255', $memberResult['outliers']);
        $this->assertSame('NZ', $memberResult['default_country_code']);
        $this->assertSame('US', $memberResult['outliers']['12.1.34.255']->getAttempts()[0]->getGeoResult()->getCountryCode());
    }
}
