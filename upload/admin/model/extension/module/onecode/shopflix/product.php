<?php

use Onecode\Shopflix\Helper;

require_once DIR_SYSTEM . 'helper/onecode/shopflix/model/Product.php';

/**
 * @property-read \DB $db
 * @property-read \Language $language
 * @property-read \Loader $load
 * @property-read \ModelCatalogProduct $model_catalog_product
 */
class ModelExtensionModuleOnecodeShopflixProduct extends Helper\Model\Product
{
    public function __construct($registry)
    {
        parent::__construct($registry);
        $this->load->model('catalog/product');
    }

    public function install()
    {
        $this->createTable();
        $products = $this->model_catalog_product->getProducts(['filter_status' => 1]);
        if (count($products))
        {
            $this->enable(array_column($products, 'product_id'));
        }
    }

    public function uninstall()
    {
        $this->db->query(sprintf('DROP TABLE IF EXISTS %s', self::getTableName()));
    }

    public function clearAll()
    {
        $this->db->query(sprintf("TRUNCATE TABLE %s", self::getTableName()));
    }

    public function enable(array $ids)
    {
        $query = $this->db->query(sprintf("SELECT product_id FROM %s", self::getTableName()));
        $stored_ids = array_column($query->rows, 'product_id');
        $news = array_diff($ids, $stored_ids);
        array_walk($news, function ($id) {
            $this->db->query(sprintf("INSERT INTO %s (`product_id`,`status`) VALUES (%d, 1)", self::getTableName(),
                $id));
        });
    }

    public function disable(array $ids)
    {
        $query = $this->db->query(sprintf("SELECT product_id FROM %s", self::getTableName()));
        $stored_ids = array_column($query->rows, 'product_id');
        $common = array_intersect($ids, $stored_ids);
        $this->db->query(sprintf("DELETE FROM %s WHERE product_id in (%s)", self::getTableName(), implode(",",
            $common)));
    }
}