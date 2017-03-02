<?php

/**
 * @package Stripe
 * @author Iurii Makukh <gplcart.software@gmail.com> 
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com> 
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0 
 */

namespace gplcart\modules\stripe;

use gplcart\core\Config;
use gplcart\core\models\Order as OrderModel,
    gplcart\core\models\Language as LanguageModel,
    gplcart\core\models\Transaction as TransactionModel;
use gplcart\modules\omnipay_library\OmnipayLibrary as OmnipayLibraryModule;

/**
 * Main class for Stripe module
 */
class Stripe
{

    /**
     * The current order
     * @var array
     */
    protected $data_order;

    /**
     * Stripe token
     * @var string
     */
    protected $data_token;

    /**
     * Omnipay response instance
     * @var object
     */
    protected $response;

    /**
     * Frontend controller instance
     * @var \gplcart\core\controllers\frontend\Controller $controller
     */
    protected $controller;

    /**
     * Stripe Omnipay instance
     * @var object
     */
    protected $gateway;

    /**
     * Order model instance
     * @var \gplcart\core\models\Order $order
     */
    protected $order;

    /**
     * Transaction model instance
     * @var \gplcart\core\models\Transaction $transaction
     */
    protected $transaction;

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
     * Omnipay library module instance
     * @var \gplcart\modules\omnipay_library\OmnipayLibrary
     */
    protected $omnipay_library_module;

    /**
     * Constructor
     * @param Config $config
     * @param LanguageModel $language
     * @param OrderModel $order
     * @param TransactionModel $transaction
     * @param OmnipayLibraryModule $omnipay_library_module
     */
    public function __construct(Config $config, LanguageModel $language,
            OrderModel $order, TransactionModel $transaction,
            OmnipayLibraryModule $omnipay_library_module)
    {
        $this->order = $order;
        $this->config = $config;
        $this->language = $language;
        $this->transaction = $transaction;

        $this->omnipay_library_module = $omnipay_library_module;
        $this->gateway = $this->omnipay_library_module->getGatewayInstance('Stripe');
    }

    /**
     * Module info
     * @return array
     */
    public function info()
    {
        return array(
            'core' => '1.x',
            'name' => 'Stripe',
            'version' => '1.0.0-alfa.1',
            'description' => 'Stripe payment methods. Based on Omnipay PHP payment processing library',
            'author' => 'Iurii Makukh <gplcart.software@gmail.com>',
            'license' => 'GNU General Public License 3.0',
            'dependencies' => array('omnipay_library' => '>= 1.0'),
            'configure' => 'admin/module/settings/stripe',
            'settings' => $this->getDefaultSettings()
        );
    }

    /**
     * Returns an array of default module settings
     * @return array
     */
    protected function getDefaultSettings()
    {
        return array(
            'test' => true,
            'status' => true,
            'order_status_success' => $this->order->getStatusProcessing()
        );
    }

    /**
     * Implements hook "route.list"
     * @param array $routes 
     */
    public function hookRouteList(array &$routes)
    {
        $routes['admin/module/settings/stripe'] = array(
            'access' => 'module_edit',
            'handlers' => array(
                'controller' => array('gplcart\\modules\\stripe\\controllers\\Settings', 'editSettings')
            )
        );
    }

    /**
     * Implements hook "module.enable.before"
     * @param mixed $result
     */
    public function hookModuleEnableBefore(&$result)
    {
        // Make sure that Stripe gateway is loaded by Omnipay Library module
        $error = $this->language->text('Unable to load Stripe gateway');
        $result = is_object($this->gateway) ? true : $error;
    }

    /**
     * Implements hook "payment.methods"
     * @param array $methods 
     */
    public function hookPaymentMethods(array &$methods)
    {
        $methods['stripe'] = array(
            'module' => 'stripe',
            'image' => 'image/icon.png',
            'status' => $this->getStatus(),
            'title' => $this->language->text('Stripe'),
            'template' => array('complete' => 'pay')
        );
    }

    /**
     * Returns a module setting
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    protected function setting($name, $default = null)
    {
        return $this->config->module('stripe', $name, $default);
    }

    /**
     * Returns the current status of the payment method
     */
    protected function getStatus()
    {
        if (!$this->setting('status')) {
            return false;
        }
        if ($this->setting('test')) {
            return $this->setting('test_key') && $this->setting('test_public_key');
        }
        return $this->setting('live_key') && $this->setting('live_public_key');
    }

