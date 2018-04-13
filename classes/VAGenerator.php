<?php namespace Octobro\Bniva\Classes;

use ApplicationException;
use RainLab\User\Models\User;
use Responsiv\Pay\Models\Invoice;
use Octobro\Bniva\Classes\BniEnc;

class VAGenerator
{
    private $clientId;

    private $secretKey;

    private $testMode = true;

    private $sandboxUrl = 'https://apibeta.bni-ecollection.com/';

    private $productionUrl = 'https://api.bni-ecollection.com/';


    public function __construct($clientId, $secretKey, $testMode = true)
    {
        $this->clientId = $clientId;
        $this->secretKey = $secretKey;
        $this->testMode = $testMode;

        return $this;
    }

    /**
     * Create lifetime virtual account by phone number
     *
     * @param $va virtual account number
     * @return JSON $response 
     */
    public function lifetimeVa(User $user)
    {
        $data = array(
            'type'             => 'createbilling',
            'client_id'        => $this->clientId,
            'trx_id'           => $user->phone,
            'trx_amount'       => 0,
            'billing_type'     => 'o',
            'customer_name'    => $user->name,
            'customer_email'   => $user->email,
            'customer_phone'   => $user->phone,
            'virtual_account'  => $this->getVa($user)
        );

        return $this->callBniVaApi($data);
    }

    public function create(Invoice $invoice, $va = null)
    {
        $data = array(
            'type'             => 'createbilling',
            'client_id'        => $this->clientId,
            'trx_id'           => $invoice->id,
            'trx_amount'       => (integer) $invoice->total,
            'billing_type'     => 'c',
            'customer_name'    => $invoice->first_name . ' ' . $invoice->last_name,
            'datetime_expired' => $invoice->due_at->format('c'),
            'customer_email'   => $invoice->email,
            'customer_phone'   => $invoice->phone
        );

        return $this->callBniVaApi($data);
    }

    protected function callBniVaApi($data)
    {
        $hashed_string = BniEnc::encrypt(
            $data,
            $this->clientId,
            $this->secretKey
        );

        $data = array(
            'client_id' => $this->clientId,
            'data'      => $hashed_string,
        );

        $response = $this->getContent($this->getApiUrl(), json_encode($data));
        $response_json = json_decode($response, true);

        if ($response_json['status'] !== '000') {
            // handling jika gagal
            throw new ApplicationException($response_json['message']);
        }
        else {
            $data_response = BniEnc::decrypt($response_json['data'], $this->clientId, $this->secretKey);

            return $data_response;
        }
    }

    protected function getApiUrl()
    {
        if ($this->testMode) {
            return $this->sandboxUrl;
        }

        return $this->productionUrl;
    }

    protected function getContent($url, $post = '') 
    {
        $usecookie = __DIR__ . "/cookie.txt";
        $header[] = 'Content-Type: application/json';
        $header[] = "Accept-Encoding: gzip, deflate";
        $header[] = "Cache-Control: max-age=0";
        $header[] = "Connection: keep-alive";
        $header[] = "Accept-Language: en-US,en;q=0.8,id;q=0.6";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_VERBOSE, false);
        // curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_ENCODING, true);
        curl_setopt($ch, CURLOPT_AUTOREFERER, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.120 Safari/537.36");

        if ($post)
        {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $rs = curl_exec($ch);

        if(empty($rs)){
            var_dump($rs, curl_error($ch));
            curl_close($ch);
            return false;
        }
        curl_close($ch);
        return $rs;
    }

    public function getExistingVa($user)
    {
        return $this->getVa($user);
    }

    protected function getVa($user)
    {
        if (strlen($this->clientId) == 5) {
            return '988' . $this->clientId . substr($user->phone, -8);
        }

        return '8' . $this->clientId . substr($user->phone, -12);
    }
}



