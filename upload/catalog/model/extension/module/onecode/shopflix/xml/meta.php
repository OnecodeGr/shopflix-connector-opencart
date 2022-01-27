<?php

/**
 * @property-read \Config $config
 * @property-read \Language $language
 */
class ModelExtensionModuleOnecodeShopflixXmlMeta extends Model
{
    /**
     * @var int
     */
    private $lastUpdate;

    /**
     * @var string
     */
    private $storeCode;

    /**
     * @var string
     */
    private $storeName;

    /**
     * @var string
     */
    private $locale;

    /**
     * @var int
     */
    private $count;

    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    /**
     * @param int $lastUpdate
     */
    public function setLastUpdate(int $lastUpdate): void
    {
        $this->lastUpdate = $lastUpdate;
    }

    /**
     * @param string $storeCode
     */
    public function setStoreCode(string $storeCode): void
    {
        $this->storeCode = $storeCode;
    }

    /**
     * @param string $storeName
     */
    public function setStoreName(string $storeName): void
    {
        $this->storeName = $storeName;
    }

    /**
     * @param string $locale
     */
    public function setLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    /**
     * @param int $count
     */
    public function setCount(int $count): void
    {
        $this->count = $count;
    }

    /**
     * @return int
     */
    public function getLastUpdate(): int
    {
        return $this->lastUpdate;
    }

    /**
     * @return string
     */
    public function getStoreCode(): string
    {
        return $this->storeCode;
    }

    /**
     * @return string
     */
    public function getStoreName(): string
    {
        return $this->storeName;
    }

    /**
     * @return string
     */
    public function getLocale(): string
    {
        return $this->locale;
    }

    /**
     * @return int
     */
    public function getCount(): int
    {
        return $this->count;
    }
}