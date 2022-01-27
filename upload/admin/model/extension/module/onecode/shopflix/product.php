<?php

use Onecode\Shopflix\Helper;

require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Product.php';

/**
 * @property-read \DB $db
 * @property-read \Language $language
 * @property-read \Loader $load
 */
class ModelExtensionModuleOnecodeShopflixProduct extends Helper\Model\Product
{
    public function __construct($registry)
    {
        parent::__construct($registry);
    }

    public function install()
    {
        $this->createTable();
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getTableName()));
    }
}