<?php namespace Sariayu\Payment\Components;

use Db;
use Auth;
use Redirect;
use Exception;
use Carbon\Carbon;
use ApplicationException;
use Cms\Classes\ComponentBase;
use Responsiv\Pay\Models\PaymentMethod;
use Octobro\Bniva\Classes\VAGenerator;

class VirtualAccount extends ComponentBase
{
    public function componentDetails()
    {
        return [
            'name'        => 'My Virtual Account',
            'description' => 'Lifetime virtual account for user'
        ];
    }

    public function defineProperties()
    {
        return [
            'hash' => [
                'title'             => 'Invoice hash',
                'description'       => 'Invoice hash to get BNI VA detail',
                'default'           => '{{ :hash }}',
                'type'              => 'string',
            ]
        ];
    }

    public function onRun()
    {
    }

    public function isPhoneNumberValid()
    {
        return !is_null(Auth::getUser()->phone);
    }

    public function getVa()
    {
        $user = Auth::getUser();

        if ($va = $user->ob_bni_va) {
            return $va;
        }

        try {
            $payMethod = PaymentMethod::getDefault(106);
            $vaGenerator = new VAGenerator($payMethod->client_id, $payMethod->secret_key, $payMethod->test_mode);
            $va = array_get($vaGenerator->lifetimeVa($user), 'virtual_account');

            return $this->attachVaToUser($user, $va);
        } 
        catch (Exception $e) {
            if (
                str_contains($e->getMessage(), 'VA Number is in use') or
                str_contains($e->getMessage(), 'Duplicate Billing ID')
            ) {
                /**
                 * Ditahap testing kemungkinan VA sudah dibuat dan belum disimpan.
                 * Maka dari itu VA yang pernah dibuat perlu disimpan ke user terkait
                 **/
                $va = $vaGenerator->getExistingVa($user);

                return $this->attachVaToUser($user, $va);
            }

            throw new ApplicationException($e->getMessage());
        }
    }

    protected function attachVaToUser($user, $va)
    {
        $user->ob_bni_va = $va;
        $user->save();

        return $user->ob_bni_va;
    }
}
