<?php

namespace SilverStripe\LoginMonitor\Tests\State;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\LoginMonitor\State\GeoResult;

/**
 * Note that IPResolvedServiceTest already tests most of the logic in this instance
 */
class GeoResultTest extends SapphireTest
{
    public function testMissingDataReturnsNull()
    {
        $result = new GeoResult([]);
        $this->assertEmpty($result->getCountryCode());
    }
}
