<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Model,
    gplcart\core\Cache;
use gplcart\core\helpers\Filter as FilterHelper;

/**
 * Manages basic behaviors and data related to HTML filters
 */
class Filter extends Model
{

    /**
     * Filter helper instance
     * @var \gplcart\core\helpers\Filter $filter
     */
    protected $filter;

    /**
     * @param FilterHelper $filter
     */
    public function __construct(FilterHelper $filter)
    {
        parent::__construct();

        $this->filter = $filter;
    }

    /**
     * Filter a text string
     * @param string $text
     * @param integer|array $filter
     * @return string
     */
    public function run($text, $filter)
    {
        if (is_string($filter)) {
            $filter = $this->get($filter);
        }

        $result = null;
        $this->hook->attach('filter', $text, $filter, $result, $this);

        if (isset($result)) {
            return (string) $result;
        }

        return $this->filter($text);
    }

    /**
     * Default system text filter
     * @param string $text
     * @return string
     */
    public function filter($text)
    {
        $tags = $this->config->get('filter_allowed_tags', array('a', 'i', 'b', 'em', 'span', 'strong', 'ul', 'ol', 'li'));
        $protocols = $this->config->get('filter_allowed_protocols', array('http', 'ftp', 'mailto'));

        $this->filter->setTags($tags);
        $this->filter->setProtocols($protocols);

        return $this->filter->filter($text);
    }

    /**
     * Returns a filter
     * @param string $filter_id
     * @return array
     */
    public function get($filter_id)
    {
        $filters = $this->getList();
        return empty($filters[$filter_id]) ? array() : $filters[$filter_id];
    }

    /**
     * Returns a filter for the given user role ID
     * @param integer $role_id
     * @return array
     */
    public function getByRole($role_id)
    {
        foreach ($this->getList() as $filter) {
            if (in_array($role_id, $filter['role_id'])) {
                return $filter;
            }
        }
        return array();
    }

    /**
     * Returns an array of defined filters
     * @return array
     */
    public function getList()
    {
        $filters = &Cache::memory(__METHOD__);

        if (isset($filters)) {
            return $filters;
        }

        $filters = array();
        $this->hook->attach('filter.list', $filters, $this);
        return $filters;
    }

}
