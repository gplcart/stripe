<?php

/**
 * @package Stripe
 * @author Iurii Makukh <gplcart.software@gmail.com>
 * @copyright Copyright (c) 2017, Iurii Makukh <gplcart.software@gmail.com>
 * @license https://www.gnu.org/licenses/gpl-3.0.en.html GNU General Public License 3.0
 */

namespace gplcart\modules\stripe;

use gplcart\core\Module;

/**
 * Main class for Stripe module
 */
class Stripe extends Module
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
     * Order model instance
     * @var \gplcart\core\models\Order $order
     */
    protected $order;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns Stripe gateway object
     * @return object
     * @throws \InvalidArgumentException
     */
    protected function getGatewayInstance()
    {
        /* @var $model \gplcart\modules\omnipay_library\OmnipayLibrary */
        $model = $this->getInstance('omnipay_library');

        /* @var $instance \Omnipay\Stripe\Gateway */
        $instance = $model->getGatewayInstance('Stripe');

        if (!$instance instanceof \Omnipay\Stripe\Gateway) {
            throw new \InvalidArgumentException('Object is not instance of Omnipay\Stripe\Gateway');
        }

        return $instance;
    }

    /**
     * Implements hook "module.enable.before"
     * @param mixed $result
     */
    public function hookModuleEnableBefore(&$result)
    {
        try {
            $this->getGatewayInstance();
        } catch (\InvalidArgumentException $ex) {
            $result = $ex->getMessage();
        }
    }

    /**
     * Implements hook "module.install.before"
     * @param mixed $result
     */
    public function hookModuleInstallBefore(&$result)
    {
        try {
            $this->getGatewayInstance();
        } catch (\InvalidArgumentException $ex) {
            $result = $ex->getMessage();
        }
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
     * Implements hook "payment.methods"
     * @param array $methods
     */
    public function hookPaymentMethods(array &$methods)
    {
        $methods['stripe'] = array(
            'module' => 'stripe',
            'image' => 'image/icon.png',
            'status' => $this->getStatus(),
            'title' => 'Stripe',
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
     * @param \gplcart\core\models\Order $model
     */
    public function hookOrderAddBefore(array &$order, $model)
    {
        // Adjust order status before creation
        // We want to get payment in advance, so assign "awaiting payment" status
        if ($order['payment'] === 'stripe') {
            $order['status'] = $model->getStatusAwaitingPayment();
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
     * @param \gplcart\core\models\Order $model
     * @param \gplcart\core\controllers\frontend\Controller $controller
     */
    public function hookOrderCompletePage(array $order, $model, $controller)
    {
        if ($order['payment'] === 'stripe') {

            $this->order = $model;
            $this->data_order = $order;
            $this->controller = $controller;

            $this->submit();

            $controller->setJs('https://js.stripe.com/v2');
            $controller->setJs('system/modules/stripe/js/common.js');
            $controller->setJsSettings('stripe', array('key' => $this->getPublicKey()));
        }
    }

    /**
     * Handles submitted payment
     */
    protected function submit()
    {
        $this->data_token = $this->controller->getPosted('stripeToken', '', true, 'string');

        if (!empty($this->data_token)) {

            $params = array(
                'token' => $this->data_token,
                'currency' => $this->data_order['currency'],
                'amount' => $this->data_order['total_formatted_number']
            );

            $gateway = $this->getGatewayInstance();
            $gateway->setApiKey($this->getSecretKey());
            $this->response = $gateway->purchase($params)->send();
            $this->processResponse();
        }
    }

    /**
     * Processes gateway response
     */
    protected function processResponse()
    {
        if ($this->response->isSuccessful()) {
            $this->updateOrderStatus();
            $this->addTransaction();
            $this->redirectSuccess();
        } else if ($this->response->isRedirect()) {
            $this->response->redirect();
        } else {
            $message = $this->response->getMessage();
            $this->controller->redirect('', $message, 'warning', true);
        }
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
        $data = array('status' => $this->setting('order_status_success'));
        $this->order->update($this->data_order['order_id'], $data);
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

        /* @var $object \gplcart\core\models\Transaction */
        $object = $this->getModel('Transaction');
        return $object->add($transaction);
    }

}
