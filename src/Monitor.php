<?php declare(strict_types=1);

namespace SilverStripe\LoginMonitor;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\LoginMonitor\Service\IPResolverService;
use SilverStripe\LoginMonitor\State\GeoResult;
use SilverStripe\ORM\DataList;
use SilverStripe\Security\LoginAttempt;

/**
 * Monitors login attempts and notifies affected users if there have been suspicious attempts to login to their
 * account from outside their primary country.
 */
class Monitor
{
    use Configurable;
    use Injectable;

    /**
     * The minimum number of LoginAttempt records that must exist for a Member before checks will be performed
     * on it. Used to help eliminate early false positives.
     *
     * @config
     * @var int
     */
    private static $minimum_login_attempts = 3;

    /**
     * Define the ratio of login attempts that come from a single country in order for it to be identified as that
     * user's default country. Default is 80% of login attempts from a single country (0.8).
     *
     * @config
     * @var float
     */
    private static $default_country_ratio = 0.8;

    /**
     * Optionally define a list of country codes that will be ignored when LoginAttempts are made from them. This
     * can be useful if your staff are always in one country.
     *
     * @var string[]
     */
    private static $whitelisted_country_codes = [];

    /**
     * @var IPResolverService
     */
    protected $ipResolver;

    /**
     * @param DataList|LoginAttempt[] $loginAttempts
     * @return GeoResult[]
     */
    public function process(DataList $loginAttempts): array
    {
        $results = $this->getListResults($loginAttempts);

        foreach ($results as $memberId => $result) {
            // Get default country
            $defaultCountryCode = $this->getDefaultCountryCode($result);
            if (!$defaultCountryCode) {
                continue;
            }
            $results[$memberId]['default_country_code'] = $defaultCountryCode;

            // Find outliers not from that country
            $results[$memberId]['outliers'] = $this->identifyLoginsFromNonStandardLocations(
                $defaultCountryCode,
                $result['ips']
            );
        }

        return $results;
    }

    /**
     * Groups the list of LoginAttempts by member ID, then by IP, and produces a summary of successful and failed
     * login attempt counts as well as a geographic information object for the IP's physical location.
     *
     * @param DataList $loginAttempts
     * @return array
     */
    protected function getListResults(DataList $loginAttempts): array
    {
        $result = [];

        $memberIds = $loginAttempts->columnUnique('MemberID');

        foreach ($memberIds as $memberId) {
            foreach ($loginAttempts as $loginAttempt) {
                // Group by member ID
                if ($loginAttempt->MemberID != $memberId) {
                    continue;
                }

                // Create initial entry for member
                if (!array_key_exists($memberId, $result)) {
                    $result[$memberId] = [
                        'total_success' => 0,
                        'total_failure' => 0,
                        'ips' => [],
                    ];
                }

                $ip = $loginAttempt->IP;
                // Create initial entry for IP on member
                if (!array_key_exists($ip, $result[$memberId])) {
                    $result[$memberId]['ips'][$ip] = [
                        'success' => 0,
                        'failure' => 0,
                        'geo_information' => $this->getIPResolver()->resolve($ip),
                    ];
                }

                // Allocate result to member and IP
                if ($loginAttempt->Status === 'Success') {
                    $result[$memberId]['total_success']++;
                    $result[$memberId]['ips'][$ip]['success']++;
                } else {
                    $result[$memberId]['total_failure']++;
                    $result[$memberId]['ips'][$ip]['failure']++;
                }
            }
        }

        return $result;
    }

    /**
     * Given a grouped login attempt result set for a member, find the usual country code they login from
     *
     * @param array $result
     * @return string       Returns empty string if the country code can not be obtained because of filtering rules or
     *                      insufficient data set size
     */
    protected function getDefaultCountryCode(array $result): string
    {
        $maxLoginCount = 0;
        $maxLoginFrom = null;
        $minimumAttempts = (int) $this->config()->get('minimum_login_attempts');
        $countryRatio = (int) $this->config()->get('default_country_ratio');

        if ($minimumAttempts > $result['total_success']) {
            // Cannot compute statistics, data set is not big enough
            return '';
        }

        // Count successful login attempts per country
        foreach ($result['ips'] as $ip => $ipResults) {
            /** @var GeoResult $geoInfo */
            $geoInfo = $ipResults['geo_information'];

            if ($ipResults['success'] > $maxLoginCount) {
                // Set a new max login count from
                $maxLoginCount = $ipResults['success'];
                $maxLoginFrom = $geoInfo->getCountryCode();
            }
        }

        // Check country's successful login attempt count is high enough overall
        if (($maxLoginCount / $result['total_success']) > $countryRatio) {
            return $maxLoginFrom;
        }

        // The ratio of successful logins from this country is not high enough overall to determine it as a default
        return '';
    }

    /**
     * For a member, the $attempts are a summary of successful and failed login attempts and a list of IPs that they
     * were made from with geographic information attached. This method will find those that aren't part of the
     * expected location and return them in a list of this format:
     *
     * <code>
     * [
     *      $ip => [
     *          'success' => n,
     *          'failure' => n,
     *          'geo_information' => GeoResult,
     *      ],
     * ]
     * </code>
     *
     * @param string $defaultCountryCode
     * @param array $attempts
     * @return array
     */
    protected function identifyLoginsFromNonStandardLocations($defaultCountryCode, $attempts): array
    {
        $outliers = [];
        foreach ($attempts as $ip => $ipResults) {
            /** @var GeoResult $geoInfo */
            $geoInfo = $ipResults['geo_information'];

            // Find attempts that were made from non-standard countries
            if ($geoInfo->getCountryCode() !== $defaultCountryCode) {
                $outliers[$ip] = $ipResults;
            }
        }
        return $outliers;
    }

    /**
     * @return IPResolverService
     */
    protected function getIPResolver(): IPResolverService
    {
        if (!$this->ipResolver) {
            $this->ipResolver = IPResolverService::create();
        }
        return $this->ipResolver;
    }
}
