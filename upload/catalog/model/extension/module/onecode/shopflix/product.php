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
    public function getAllEnabledProducts($filters = []): array
    {
        $data = $this->db->query("SELECT product_id FROM %s ", self::getTableName())->rows;
        $filters['filter_product_id'] = array_column($data, 'product_id');
        return $this->getAllProducts($filters);
    }
}