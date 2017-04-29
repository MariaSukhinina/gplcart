<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\models;

use gplcart\core\Route,
    gplcart\core\Cache,
    gplcart\core\Model;

/**
 * Manages basic behaviors and data related to languages and their translations
 */
class Language extends Model
{

    /**
     * Array of processed translations
     * @var array
     */
    protected $processed = array();

    /**
     * Route class instance
     * @var \gplcart\core\Route $route
     */
    protected $route;

    /**
     * Current language code
     * @var string
     */
    protected $langcode = '';

    /**
     * Directory that holds main translation file for the current language
     * @var string
     */
    protected $language_directory = '';

    /**
     * Path to directory that keeps complied .csv translations
     * for the current language
     * @var string
     */
    protected $compiled_directory_csv = '';

    /**
     * Path to directory that keeps complied js translations
     * for the current language
     * @var string
     */
    protected $compiled_directory_js = '';

    /**
     * @param Route $route
     */
    public function __construct(Route $route)
    {
        parent::__construct();

        $this->route = $route;
        $this->set($this->route->getLangcode());
        $this->init();
    }

    /**
     * Set a langcode
     * @param string $langcode
     */
    public function set($langcode)
    {
        if ($this->exists($langcode)) {
            $this->langcode = $langcode;
        }
    }

    /**
     * Whether the language exists, i.e available
     * @param string $code
     * @return boolean
     */
    public function exists($code)
    {
        $languages = $this->getAll();
        return isset($languages[$code]);
    }

    /**
     * Returns an array of all languages
     * including default and added/updated languages
     * @return array
     */
    public function getAll()
    {
        $languages = &Cache::memory(__METHOD__);

        if (isset($languages)) {
            return $languages;
        }

        $default = $this->getDefault();
        $available = $this->getAvailable();
        $saved = $this->config->get('languages', array());
        $languages = gplcart_array_merge($available, $saved);

        foreach ($languages as $code => &$language) {
            $language['code'] = $code;
            $language['default'] = ($code == $default);
            $language['weight'] = isset($language['weight']) ? (int) $language['weight'] : 0;
        }

        $this->hook->fire('language.list', $languages);
        return $languages;
    }

    /**
     * Returns a default language code
     * @return string
     */
    public function getDefault()
    {
        return $this->config->get('language', '');
    }

    /**
     * Scans language folders and returns an array of available languages
     * It assumes that each language folder name matches a valid language code
     * @return array
     */
    public function getAvailable()
    {
        $languages = array();
        foreach (scandir(GC_LOCALE_DIR) as $langcode) {
            if (preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $langcode) === 1) {
                $languages[$langcode] = array(
                    'weight' => 0,
                    'status' => false,
                    'default' => false,
                    'code' => $langcode,
                    'name' => $langcode,
                    'native_name' => $langcode
                );
            }
        }

