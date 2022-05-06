<?php
namespace Onecode\Shopflix\Helper\Model;

use Model;
use const DB_PREFIX;

/**
 * @property-read \Config $config
 * @property-read \DB $db
 */
class Product extends Model
{
    protected function createTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `product_id` INT UNSIGNED NOT NULL,
 `status` tinyint(1) UNSIGNED NOT NULL default 0,
 PRIMARY KEY (`product_id`)
)", self::getTableName()));
    }

    public static function getTableName(): string
    {
        return DB_PREFIX . 'onecode_shopflix_product_xml';
    }

    public function getTotalProducts(array $data = []): int
    {
        $sql = [
            sprintf("SELECT COUNT(DISTINCT op.product_id) AS total FROM %s AS op", self::getTableName()),
            sprintf("INNER JOIN %s%s AS p ON p.product_id = op.product_id", DB_PREFIX, 'product'),
            sprintf("LEFT JOIN %s%s AS pd ON p.product_id = pd.product_id", DB_PREFIX, 'product_description'),
            sprintf("WHERE pd.language_id = '%d'", (int) $this->config->get('config_language_id')),
        ];

        if (! empty($data['filter_name']))
        {
            $sql[] = " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }
        if (! empty($data['filter_model']))
        {
            $sql[] = " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }
        if (isset($data['filter_status']) && $data['filter_status'] !== '')
        {
            $sql[] = " AND p.status = '" . (int) $data['filter_status'] . "'";
        }
        if (isset($data['filter_enabled']) && $data['filter_enabled'] !== '')
        {
            $sql[] = " AND op.status = '" . (int) $data['filter_enabled'] . "'";
        }
        $query = $this->db->query(implode(' ', $sql));

        return (int) $query->row['total'];
    }

    /**
     * @param array $data
     *
     * @return array
     */
    public function getAllProducts(array $data = []): array
    {
        $sql = [
            sprintf("SELECT 
       pd.name,
       pd.description,
       mp.name as manufacturer,
       p.*,
       (CASE WHEN op.status IS NOT NULL THEN op.status ELSE 0 END) AS enabled
FROM %s%s AS p",
                DB_PREFIX, 'product'),
            sprintf("LEFT JOIN %s%s AS pd ON p.product_id = pd.product_id", DB_PREFIX, 'product_description'),
            sprintf("LEFT JOIN %s%s AS pc ON p.product_id = pc.product_id", DB_PREFIX, 'product_to_category'),
            sprintf("LEFT JOIN %s%s AS cd ON pc.category_id = cd.category_id", DB_PREFIX, 'category_description'),
            sprintf("LEFT JOIN %s%s AS mp ON p.manufacturer_id = mp.manufacturer_id", DB_PREFIX, 'manufacturer'),
            sprintf("LEFT JOIN %s AS op ON p.product_id = op.product_id", self::getTableName()),
            sprintf("WHERE pd.language_id = '%d' AND cd.language_id = '%d'"
                , (int) $this->config->get('config_language_id')
                , (int) $this->config->get('config_language_id')),
        ];

        if (! empty($data['filter_product_id']))
        {
            $list = is_array($data['filter_product_id']) ? $data['filter_product_id'] : [$data['filter_product_id']];
            $sql[] = sprintf(" AND p.product_id IN (%s)", implode(',', $list));
        }
        if (! empty($data['filter_manufacturer']))
        {
            $sql[] = " AND mp.name LIKE '" . $this->db->escape($data['filter_manufacturer']) . "%'";
        }
        if (! empty($data['filter_category']))
        {
            $sql[] = " AND cd.name LIKE '" . $this->db->escape($data['filter_category']) . "%'";
        }
        if (! empty($data['filter_name']))
        {
            $sql[] = " AND pd.name LIKE '" . $this->db->escape($data['filter_name']) . "%'";
        }
        if (! empty($data['filter_model']))
        {
            $sql[] = " AND p.model LIKE '" . $this->db->escape($data['filter_model']) . "%'";
        }
        if (isset($data['filter_status']) && $data['filter_status'] !== '')
        {
            $sql[] = " AND p.status = '" . (int) $data['filter_status'] . "'";
        }
        if (isset($data['filter_enabled']) && $data['filter_enabled'] == '1')
        {
            $sql[] = " AND op.status = '" . (int) $data['filter_enabled'] . "'";
        }

        $sql[] = " GROUP BY p.product_id";

        $sort_data = [
            'pd.name',
            'mp.name',
            'p.model',
            'p.status',
            'enabled',
            'p.sort_order',
        ];

        $sql[] = (isset($data['sort']) && in_array($data['sort'], $sort_data))
            ? " ORDER BY " . $data['sort']
            : " ORDER BY pd.name";

        $sql[] = isset($data['order']) && ($data['order'] == 'DESC') ? " DESC" : " ASC";

        if (isset($data['start']) || isset($data['limit']))
        {
            $data['start'] = max($data['start'], 0);
            $data['limit'] = $data['limit'] < 1 ? 20 : $data['limit'];
            $sql[] = " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
        }

        //print_r(implode(' ', $sql));
        $query = $this->db->query(implode(' ', $sql));
        $products = $query->rows;
        if (count($products))
        {
            $products = array_map(function ($product) {
                $attrs = $this->db->query(sprintf('SELECT * FROM %s%s WHERE product_id = %d and language_id = %d',
                    DB_PREFIX, 'product_attribute', $product['product_id'], (int) $this->config->get('config_language_id')));
                if (count($attrs->rows))
                {
                    $items = $attrs->rows;
                    $items = array_map(function ($item) {
                        return [
                            'attribute_id' => $item['attribute_id'],
                            'name' => $item['text'],
                        ];
                    }, $items);
                    $product['attributes'] = $items;
                }
                return $product;
            }, $products);
        }

        return $products;
    }

    public function getProduct($product_id)
    {
        $query = implode(' ', [
            "SELECT DISTINCT * FROM " . self::getTableName() . " as p",
            "WHERE p.product_id = '" . (int) $product_id . "'",
        ]);
        $query = $this->db->query($query);

        return $query->row;
    }

    public function getProductCategoriesName($product_id)
    {
        $sql = sprintf('select cd.name as name
                    from %s as cd
                    inner join %s as pc on pc.category_id = cd.category_id
                    WHERE pc.product_id = %d AND cd.language_id = %d'
            , DB_PREFIX . 'category_description'
            , DB_PREFIX . 'product_to_category'
            , $product_id
            , $this->config->get('config_language_id')
        );
        $query = $this->db->query($sql);

        return array_map(function ($item) {
            return $item['name'];
        }, $query->rows);
    }
}