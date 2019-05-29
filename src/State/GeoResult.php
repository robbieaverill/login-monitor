<?php declare(strict_types=1);

namespace SilverStripe\LoginMonitor\State;

use SilverStripe\Core\Injector\Injectable;

/**
 * A state encapsulation class for part of the result from a geoplugin.net API response
 */
class GeoResult
{
    use Injectable;

    /**
     * @var string[]
     */
    protected $data;

    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * The IP for which the geographic information was requested
     *
     * @return string
     */
    public function getIp(): string
    {
        return $this->getData('request');
    }

    /**
     * HTTP status code from the API response data, e.g. 200
     *
     * @return int
     */
    public function getStatus(): int
    {
        return (int) $this->getData('status');
    }

    /**
     * City name, e.g. "Fort Lauderdale"
     *
     * @return string
     */
    public function getCity(): string
    {
        return $this->getData('city');
    }

    /**
     * Region name, e.g. "Florida"
     *
     * @return string
     */
    public function getRegion(): string
    {
        return $this->getData('region');
    }

    /**
     * Region code, e.g. "FL"
     *
     * @return string
     */
    public function getRegionCode(): string
    {
        return $this->getData('regionCode');
    }

    /**
     * Country name, e.g. "United States"
     *
     * @return string
     */
    public function getCountryName(): string
    {
        return $this->getData('countryName');
    }

    /**
     * Country code, e.g. "US"
     *
     * @return string
     */
    public function getCountryCode(): string
    {
        return $this->getData('countryCode');
    }

    /**
     * @param string $item
     * @return string
     */
    protected function getData($item): string
    {
        $key = 'geoplugin_' . $item;
        if (isset($this->data[$key])) {
            return (string) $this->data[$key];
        }
        return '';
    }
}