        return $languages;
    }

    /**
     * Performs some initial tasks: sets up folders, object properties etc...
     */
    protected function init()
    {
        if (empty($this->langcode)) {
            return null;
        }

        $this->language_directory = GC_LOCALE_DIR . "/{$this->langcode}";
        $this->compiled_directory_csv = "{$this->language_directory}/compiled";
        $this->compiled_directory_js = GC_LOCALE_JS_DIR . "/{$this->langcode}";

        if (!file_exists($this->compiled_directory_csv)) {
            mkdir($this->compiled_directory_csv, 0755, true);
        }

        if (!file_exists($this->compiled_directory_js)) {
            mkdir($this->compiled_directory_js, 0755, true);
        }

        return null;
    }

    /**
     * Returns a sorted array of available languages
     * @param boolean $enabled If true disabled languages will be excluded
     * @return array
     */
    public function getList($enabled = false)
    {
        $languages = $this->getAll();

        if ($enabled) {
            $languages = array_filter($languages, function ($language) {
                return !empty($language['status']);
            });
        }

        gplcart_array_sort($languages);
        return $languages;
    }

    /**
     * Returns a language
     * @param string $code
     * @return array
     */
    public function get($code)
    {
        $languages = $this->getAll();
        return isset($languages[$code]) ? $languages[$code] : array();
    }

    /**
     * Adds a language
     * @param array $data
     * @return boolean
     */
    public function add(array $data)
    {
        $this->hook->fire('language.add.before', $data);

        if (empty($data['code'])) {
            return false;
        }

        $values = array(
            'code' => $data['code'],
            'status' => !empty($data['status']),
            'default' => !empty($data['default']),
            'weight' => isset($data['weight']) ? (int) $data['weight'] : 0,
            'name' => empty($data['name']) ? $data['code'] : $data['name'],
            'native_name' => empty($data['native_name']) ? $data['code'] : $data['native_name']
        );

        $languages = $this->getAll();

        if (!empty($values['default'])) {
            $values['status'] = true;
            $this->config->set('language', $data['code']);
        }

        $languages[$data['code']] = $values;
        $this->config->set('languages', $languages);

        $this->hook->fire('language.add.after', $data);
        return true;
    }

    /**
     * Updates a language
     * @param string $code
     * @param array $data
     * @return boolean
     */
    public function update($code, array $data)
    {
        $this->hook->fire('language.update.before', $code, $data);

        $languages = $this->getAll();

        if (empty($languages[$code])) {
            return false;
        }

        if (!empty($data['default']) && !$this->isDefault($code)) {
            $data['status'] = true;
            $this->config->set('language', $code);
        }

        if ($this->isDefault($code)) {
            $data['status'] = true;
        }

        $languages[$code] = $data + $languages[$code];
        $this->config->set('languages', $languages);

        $this->hook->fire('language.update.after', $code, $data);
        return true;
    }

    /**
     * Deletes a language
     * @param string $code
     * @return boolean
     */
    public function delete($code)
    {
        $this->hook->fire('language.delete.before', $code);

        if (empty($code)) {
            return false;
        }

        $languages = $this->getAll();
        unset($languages[$code]);
        $this->config->set('languages', $languages);

        if ($this->isDefault($code)) {
            $this->config->reset('language');
        }

        $this->hook->fire('language.delete.after', $code, $languages);
        return true;
    }

    /**
     * Whether the code is default
     * @param string $code
     * @return bool
     */
    public function isDefault($code)
    {
        return $code === $this->getDefault();
    }

    /**
     * Translates a string
     * @param string $string
     * @param array $arguments
     * @param string $class
     * @return string
     */
    public function text($string, array $arguments = array(), $class = '')
    {
        if (empty($this->langcode)) {
            return $this->formatString($string, $arguments);
        }

        if (empty($class)) {
            $class = __CLASS__;
        }

        $filename = strtolower(str_replace('\\', '-', $class));
        $class_translations = $this->load($filename);

        if (isset($class_translations[$string])) {
            $this->processed[$string] = true;
            return $this->formatString($string, $arguments, $class_translations[$string]);
        }

        $all_translations = $this->load();

        if (isset($all_translations[$string])) {
            $this->addString($string, $all_translations[$string], $filename);
            $this->processed[$string] = true;
            return $this->formatString($string, $arguments, $all_translations[$string]);
        }

        $this->addString($string);
        $this->processed[$string] = true;
        return $this->formatString($string, $arguments);
    }

    /**
     * Returns translated and formated staring
     * @param string $source
     * @param array $args
     * @param array $data
     * @return string
     */
    protected function formatString($source, array $args, array $data = array())
    {
        if (!isset($data[0]) || $data[0] === '') {
            return gplcart_string_format($source, $args);
        }
        return gplcart_string_format($data[0], $args);
    }

    /**
     * Returns an array of translations from CSV files
     * @param string $filename
     * @return array
     */
    public function load($filename = '')
    {
        $cache_key = __METHOD__ . $this->langcode;

        if (!empty($filename)) {
            $cache_key .= $filename;
        }

        $translations = &Cache::memory($cache_key);

        if (isset($translations)) {
            return (array) $translations;
        }

        $file = "{$this->language_directory}/common.csv";

        if (!empty($filename)) {
            $file = "{$this->compiled_directory_csv}/$filename.csv";
        }

        if (!file_exists($file)) {
            return array();
        }

        $rows = array_map('str_getcsv', file($file));

        if (empty($rows)) {
            return array();
        }

        foreach ($rows as $row) {
            $key = array_shift($row);
            $translations[$key] = $row;
        }

        return $translations;
    }

    /**
     * Writes one line to CSV and JS translation files
     * @param string $string
     * @param array $data
     * @param string $filename
     * @return bool
     */
    protected function addString($string, $data = array(), $filename = '')
    {
        if (isset($this->processed[$string])) {
            return false;
        }

        $file = "{$this->language_directory}/common.csv";

        if (!empty($filename)) {
            $file = "{$this->compiled_directory_csv}/$filename.csv";
            $this->addStringJs($string, $data, $filename);
        }

        array_unshift($data, $string);
        return gplcart_file_csv($file, $data);
    }

    /**
     * Writes one line of JS code to JS translation file
     * @param string $string
     * @param array $data
     * @param string $filename
     * @return bool
     */
    protected function addStringJs($string, array $data, $filename)
    {
        $jsfile = "{$this->compiled_directory_js}/$filename.js";
        $json = 'GplCart.translations[' . json_encode($string) . ']=' . json_encode($data) . ';' . PHP_EOL;

        return (bool) file_put_contents($jsfile, $json, FILE_APPEND);
    }

    /**
     * Removes cached translation files
     * @param string $langcode
     */
    public function refresh($langcode)
    {
        gplcart_file_delete(GC_LOCALE_DIR . "/$langcode/compiled", array('csv'));
        gplcart_file_delete(GC_LOCALE_JS_DIR . "/$langcode", array('js'));
    }

    /**
     * Returns the current language
     * @return string
     */
    public function current()
    {
        return $this->langcode;
    }

    /**
     * Transliterates a string
     * @param string $string
     * @param string $language
     * @return string
     */
    public function translit($string, $language)
    {
        $translit = null;
        $this->hook->fire('language.translit', $string, $language, $translit);

        if (isset($translit)) {
            return $translit;
        }

        if (function_exists('transliterator_transliterate')) {
            return transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $string);
        }
        return $string;
    }

    /**
     * Returns an array of common languages with their English and native names
     * @param null|string $code
     * @return array
     */
    public function getIso($code = null)
    {
        $data = include GC_CONFIG_LANGUAGE;
        if (isset($code)) {
            return isset($data[$code]) ? (array) $data[$code] : array();
        }
        return $data;
    }

}
