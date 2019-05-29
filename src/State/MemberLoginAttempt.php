<?php declare(strict_types=1);

namespace SilverStripe\LoginMonitor\State;

use SilverStripe\Core\Injector\Injectable;
use SilverStripe\LoginMonitor\Service\IPResolverInterface;
use SilverStripe\LoginMonitor\Service\IPResolverService;

class MemberLoginAttempt
{
    use Injectable;

    /**
     * @var int
     */
    private $memberID = 0;

    /**
     * @var string
     */
    private $ip = '';

    /**
     * @var bool
     */
    private $success = true;

    /**
     * @param int $memberID
     * @param string $ip
     * @param bool $success
     */
    public function __construct(int $memberID, string $ip, bool $success)
    {
        $this->memberID = $memberID;
        $this->ip = $ip;
        $this->success = $success;
    }

    /**
     * @return string
     */
    public function getIP(): string
    {
        return $this->ip;
    }

    /**
     * Whether the login attempt was successful
     *
     * @return bool
     */
    public function getSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return int
     */
    public function getMemberID(): int
    {
        return $this->memberID;
    }

    /**
     * Returns geographic information about the IP address used in the attempt
     *
     * @return GeoResult
     */
    public function getGeoResult(): GeoResult
    {
        /** @var IPResolverInterface $resolver */
        $resolver = IPResolverService::singleton();
        return $resolver->resolve($this->getIP());
    }
}
