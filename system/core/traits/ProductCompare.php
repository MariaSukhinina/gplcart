<?php

/**
 * @package GPL Cart core
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2015, Iurii Makukh
 * @license https://www.gnu.org/licenses/gpl.html GNU/GPLv3
 */

namespace gplcart\core\traits;

/**
 * Contains controller methods for product comparison
 */
trait ProductCompare
{

    /**
     * @see \gplcart\core\Controller::isAjax()
     */
    abstract public function isAjax();

    /**
     * @see \gplcart\core\Controller::path()
     * @param null $pattern
     * @return
     */
    abstract public function path($pattern = null);

    /**
     * @see \gplcart\core\Controller::isPosted()
     * @param null $key
     * @return
     */
    abstract public function isPosted($key = null);

    /**
     * @see \gplcart\core\Controller::filterSubmitted()
     * @param array $allowed
     * @return
     */
    abstract public function filterSubmitted(array $allowed);

    /**
     * @see \gplcart\core\Controller::getSubmitted()
     * @param null $key
     * @param null $default
     * @return
     */
    abstract public function getSubmitted($key = null, $default = null);

    /**
     * @see \gplcart\core\Controller::outputJson()
     * @param $data
     * @param array $options
     * @return
     */
    abstract public function outputJson($data, array $options = array());

    /**
     * @see \gplcart\core\Controller::setSubmitted()
     * @param null $key
     * @param null $value
     * @param bool $filter
     * @return
     */
    abstract public function setSubmitted($key = null, $value = null, $filter = true);

    /**
     * @see \gplcart\core\Controller::validateComponent()
     * @param $handler_id
     * @param array $options
     * @return
     */
    abstract public function validateComponent($handler_id, array $options = array());

    /**
     * @see \gplcart\core\Controller::format()
     * @param $format
     * @param array $arguments
     * @param string $glue
     * @return
     */
    abstract public function format($format, array $arguments = array(), $glue = '<br>');

    /**
     * @see \gplcart\core\Controller::error()
     * @param null $key
     * @param null $return_error
     * @param string $return_no_error
     * @return
     */
    abstract public function error($key = null, $return_error = null, $return_no_error = '');

    /**
     * @see \gplcart\core\Controller::redirect()
     * @param string $url
     * @param string $message
     * @param string $severity
     * @param bool $exclude
     * @return
     */
    abstract public function redirect($url = '', $message = '', $severity = 'info', $exclude = false);

    /**
     * Handles adding/removing a submitted product from comparison
     * @param \gplcart\core\models\ProductCompareAction $compare_action_model
     */
    public function submitProductCompare($compare_action_model)
    {
        $this->setSubmitted('product');
        $this->filterSubmitted(array('product_id'));

        if ($this->isPosted('remove_from_compare')) {
            $this->deleteFromProductCompare($compare_action_model);
        } else if ($this->isPosted('add_to_compare')) {
            $this->validateAddProductCompare();
            $this->addToProductCompare($compare_action_model);
        }
    }

    /**
     * Validate adding a product to comparison
     */
    public function validateAddProductCompare()
    {
        $this->validateComponent('compare');
    }

    /**
     * Adds a submitted product to comparison
     * @param \gplcart\core\models\ProductCompareAction $compare_action_model
     */
    public function addToProductCompare($compare_action_model)
    {
        $errors = $this->error();

        if (empty($errors)) {
            $submitted = $this->getSubmitted();
            $result = $compare_action_model->add($submitted['product'], $submitted);
        } else {
            $result = array(
                'redirect' => '',
                'severity' => 'warning',
                'message' => $this->format($errors)
            );
        }

        if ($this->isAjax()) {
            $this->outputJson($result);
        }

        $this->redirect($result['redirect'], $result['message'], $result['severity']);
    }

    /**
     * Deletes a submitted product from comparison
     * @param \gplcart\core\models\ProductCompareAction $compare_action_model
     */
    public function deleteFromProductCompare($compare_action_model)
    {
        $product_id = $this->getSubmitted('product_id');
        $result = $compare_action_model->delete($product_id);

        if ($this->isAjax()) {
            $this->outputJson($result);
        }

        $this->controlDeleteProductCompare($result, $product_id);
        $this->redirect($result['redirect'], $result['message'], $result['severity']);
    }

    /**
     * Controls result after a product has been deleted from comparison
     * @param array $result
     * @param integer $product_id
     */
    protected function controlDeleteProductCompare(array &$result, $product_id)
    {
        if (empty($result['redirect'])) {
            $segments = explode(',', $this->path());
            if (isset($segments[0]) && $segments[0] === 'compare' && !empty($segments[1])) {
                $ids = array_filter(array_map('trim', explode(',', $segments[1])), 'ctype_digit');
                unset($ids[array_search($product_id, $ids)]);
                $result['redirect'] = $segments[0] . '/' . implode(',', $ids);
            }
        }
    }

}
