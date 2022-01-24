<?php

use Onecode\Shopflix\Helper;

/**
 * @property-read \DB $db
 * @property-read \Loader $load
 */
class ModelExtensionModuleOnecodeShopflixProduct extends Model
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->helper('onecode/shopflix/model/Product');
    }

    public function install()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `proiduct_id` INT UNSIGNED NOT NULL,
 `status` tinyint(1) UNSIGNED NOT NULL default 0,
 `mpn` varchar(255),
 `ean` varchar(255),
 `title` varchar(255),
 `description` varchar(255),
 `manufacturer` varchar(255),
 `weight` varchar(255),
 PRIMARY KEY (`proiduct_id`)
)", Helper\Model\Product::getTableName()));
    }

    public function uninstall(){
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', Helper\Model\Product::getTableName()));
    }
}