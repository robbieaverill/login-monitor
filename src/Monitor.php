<?php declare(strict_types=1);

namespace SilverStripe\LoginMonitor;

use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\LoginMonitor\State\LoginAttemptCollection;
use SilverStripe\LoginMonitor\State\MemberLoginAttemptCollection;
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
     * @param DataList|LoginAttempt[] $loginAttempts
     * @return array
     */
    public function process(DataList $loginAttempts): array
    {
        $attemptCollection = LoginAttemptCollection::fromDataList($loginAttempts);
        $memberIds = $loginAttempts->columnUnique('MemberID');

        $results = [];
        foreach ($memberIds as $memberId) {
            $attempts = $attemptCollection->forMember((int) $memberId);

            $results[$memberId]['attempts'] = $attempts;

            // Get default country
            $defaultCountryCode = $attempts->getDefaultCountryCode(
                (int) $this->config()->get('minimum_login_attempts'),
                (float) $this->config()->get('default_country_ratio')
            );
            if (empty($defaultCountryCode)) {
                continue;
            }

            $results[$memberId]['default_country_code'] = $defaultCountryCode;

            // Find outliers not from that country
            $results[$memberId]['outliers'] = $this->identifyLoginsFromNonStandardLocations(
                $defaultCountryCode,
                $attempts
            );
        }

        return $results;
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
     * @return LoginAttemptCollection[]
     */
    protected function identifyLoginsFromNonStandardLocations(
        string $defaultCountryCode,
        MemberLoginAttemptCollection $attempts
    ): array {
        $outliers = [];
        $processedIps = [];

        foreach ($attempts->getAttemptCollection()->getAttempts() as $attempt) {
            $ip = $attempt->getIP();
            if (in_array($ip, $processedIps)) {
                continue;
            }

            $processedIps[] = $ip;
            $geoInfo = $attempt->getGeoResult();

            // Find attempts that were made from non-standard countries
            if ($geoInfo->getCountryCode() !== $defaultCountryCode) {
                $outliers[$ip] = $attempts->forIP($ip);
            }
        }
        return $outliers;
    }
}
