<?php

namespace App\Payment;

use App\Models\Cloud\License\Transaction;
use App\Events\Payment;
use mysql_xdevapi\Exception;

abstract class PaymentGateway
{
    protected $callbackNeeded = false;
    protected $refundSupported = false;
    protected $gatewayName = null;
    protected $gatewayConfig = [];

    /**
     * @param Transaction $transaction
     * @param array $meta
     * @return mixed
     */
    abstract public function pay($meta = []);

    /**
     * @param Transaction $transaction
     * @param array $meta
     * @return mixed
     */
    abstract public function paymentRequest($transaction, $meta = []);

    /**
     * PaymentGateway constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        $this->gatewayConfig = $config;
    }

    /**
     * @param Transaction $transaction
     * @param array $meta
     * @return mixed
     */
    public function getInvoiceView($transaction, $meta = [])
    {

        if (!isset($meta['success']) || !isset($meta['user'])) {
            throw new \Exception();
        }

        if ($meta['success']) {

            return view('payment.success', [
                'Customer' => $meta['user'],
                'License' => $meta['license'],
                'InvoiceId' => $transaction->InvoiceNumber,
                'InvoiceDate' => $transaction->InvoiceDate,
                'Total' => $transaction->BillTotal,
                'Unit' => $this->getConfig('UnitSymbol'),
                'Items' => [
                    [
                        'Name' => $meta['service_name'],
                        'Price' => $transaction->BillTotal
                    ]
                ]
            ]);
        } else {

            return view('payment.callback', [
                'message' => $transaction->GatewayMessage,
                'success' => false
            ]);
        }
    }

    abstract public function getGatewayIcon();

    static function create($type, $config)
    {

        switch ($type) {

            case 'braintree':
                return new BraintreeGateway($config);
            case 'zarinpal':
                return new ZarinpalGateway($config);
            case 'pep':
                return new PasargadGateway($config);
        }

        throw new \Exception(trans('errors.noValidGateway'), 422);
    }

    protected function callbackProcess()
    {
        return false;
    }

    /**
     * @param Transaction $transaction
     * @param array $meta
     * @return mixed
     */
    protected function refundProcess($transaction, $meta = [])
    {
        return false;
    }

    /**
     * @param Transaction $transaction
     * @param array $meta
     * @return mixed
     */
    public function refund($transaction, $meta = [])
    {
        if ($this->refundSupported) {
            return $this->refundProcess($transaction, $meta);
        }

        return false;
    }

    public function isCallbackNeeded()
    {
        return $this->callbackNeeded;
    }

    public function isRefundSupported()
    {
        return $this->refundSupported;
    }

    public function getGatewayName()
    {
        return $this->gatewayName;
    }

    protected function getConfig($key, $default = null)
    {
        return $this->gatewayConfig[$key] ?? $default;
    }

    protected function fireSuccessfulSaleEvent($transaction, $user)
    {
		$event = new Payment([
                    'Transaction' => $transaction->toArray(),
                    'Unit' => $this->getConfig('UnitSymbol')
                ], $user->toArray());

        event($event);
    }
}