<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

/**
 * Generates an array of time zones and their local data
 * @return array
 */
function gplcart_date_timezones()
{
    $zones = array();
    $timestamp = GC_TIME;
    $default_timezone = date_default_timezone_get();

    foreach (timezone_identifiers_list() as $zone) {
        date_default_timezone_set($zone);
        $zones[$zone] = '(UTC/GMT ' . date('P', $timestamp) . ') ' . $zone;
    }

    date_default_timezone_set($default_timezone);
    return $zones;
}
