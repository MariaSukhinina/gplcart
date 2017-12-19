<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Hook,
    gplcart\core\Config;
use gplcart\core\models\User as UserModel;

/**
 * Manages basic behaviors and data related to user wishlists
 */
class Wishlist
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
     * User model instance
     * @var \gplcart\core\models\User $user
     */
    protected $user;

    /**
     * @param Hook $hook
     * @param Config $config
     * @param UserModel $user
     */
    public function __construct(Hook $hook, Config $config, UserModel $user)
    {
        $this->hook = $hook;
        $this->config = $config;
        $this->db = $this->config->getDb();
        $this->user = $user;
    }

    /**
     * Returns a wishlist
     * @param integer $wishlist_id
     * @return array
     */
    public function get($wishlist_id)
    {
        $result = null;
        $this->hook->attach('wishlist.get.before', $wishlist_id, $result, $this);

        if (isset($result)) {
            return $result;
        }

        $result = $this->db->fetch('SELECT * FROM wishlist WHERE wishlist_id=?', array($wishlist_id));
        $this->hook->attach('wishlist.get.after', $wishlist_id, $result, $this);
        return $result;
    }

    /**
     * Adds a product to a wishlist
     * @param array $data
     * @return integer
     */
    public function add(array $data)
    {
        $result = null;
        $this->hook->attach('wishlist.add.before', $data, $result, $this);

        if (isset($result)) {
            return (int) $result;
        }

        $data['created'] = GC_TIME;
        $result = $this->db->insert('wishlist', $data);

        gplcart_static_clear();

        $this->hook->attach('wishlist.add.after', $data, $result, $this);
        return (int) $result;
    }

    /**
     * Whether the user exceeds max allowed number of products in the wishlist
     * @param integer|string $user_id
     * @param integer $store_id
     * @return boolean
     */
    public function exceedsLimit($user_id, $store_id)
    {
        if ($this->user->isSuperadmin($user_id)) {
            return false; // No limits for superadmin
        }

        $limit = $this->getLimit($user_id);

        if (empty($limit)) {
            return false;
        }

        $conditions = array(
            'user_id' => $user_id,
            'store_id' => $store_id
        );

        $existing = $this->getList($conditions);
        return count($existing) > $limit;
    }

    /**
     * Deletes a wishlist item
     * @param array|int $condition
     * @return boolean
     */
    public function delete($condition)
    {
        $result = null;
        $this->hook->attach('wishlist.delete.before', $condition, $result, $this);

        if (isset($result)) {
            return (bool) $result;
        }

        if (!is_array($condition)) {
            $condition = array('wishlist_id' => (int) $condition);
        }

        $result = (bool) $this->db->delete('wishlist', $condition);

        gplcart_static_clear();

        $this->hook->attach('wishlist.delete.after', $condition, $result, $this);
        return (bool) $result;
    }

    /**
     * Returns an array of wishlist items
     * @param array $data
     * @return array|integer
     */
    public function getList(array $data = array())
    {
        $items = &gplcart_static(gplcart_array_hash(array('wishlist.list' => $data)));

        if (isset($items)) {
            return $items;
        }

        $sql = 'SELECT w.*, u.name AS user_name, u.email';

        if (!empty($data['count'])) {
            $sql = 'SELECT COUNT(w.wishlist_id)';
        }

        $sql .= ' FROM wishlist w'
                . ' LEFT JOIN user u ON(w.user_id = u.user_id)'
                . ' LEFT JOIN product p ON(w.product_id = p.product_id)'
                . ' WHERE w.wishlist_id IS NOT NULL';

        $conditions = array();

        if (!empty($data['product_id'])) {
            settype($data['product_id'], 'array');
            $placeholders = rtrim(str_repeat('?,', count($data['product_id'])), ',');
            $sql .= " AND w.product_id IN($placeholders)";
            $conditions = array_merge($conditions, $data['product_id']);
        }

        if (isset($data['user_id'])) {
            $sql .= ' AND w.user_id = ?';
            $conditions[] = $data['user_id'];
        }

        if (isset($data['store_id'])) {
            $sql .= ' AND w.store_id = ?';
            $conditions[] = (int) $data['store_id'];
        }

        if (isset($data['created'])) {
            $sql .= ' AND w.created = ?';
            $conditions[] = $data['created'];
        }

        if (isset($data['product_status'])) {
            $sql .= ' AND p.status = ?';
            $conditions[] = (int) $data['product_status'];
        }

        $allowed_order = array('asc', 'desc');
        $allowed_sort = array('product_id', 'user_id', 'created');

        if (isset($data['sort']) && in_array($data['sort'], $allowed_sort)//
                && isset($data['order']) && in_array($data['order'], $allowed_order)) {
            $sql .= " ORDER BY w.{$data['sort']} {$data['order']}";
        } else {
            $sql .= " ORDER BY w.created DESC";
        }

        if (!empty($data['limit'])) {
            $sql .= ' LIMIT ' . implode(',', array_map('intval', $data['limit']));
        }

        if (!empty($data['count'])) {
            return (int) $this->db->fetchColumn($sql, $conditions);
        }

        $items = $this->db->fetchAll($sql, $conditions, array('index' => 'wishlist_id'));
        $this->hook->attach('wishlist.list', $items, $this);
        return $items;
    }

    /**
     * Whether a product ID alredy exists in the wishlist
     * @param array $data
     * @return boolean
     */
    public function exists(array $data)
    {
        if (empty($data['product_id'])) {
            return false;
        }

        $product_id = $data['product_id'];
        unset($data['product_id']);
        $items = (array) $this->getList($data);

        foreach ($items as $item) {
            if ($item['product_id'] == $product_id) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns a max number of items for the user
     * @param integer|string $user_id
     * @return integer
     */
    public function getLimit($user_id)
    {
        if (is_numeric($user_id)) {
            $user = $this->user->get($user_id);
            if (isset($user['role_id'])) {
                return $this->getLimitByRole($user['role_id']);
            }
        }

        return $this->getDefaultLimit();
    }

    /**
     * Returns a max number of wishlist items for the role
     * @param string|integer $role_id
     * @return integer
     */
    public function getLimitByRole($role_id)
    {
        return (int) $this->config->get("wishlist_limit_$role_id", $this->getDefaultLimit());
    }

    /**
     * Returns a max number of wishlist items a user can have by default
     * @return integer
     */
    public function getDefaultLimit()
    {
        return (int) $this->config->get('wishlist_limit', 20);
    }

}
