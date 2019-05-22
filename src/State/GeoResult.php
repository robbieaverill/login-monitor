<?php

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
     * @return string|null
     */
    public function getIp()
    {
        return $this->getData('request');
    }

    /**
     * HTTP status code from the API response data, e.g. 200
     *
     * @return int
     */
    public function getStatus()
    {
        return (int) $this->getData('status');
    }

    /**
     * City name, e.g. "Fort Lauderdale"
     *
     * @return string|null
     */
    public function getCity()
    {
        return $this->getData('city');
    }

    /**
     * Region name, e.g. "Florida"
     *
     * @return string|null
     */
    public function getRegion()
    {
        return $this->getData('region');
    }

    /**
     * Region code, e.g. "FL"
     *
     * @return string|null
     */
    public function getRegionCode()
    {
        return $this->getData('regionCode');
    }

    /**
     * Country name, e.g. "United States"
     *
     * @return string|null
     */
    public function getCountryName()
    {
        return $this->getData('countryName');
    }

    /**
     * Country code, e.g. "US"
     *
     * @return string|null
     */
    public function getCountryCode()
    {
        return $this->getData('countryCode');
    }

    /**
     * @param string $item
     * @return string|null
     */
    protected function getData($item)
    {
        $key = 'geoplugin_' . $item;
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }
}
