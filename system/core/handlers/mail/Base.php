<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\handlers\mail;

use gplcart\core\Container;

/**
 * Base mail handler class
 */
class Base
{

    /**
     * Store model instance
     * @var \gplcart\core\models\Store $store
     */
    protected $store;

    /**
     * Mail model instance
     * @var \gplcart\core\models\Mail $mail
     */
    protected $mail;

    /**
     * Language model instance
     * @var \gplcart\core\models\Language $language
     */
    protected $language;

    /**
     * Config class instance
     * @var \gplcart\core\Config $config
     */
    protected $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        /* @var $config \gplcart\core\Config */
        $this->config = Container::getInstance('gplcart\\core\\Config');

        /* @var $mail \gplcart\core\models\Mail */
        $this->mail = Container::getInstance('gplcart\\core\\models\\Mail');

        /* @var $store \gplcart\core\models\Store */
        $this->store = Container::getInstance('gplcart\\core\\models\\Store');

        /* @var $language \gplcart\core\models\Language */
        $this->language = Container::getInstance('gplcart\\core\\models\\Language');
    }

    /**
     * Returns a string containing default e-mail signature
     * @param array $options Store settings
     * @return string
     */
    protected function signatureText(array $options)
    {
        $signature = array();

        if (!empty($options['owner'])) {
            $signature[] = "!owner";
        }

        if (!empty($options['address'])) {
            $signature[] = "!address";
        }

        if (!empty($options['phone'])) {
            $signature[] = "tel: !phone";
        }

        if (!empty($options['fax'])) {
            $signature[] = "fax: !fax";
        }

        if (!empty($options['email'])) {
            $signature[] = "e-mail: !store_email";
        }

        if (!empty($options['map'])) {
            $signature[] = "Find us on Google Maps: !map";
        }

        if (empty($signature)) {
            return '';
        }

        return "-------------------------------------\n" . implode("\n", $signature);
    }

    /**
     * Returns an array of placeholders for the signature
     * @param array $options
     * @return array
     */
    protected function signatureVariables(array $options)
    {
        return array(
            '!owner' => $options['owner'],
            '!phone' => implode(',', $options['phone']),
            '!store_email' => implode(',', $options['email']),
            '!fax' => implode(',', $options['fax']),
            '!address' => $options['address'],
            '!map' => empty($options['map']) ? '' : 'http://maps.google.com/?q=' . implode(',', $options['map']),
        );
    }

}
