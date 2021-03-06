<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Config;
use gplcart\core\Hook;
use gplcart\core\models\Translation as TranslationModel;

/**
 * Manages basic behaviors and data related to product SKU
 */
class Sku
{

    /**
     * Database class instance
     * @var \gplcart\core\Database $db
     */
    protected $db;

    /**
     * Hook class instance
     * @var \gplcart\core\Hook $hook
     */
    protected $hook;

    /**
     * Config class instance
     * @var \gplcart\core\Config $config
     */
    protected $config;

    /**
     * Translation UI model instance
     * @var \gplcart\core\models\Translation $translation
     */
    protected $translation;

    /**
     * @param Hook $hook
     * @param Config $config
     * @param Translation $translation
     */
    public function __construct(Hook $hook, Config $config, TranslationModel $translation)
    {
        $this->hook = $hook;
        $this->config = $config;
        $this->db = $this->config->getDb();
        $this->translation = $translation;
    }

    /**
     * Loads a SKU
     * @param int|array $condition
     * @return array
     */
    public function get($condition)
    {
        $result = null;
        $this->hook->attach('sku.get.before', $condition, $result, $this);

        if (isset($result)) {
            return $result;
        }

        if (!is_array($condition)) {
            $condition = array('product_sku_id' => $condition);
        }

        $condition['limit'] = array(0, 1);
        $list = (array) $this->getList($condition);
        $result = empty($list) ? array() : reset($list);

        $this->hook->attach('sku.get.after', $condition, $result, $this);
        return $result;
    }

