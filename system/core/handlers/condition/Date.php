<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\handlers\condition;

use gplcart\core\handlers\condition\Base as BaseHandler;

/**
 * Provides methods to check date/time conditions
 */
class Date extends BaseHandler
{

    public function __construct();

    /**
     * Whether the date condition is met
     * @param array $condition
     * @return boolean
     */
    public function date(array $condition)
    {
        $value = strtotime(reset($condition['value']));
        return empty($value) ? false : $this->compare(GC_TIME, $value, $condition['operator']);
    }

}
