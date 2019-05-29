<?php declare(strict_types=1);

namespace SilverStripe\LoginMonitor\Service;

use SilverStripe\LoginMonitor\State\GeoResult;

/**
 * Resolves a given IP address and returns geographic information about it
 */
interface IPResolverInterface
{
    /**
     * @param string $ip
     * @return GeoResult
     */
    public function resolve(string $ip): GeoResult;
}