    /**
     * Returns an array of SKUs or counts them
     * @param array $options
     * @return array|integer
     */
    public function getList(array $options = array())
    {
        $result = null;
        $this->hook->attach('sku.list.before', $options, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $sql = 'SELECT ps.*, p.title, p.currency';

        if (!empty($options['count'])) {
            $sql = 'SELECT COUNT(ps.product_sku_id)';
        }

        $sql .= ' FROM product_sku ps LEFT JOIN product p ON(ps.product_id = p.product_id)';

        $conditions = array();

        if (isset($options['product_sku_id'])) {
            $sql .= ' WHERE ps.product_sku_id = ?';
            $conditions[] = $options['product_sku_id'];
        } else {
            $sql .= ' WHERE ps.product_sku_id IS NOT NULL';
        }

        if (isset($options['sku'])) {
            $sql .= ' AND ps.sku=?';
            $conditions[] = $options['sku'];
        }

        if (isset($options['store_id'])) {
            $sql .= ' AND p.store_id=?';
            $conditions[] = $options['store_id'];
        }

        if (isset($options['title_sku'])) {
            $sql .= ' AND (p.title LIKE ? OR ps.sku LIKE ?)';
            $conditions[] = "%{$options['title_sku']}%";
            $conditions[] = "%{$options['title_sku']}%";
        }

        if (isset($options['product_id'])) {
            $sql .= ' AND ps.product_id=?';
            $conditions[] = $options['product_id'];
        }

        if (isset($options['combination_id'])) {
            $sql .= ' AND ps.combination_id=?';
            $conditions[] = $options['combination_id'];
        }

        if (isset($options['status'])) {
            $sql .= ' AND ps.status=?';
            $conditions[] = (int) $options['status'];
        }

        $sql .= " ORDER BY ps.sku ASC";

        if (!empty($options['limit'])) {
            $sql .= ' LIMIT ' . implode(',', array_map('intval', $options['limit']));
        }

        if (empty($options['count'])) {
            $list = $this->db->fetchAll($sql, $conditions, array('index' => 'product_sku_id'));
            $result = $this->prepareList($list);
        } else {
            $result = (int) $this->db->fetchColumn($sql, $conditions);
        }

        $this->hook->attach('sku.list.after', $options, $result, $this);
        return $result;
    }

    /**
     * Adds a SKU
     * @param array $data
     * @return integer
     */
    public function add(array $data)
    {
        $result = null;
        $this->hook->attach('sku.add.before', $data, $result, $this);

        if (isset($result)) {
            return (int) $result;
        }

        $result = $this->db->insert('product_sku', $data);
        $this->hook->attach('sku.add.after', $data, $result, $this);
        return (int) $result;
    }

    /**
     * Deletes a product SKU
     * @param integer $product_id
     * @param array $options
     * @todo Refactor
     * @return boolean
     */
    public function delete($product_id, array $options = array())
    {
        $result = null;
        $this->hook->attach('sku.delete.before', $product_id, $options, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        $sql = 'DELETE FROM product_sku WHERE product_id=?';

        if (!empty($options['combinations'])) {
            $sql .= ' AND LENGTH(combination_id) > 0';
        }

        if (!empty($options['base'])) {
            $sql .= ' AND LENGTH(combination_id) = 0';
        }

        $result = (bool) $this->db->run($sql, array($product_id))->rowCount();
        $this->hook->attach('sku.delete.after', $product_id, $options, $result, $this);
        return (bool) $result;
    }

    /**
     * Generates a SKU
     * @param string $pattern
     * @param array $options
     * @return string
     */
    public function generate($pattern, array $options = array())
    {
        $options += array('store_id' => null, 'placeholders' => array());

        $result = null;
        $this->hook->attach('sku.generate.before', $pattern, $options, $result);

        if (isset($result)) {
            return (string) $result;
        }

        $sku = $pattern;
        if (!empty($options['placeholders'])) {
            $sku = gplcart_string_replace($pattern, $options['placeholders'], $options);
        }

        $result = $this->getUnique(mb_strimwidth($sku, 0, 200, ''), $options['store_id']);
        $this->hook->attach('sku.generate.after', $pattern, $options, $result);
        return $result;
    }

    /**
     * Returns a product SKU pattern
     * @return string
     */
    public function getPattern()
    {
        return $this->config->get("product_sku_pattern", 'PRODUCT-%i');
    }

    /**
     * Returns an array of placeholders for a product SKU pattern
     * @return array
     */
    public function getPatternPlaceholders()
    {
        return $this->config->get('product_sku_placeholder', array('%i' => 'product_id'));
    }

    /**
     * Returns a unique SKU for the given store ID
     * @param string $sku
     * @param integer|null $store_id
     * @return string
     */
    public function getUnique($sku, $store_id)
    {
        $existing = $this->get(array('sku' => $sku, 'store_id' => $store_id));

        if (empty($existing)) {
            return $sku;
        }

        $counter = 1;

        do {
            $modified = $sku . '-' . $counter;
            $counter++;
        } while ($this->get(array('sku' => $modified, 'store_id' => $store_id)));

        return $modified;
    }

    /**
     * Returns an array of field value IDs from a combination ID
     * @param string $combination_id
     * @return array
     */
    public function getFieldValues($combination_id)
    {
        $field_value_ids = explode('_', substr($combination_id, strpos($combination_id, '-') + 1));
        sort($field_value_ids);
        return $field_value_ids;
    }

    /**
     * Creates a field combination id from an array of field value ids
     * @param array $field_value_ids
     * @param null|integer $product_id
     * @return string
     */
    public function getCombinationId(array $field_value_ids, $product_id = null)
    {
        sort($field_value_ids);
        $combination_id = implode('_', $field_value_ids);
        return empty($product_id) ? $combination_id : "$product_id-$combination_id";
    }

    /**
     * Returns an array of data when selecting SKU combinations
     * @param array $product
     * @param array $field_value_ids
     * @return array
     */
    public function selectCombination(array $product, array $field_value_ids)
    {
        $result = array();
        $this->hook->attach('sku.select.combination.before', $product, $field_value_ids, $result, $this);

        if (!empty($result)) {
            return (array) $result;
        }

        $access = !empty($product['stock']) || empty($product['subtract']);

        $result = array(
            'modal' => '',
            'severity' => '',
            'cart_access' => $access,
            'combination' => array(),
            'sku' => $product['sku'],
            'price' => $product['price'],
            'currency' => $product['currency'],
            'message' => $access ? '' : $this->translation->text('Out of stock')
        );

        if (empty($field_value_ids)) {
            $this->hook->attach('sku.select.combination.after', $product, $field_value_ids, $result, $this);
            return (array) $result;
        }

        if (empty($product['status'])) {
            $result['severity'] = 'danger';
            $result['message'] = $this->translation->text('Unavailable');

            $this->hook->attach('sku.select.combination.after', $product, $field_value_ids, $result, $this);
            return (array) $result;
        }

        $combination_id = $this->getCombinationId($field_value_ids, $product['product_id']);

        if (empty($product['combination'][$combination_id]['status'])) {

            $result['not_matched'] = true;
            $result['cart_access'] = false;

            $result['severity'] = 'danger';
            $result['message'] = $this->translation->text('Unavailable');
            $result['related'] = $this->getRelatedFieldValues($product, $field_value_ids);

            $this->hook->attach('sku.select.combination.after', $product, $field_value_ids, $result, $this);
            return (array) $result;
        }

        $result['combination'] = $product['combination'][$combination_id];
        $result['combination']['currency'] = $product['currency'];

        $result['sku'] = $result['combination']['sku'];
        $result['price'] = $result['combination']['price'];

        if (empty($result['combination']['stock']) && $product['subtract']) {
            $result['cart_access'] = false;
            $result['severity'] = 'warning';
            $result['message'] = $this->translation->text('Out of stock');
        }

        $this->hook->attach('sku.select.combination.after', $product, $field_value_ids, $result, $this);
        return (array) $result;
    }

    /**
     * Prepare an array of product SKU items
     * @param array $list
     * @return array
     */
    protected function prepareList(array $list)
    {
        foreach ($list as &$item) {
            $item['fields'] = $this->getFieldValues($item['combination_id']);
        }

        return $list;
    }

    /**
     * Returns an array of related fields value IDs
     * @todo Rethink this. It should return all possible combinations
     * @param array $product
     * @param array $ids
     * @return array
     */
    protected function getRelatedFieldValues(array $product, array $ids)
    {
        $related = array();
        foreach ($product['combination'] as $combination) {
            if (array_intersect($ids, $combination['fields'])) {
                $related += $combination['fields'];
            }
        }

        return $related;
    }

}
