<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Model;
use gplcart\core\Cache;
use gplcart\core\helpers\Request as RequestHelper;

/**
 * Manages basic behaviors and data related to currencies
 */
class Currency extends Model
{

    /**
     * Request class instance
     * @var \gplcart\core\helpers\Request $request
     */
    protected $request;

    /**
     * Constructor
     * @param RequestHelper $request
     */
    public function __construct(RequestHelper $request)
    {
        parent::__construct();

        $this->request = $request;
    }

    /**
     * Adds a currency
     * @param array $data
     * @return boolean
     */
    public function add(array $data)
    {
        $this->hook->fire('add.currency.before', $data);

        if (empty($data)) {
            return false;
        }

        $default = $this->defaultCurrencyValues();

        $data += $default;

        if (!empty($data['default'])) {
            $data['status'] = 1;
            $this->config->set('currency', $data['code']);
        }

        $currencies = $this->getList();

        // Use array_intersect_key to filter out garbage keys
        $currencies[$data['code']] = array_intersect_key($data, $default);
        $this->config->set('currencies', $currencies);

        $this->hook->fire('add.currency.after', $data);
        return true;
    }

    /**
     * Returns an array of currensies
     * @param boolean $enabled
     * @return array
     */
    public function getList($enabled = false)
    {
        $currencies = &Cache::memory("currencies.$enabled");

        if (isset($currencies)) {
            return $currencies;
        }

        $default = $this->getDefaultList();
        $saved = $this->config->get('currencies', array());
        $currencies = gplcart_array_merge($default, $saved);

        $this->hook->fire('currencies', $currencies);

        if (!$enabled) {
            return $currencies;
        }

        $currencies = array_filter($currencies, function ($currency) {
            return !empty($currency['status']);
        });

        return $currencies;
    }

    /**
     * Updates a currency
     * @param string $code
     * @param array $data
     * @return boolean
     */
    public function update($code, array $data)
    {
        $this->hook->fire('update.currency.before', $code, $data);

        $currencies = $this->getList();

        if (empty($currencies[$code])) {
            return false;
        }

        if (!empty($data['default'])) {
            $data['status'] = 1;
            $this->config->set('currency', $code);
        }

        $data += $currencies[$code];
        $default = $this->defaultCurrencyValues();

        // Use array_intersect_key to filter out garbage keys
        $currencies[$code] = array_intersect_key($data, $default);
        $this->config->set('currencies', $currencies);

        $this->hook->fire('update.currency.after', $data);
        return true;
    }

    /**
     * Deletes a currency
     * @param string $code
     * @return boolean
     */
    public function delete($code)
    {
        $this->hook->fire('delete.currency.before', $code);

        $currencies = $this->getList();

        if (empty($currencies[$code]) || !$this->canDelete($code)) {
            return false;
        }

        unset($currencies[$code]);
        $this->config->set('currencies', $currencies);

        $this->hook->fire('delete.currency.after', $code);
        return true;
    }

    /**
     * Returns true if the currency can be deleted
     * @param string $code
     * @return boolean
     */
    public function canDelete($code)
    {
        if ($code == $this->getDefault()) {
            return false;
        }

        $sql = 'SELECT NOT EXISTS (SELECT currency FROM orders WHERE currency=:code)'
                . ' AND NOT EXISTS (SELECT currency FROM price_rule WHERE currency=:code)'
                . ' AND NOT EXISTS (SELECT currency FROM product WHERE currency=:code)';

        return (bool) $this->db->fetchColumn($sql, array('code' => $code));
    }

    /**
     * Converts currencies
     * @param integer $amount
     * @param string $code
     * @param string $target_code
     * @return integer
     */
    public function convert($amount, $code, $target_code)
    {
        if ($code === $target_code) {
            return $amount; // Nothing to convert
        }

        $currency = $this->get($code);
        $target_currency = $this->get($target_code);

        $exponent = $target_currency['decimals'] - $currency['decimals'];
        $amount *= pow(10, $exponent);

        return $amount * ($currency['conversion_rate'] / $target_currency['conversion_rate']);
    }

    /**
     * Loads a currency from the database
     * @param null|string $code
     * @return array|string
     */
    public function get($code = null)
    {
        $currency = &Cache::memory("currency.$code");

        if (isset($currency)) {
            return $currency;
        }

        $list = $this->getList();

        if (!empty($code)) {
            $currency = empty($list[$code]) ? array() : $list[$code];
            return $currency;
        }

        $code = $this->getFromUrl();

        if (empty($code)) {
            $code = $this->getFromCookie();
        }

        if (empty($list[$code])) {
            $code = $this->getDefault();
        }

        $this->setCookie($code);
        return $currency = $code;
    }

    /**
     * Saves a currency code in cookie
     * @param string $code
     */
    public function setCookie($code)
    {
        $lifespan = $this->config->get('currency_cookie_lifespan', 31536000);
        $this->request->setCookie('currency', $code, $lifespan);
    }

    /**
     * Returns a currency code from cookie
     * @return string
     */
    public function getFromCookie()
    {
        return (string) $this->request->cookie('currency');
    }

    /**
     * Returns a currency code from the current GET query
     * @return string
     */
    public function getFromUrl()
    {
        return (string) $this->request->get('currency');
    }

    /**
     * Returns a currency by a numeric code
     * @param integer $code
     * @return array
     */
    public function getByNumericCode($code)
    {
        $list = $this->getList();

        foreach ($list as $currency) {
            if ($currency['numeric_code'] == $code) {
                return $currency;
            }
        }

        return array();
    }

    /**
     * Returns a default currency
     * @param boolean $load
     * @return string
     */
    public function getDefault($load = false)
    {
        $currency = $this->config->get('currency', 'USD');

        if ($load) {
            $currencies = $this->getList();
            return empty($currencies[$currency]) ? array() : $currencies[$currency];
        }

        return $currency;
    }

    /**
     * Returns an array of default currencies
     * @return array
     */
    protected function getDefaultList()
    {
        return array(
            'USD' => array(
                'code' => 'USD',
                'name' => 'United States Dollars',
                'symbol' => '$',
                'status' => 1,
                'default' => 1,
                'decimals' => 2,
                'major_unit' => 'Dollar',
                'minor_unit' => 'Cent',
                'numeric_code' => 840,
                'rounding_step' => 0,
                'conversion_rate' => 1,
                'decimal_separator' => '.',
                'thousands_separator' => ',',
                'template' => '%symbol%price'
            )
        );
    }

    /**
     * Returns an array of default currency values
     * @return array
     */
    protected function defaultCurrencyValues()
    {
        return array(
            'code' => '',
            'name' => '',
            'symbol' => '',
            'status' => 0,
            'default' => 0,
            'decimals' => 2,
            'major_unit' => '',
            'minor_unit' => '',
            'numeric_code' => '',
            'rounding_step' => 0,
            'conversion_rate' => 1,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
            'template' => '%symbol%price'
        );
    }

}
