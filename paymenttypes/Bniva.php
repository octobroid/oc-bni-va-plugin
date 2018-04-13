<?php namespace Octobro\Bniva\PaymentTypes;

use Input;
use Backend;
use Exception;
use ApplicationException;
use RainLab\User\Models\User;
use Sariayu\Payment\Classes\BniEnc;
use Responsiv\Pay\Classes\GatewayBase;
use Responsiv\Pay\Models\PaymentMethod;
use Octommerce\Wallet\Models\Transaction;

class Bniva extends GatewayBase
{

    /**
     * {@inheritDoc}
     */
    public function gatewayDetails()
    {
        return [
            'name'        => 'BNI Virtual Account',
            'description' => 'BNI Virtual Account'
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function defineFormFields()
    {
        return 'fields.yaml';
    }

    /**
     * {@inheritDoc}
     */
    public function defineValidationRules()
    {
        return [
            'client_id'  => ['required'],
            'secret_key' => ['required']
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function initConfigData($host)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function registerAccessPoints()
    {
        return array(
            'va_bni_notify' => 'processNotify',
        );
    }

    /**
     * Status field options.
     */
    public function getDropdownOptions()
    {
        return $this->createInvoiceStatusModel()->listStatuses();
    }

    public function processPaymentForm($data, $host, $invoice)
    {
        /*
         * We do not need any code here since payments are processed on PayPal server.
         */
    }

    public function processNotify($params)
    {
        try {
            $response = $this->getDecryptData();

            if ($this->isOpenPayment($response)) {
                Event::fire('octobro.bniva.openPaymentNotify', [$response]);

                return response()->json(['status' => '000']);
            }

            $invoice = $this->createInvoiceModel()
                ->whereTotal($response['trx_amount'])
                ->whereId($response['trx_id'])
                ->first();

            if (!$invoice) {
                throw new ApplicationException('Invoice not found');
            }

            if (!$paymentMethod = $invoice->getPaymentMethod()) {
                throw new ApplicationException('Payment method not found');
            }

            if ($paymentMethod->getGatewayClass() != 'Octobro\Bniva\PaymentTypes\Bniva') {
                throw new ApplicationException('Invalid payment method');
            }

            if ( ! $response) {
                throw new ApplicationException('waktu server tidak sesuai NTP atau secret key salah.');
            }

            if ($invoice->markAsPaymentProcessed()) {
                $invoice->logPaymentAttempt('Pembayaran melalui BNI VA', 1, [], $response, null);
                $invoice->updateInvoiceStatus($invoice->payment_method->invoice_paid_status);

                return response()->json(['status' => '000']);
            }
        }
        catch (Exception $ex)
        {
            if (isset($invoice) and $invoice) {
                $invoice->logPaymentAttempt($ex->getMessage(), 0, [], $_GET, $response);
            }

            throw new ApplicationException($ex->getMessage());
        }
    }

    protected function isOpenPayment($response)
    {
        /**
         * Open payment VA memiliki trx_amount bernilai 0
         **/
        return array_get($response, 'trx_amount') == 0;
    }

    /**
     * Decrypt data from response
     *
     * @return array
     */
    protected function getDecryptData()
    {
        return BniEnc::decrypt(
            array_get(Input::all(), 'data'),
            $this->getClientId(),
            $this->getSecretKey()
        );
    }

    protected function getClientId()
    {
        return PaymentMethod::getDefault(106)->client_id;
    }

    protected function getSecretKey()
    {
        return PaymentMethod::getDefault(106)->secret_key;
    }
}
