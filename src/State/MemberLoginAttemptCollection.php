<?php declare(strict_types=1);

namespace SilverStripe\LoginMonitor\State;

use SilverStripe\Core\Injector\Injectable;

class MemberLoginAttemptCollection
{
    use Injectable;

    /**
     * @var MemberLoginAttempt[]
     */
    private $attempts = [];

    /**
     * @param MemberLoginAttempt[] $attempts
     */
    public function __construct(array $attempts)
    {
        $this->attempts = $attempts;
    }

    /**
     * @param string $ip
     * @return LoginAttemptCollection
     */
    public function forIP(string $ip): LoginAttemptCollection
    {
        $attempts = [];
        foreach ($this->getAttemptCollection()->getAttempts() as $attempt) {
            if ($attempt->getIP() === $ip) {
                $attempts[] = $attempt;
            }
        }
        return LoginAttemptCollection::create($attempts);
    }

    /**
     * @return LoginAttemptCollection
     */
    public function getAttemptCollection(): LoginAttemptCollection
    {
        return LoginAttemptCollection::create($this->attempts);
    }

    public function getDefaultCountryCode(int $minimumLoginAttempts, float $defaultCountryRatio): string
    {
        $maxLoginCount = 0;
        $maxLoginFrom = null;

        if ($minimumLoginAttempts > $this->getAttemptCollection()->getSuccessful()) {
            // Cannot compute statistics, data set is not big enough
            return '';
        }

        // Count successful login attempts per IP address so we can work out the default location
        foreach ($this->getAttemptCollection()->getIPs() as $ip) {
            foreach ($this->getAttemptCollection()->getAttempts() as $attempt) {
                if ($attempt->getIP() !== $ip) {
                    continue;
                }

                $successfulLoginsForIP = $this->forIP($ip)->getSuccessful();
                if ($successfulLoginsForIP > $maxLoginCount) {
                    // Set a new max login count from
                    $maxLoginCount = $successfulLoginsForIP;
                    $maxLoginFrom = $attempt->getGeoResult()->getCountryCode();
                }
            }
        }

        // Check country's successful login attempt count is high enough overall
        if (($maxLoginCount / $this->getAttemptCollection()->getSuccessful()) > $defaultCountryRatio) {
            return $maxLoginFrom;
        }

        // The ratio of successful logins from this country is not high enough overall to determine it as a default
        return '';
    }
}
