<?php

namespace Antcern\Paysera;

use Hashids\Hashids;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\URL;
use WebToPay;
use WebToPayException;

class PayseraManager {

    private static $instance;

    /**
     * Create instant of self or return already created instant
     *
     * @param array|null $config
     * @return SettingsManager $settings_manager
     */
    public static function getInstance($config = null)
    {
        return static::$instance ?: (static::$instance = new self($config));
    }

    /**
     * Return available payment methods by country and payment group
     * Method parameters can be set via config
     *
     * @param string [Optional] $country
     * @param array [Optional] $payment_groups_names
     * @return array
     */
    public static function getPaymentMethods($country = null, $payment_groups_names = null){
        $payment_methods_info = WebToPay::getPaymentMethodList(intval(config('paysera.projectid')), config('paysera.currency'));
        $country_code = !is_null($country)?$country:strtolower(config('paysera.country'));
        $payment_methods_info->setDefaultLanguage(App::getLocale());

        $result = [];

        $country_payment_methods_info = $payment_methods_info->getCountry($country_code);
        $result['country_code'] = $country_payment_methods_info->getCode();
        $result['country_title'] = $country_payment_methods_info->getTitle();
        $payment_methods_groups_all = $country_payment_methods_info->getGroups();
        if($payment_groups_names == null){
            $payment_groups_names = config('paysera.payment_groups');
        }
        foreach ($payment_groups_names as $payment_groups_name){
            $payment_methods_groups[$payment_groups_name] = $payment_methods_groups_all[$payment_groups_name];
            $result['payment_groups'][$payment_groups_name]['title'] = $payment_methods_groups_all[$payment_groups_name]->getTitle(App::getLocale());
            foreach($payment_methods_groups_all[$payment_groups_name]->getPaymentMethods() as $key => $method){
                $tmp = [];
                $tmp['title'] = $method->getTitle(App::getLocale());
                $tmp['key'] = $key;
                $tmp['currency'] = $method->getBaseCurrency();
                $tmp['logo_url'] = $method->getLogoUrl();
                $tmp['object'] = $method;

                $result['payment_groups'][$payment_groups_name]['methods'][$key] = $tmp;
            }
        }
        return $result;
    }

    /**
     * Generates full request and redirects with parameters to Paysera
     * Parameter $options can override $order_id and $amount
     *
     * TODO: Handle exceptions. At the moment imagine you're doing everything perfectly
     *
     * @param integer $order_id
     * @param float $amount
     * @param array $options
     */
    public static function makePayment($order_id, $amount, $options = []){
        try {
            $payment_data = [
                'projectid'     => config('paysera.projectid'),
                'sign_password' => config('paysera.sign_password'),
                'currency'      => config('paysera.currency'),
                'country'       => config('paysera.country'),
                'accepturl'     => route(config('paysera.routes_names.accept')),
                'cancelurl'     => route(config('paysera.routes_names.cancel')),
                'callbackurl'   => route(config('paysera.routes_names.callback')),
                'test'          => config('paysera.test'),
                'version'       => '1.6',
                'orderid'       => $order_id,
                'amount'        => intval($amount*100)
            ];

            $payment_data = array_merge($payment_data, $options);

            WebToPay::redirectToPayment($payment_data, true);
        } catch (WebToPayException $e) {
            echo get_class($e) . ': ' . $e->getMessage();
        }
    }

    /**
     * Check if callback response is from Paysera and parse data to array
     *
     * @param Request $request
     * @return array
     */
    public static function parsePayment(Request $request){
        try {
            $response = WebToPay::validateAndParseData(
                $request->all(),
                intval(config('paysera.projectid')),
                config('paysera.sign_password')
            );

            return $response;
        } catch (\Exception $e) {
            echo get_class($e) . ': ' . $e->getMessage();
        }
    }

    public function encode($value){
        $hashids = new Hashids(config('paysera.projectid'), 10);

        return $hashids->encode($value);
    }

    public function decode($value){
        $hashids = new Hashids(config('paysera.projectid'), 10);
        $decoded = $hashids->decode($value);

        return isset($decoded[0])?$decoded[0]:null;
    }
}