    /**
     * Returns a public API key
     * @return string
     */
    protected function getPublicKey()
    {
        $key = $this->setting('test') ? 'test_public_key' : 'live_public_key';
        return $this->setting($key);
    }

    /**
     * Returns a secret API key
     * @return string
     */
    protected function getSecretKey()
    {
        $key = $this->setting('test') ? 'test_key' : 'live_key';
        return $this->setting($key);
    }

    /**
     * Implements hook "order.add.before"
     * @param array $order
     */
    public function hookOrderAddBefore(array &$order)
    {
        // Adjust order status before creation
        // We want to get payment in advance, so assign "awaiting payment" status
        if ($order['payment'] === 'stripe') {
            $order['status'] = $this->order->getStatusAwaitingPayment();
        }
    }

    /**
     * Implements hook "order.checkout.complete"
     * @param string $message
     * @param array $order
     */
    public function hookOrderCompleteMessage(&$message, $order)
    {
        if ($order['payment'] === 'stripe') {
            $message = ''; // Hide default message
        }
    }

    /**
     * Implements hook "order.complete.page"
     * @param array $order
     * @param \gplcart\core\controllers\frontend\Controller $controller
     * @return null
     */
    public function hookOrderCompletePage(array $order, $controller)
    {
        if ($order['payment'] !== 'stripe') {
            return null;
        }

        // Make order data and controller object accessible across all class
        $this->data_order = $order;
        $this->controller = $controller;

        $this->submit();

        // We're using Stripe.js, so add all needed javascripts
        $controller->setJs('https://js.stripe.com/v2');
        $controller->setJs('system/modules/stripe/js/common.js');

        // Pass public key to JS files
        $controller->setJsSettings('stripe', array('key' => $this->getPublicKey()));
    }

    /**
     * Handles submitted payment
     * @return null
     */
    protected function submit()
    {
        $this->data_token = $this->controller->getPosted('stripeToken');

        if (empty($this->data_token)) {
            return null;
        }

        $params = array(
            'token' => $this->data_token,
            'currency' => $this->data_order['currency'],
            'amount' => $this->data_order['total_formatted_number']
        );

        $this->gateway->setApiKey($this->getSecretKey());
        $this->response = $this->gateway->purchase($params)->send();

        return $this->processResponse();
    }

    /**
     * Processes response from Stripe gateway
     * @return boolean
     */
    protected function processResponse()
    {
        if ($this->response->isSuccessful()) {
            $this->updateOrderStatus();
            $this->addTransaction();
            $this->redirectSuccess();
            return true;
        }

        if ($this->response->isRedirect()) {
            $this->response->redirect();
            return true;
        }

        $this->redirectError();
        return false;
    }

    /**
     * Redirect on error transaction
     */
    protected function redirectError()
    {
        $this->controller->redirect('', $this->response->getMessage(), 'warning', true);
    }

    /**
     * Redirect on successful transaction
     */
    protected function redirectSuccess()
    {
        $vars = array(
            '@num' => $this->data_order['order_id'],
            '@status' => $this->order->getStatusName($this->data_order['status'])
        );

        $message = $this->controller->text('Thank you! Payment has been made. Order #@num, status: @status', $vars);
        $this->controller->redirect('/', $message, 'success', true);
    }

    /**
     * Update order status after successful transaction
     */
    protected function updateOrderStatus()
    {
        $data = array(
            'status' => $this->setting('order_status_success'));
        $this->order->update($this->data_order['order_id'], $data);

        // Load fresh data
        $this->data_order = $this->order->get($this->data_order['order_id']);
    }

    /**
     * Adds a transaction
     * @return integer
     */
    protected function addTransaction()
    {
        $transaction = array(
            'total' => $this->data_order['total'],
            'order_id' => $this->data_order['order_id'],
            'currency' => $this->data_order['currency'],
            'payment_method' => $this->data_order['payment'],
            'gateway_transaction_id' => $this->response->getTransactionReference()
        );

        return $this->transaction->add($transaction);
    }

}
