<?php
namespace Onecode\Shopflix\Helper\Model;

/**
 * @property-read \Config $config
 * @property-read \DB $db
 */
class Product extends \Model
{
    protected function createTable()
    {
        $this->db->query(sprintf("CREATE TABLE IF NOT EXISTS %s (
 `product_id` INT UNSIGNED NOT NULL,
 `status` tinyint(1) UNSIGNED NOT NULL default 0,
 PRIMARY KEY (`product_id`)
)", self::getTableName()));
    }

    public static function getTableName()
    {
        return \DB_PREFIX . 'onecode_shopflix_product_xml';
    }

    public function getTotalProducts(array $data = []): int
    {
        $sql = [
            sprintf("SELECT COUNT(DISTINCT op.product_id) AS total FROM %s AS op", self::getTableName()),
            sprintf("INNER JOIN %s%s AS p ON p.product_id = op.product_id", \DB_PREFIX, 'product'),
            sprintf("LEFT JOIN %s%s AS pd ON p.product_id = pd.product_id", \DB_PREFIX, 'product_description'),
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
     * @param array $filters
     *
     * @return array
     */
    public function getAllProducts(array $data = []): array
    {
        $sql = [
            sprintf("SELECT 
       pd.name,
       p.*,
       (CASE WHEN op.status IS NOT NULL THEN op.status ELSE 0 END) AS enabled
FROM %s%s AS p",
                \DB_PREFIX, 'product'),
            sprintf("LEFT JOIN %s%s AS pd ON p.product_id = pd.product_id", \DB_PREFIX, 'product_description'),
            sprintf("LEFT JOIN %s AS op ON p.product_id = op.product_id", self::getTableName()),
            sprintf("WHERE pd.language_id = '%d'", (int) $this->config->get('config_language_id')),
        ];

        if (! empty($data['filter_product_id']))
        {
            $list = is_array($data['filter_product_id']) ? $data['filter_product_id'] : [$data['filter_product_id']];
            $sql[] = sprintf(" AND p.product_id IN (%s)", implode(',', $list));
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

        return $query->rows;
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
}