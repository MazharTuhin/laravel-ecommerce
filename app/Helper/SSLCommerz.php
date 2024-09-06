<?php

namespace App\Helper;

use App\Models\Invoice;
use App\Models\SslcommerzAccount;
use Exception;
use Illuminate\Support\Facades\Http;

class SSLCommerz 
{
    static function InitiatePayment($Profile, $payable, $transaction_id, $user_email): array {
        try {
            $ssl = SslcommerzAccount::first();
            $response = Http::asForm()->post($ssl->init_url, [
                "store_id" => $ssl->store_id,
                "store_password" => $ssl->store_password,
                "total_amount" => $payable,
                "currency" => $ssl->currency,
                "transaction_id" => $transaction_id,
                "success_url" => "$ssl->success_url?transaction_id=$transaction_id",
                "fail_url" => "$ssl->fail_url?transaction_id=$transaction_id",
                "cancel_url" => "$ssl->cancel_url?transaction_id=$transaction_id",
                "ipn_url" => $ssl->ipn_url,

                // Customer Information
                "customer_name" => $Profile->customer_name,
                "customer_email" => $user_email,
                "customer_address1" => $Profile->customer_address,
                "customer_address2" => $Profile->customer_address,
                "customer_city" => $Profile->customer_city,
                "customer_state" => $Profile->customer_state,
                "customer_postcode" => "3900",
                "customer_country" => $Profile->customer_country,
                "customer_phone" => $Profile->customer_phone,
                "customer_fax" => $Profile->customer_fax,

                // Shipment Information
                "shipping_method" => "YES",
                "ship_name" => $Profile->ship_name,
                "ship_address1" => $Profile->ship_address,
                "ship_address2" => $Profile->ship_address,
                "ship_city" => $Profile->ship_city,
                "ship_state" => $Profile->ship_state,
                "ship_postcode" => "3900",
                "ship_country" => $Profile->ship_country,

                "product_name" => "Apple Shop Product",
                "product_category" => "Apple Shop Category",
                "product_profile" => "Apple Shop Profile",
                "product_amount" => $payable
            ]);

            return $response->json('desc');
        }
        catch(Exception $e){
            return ['error' => 'Payment initiation failed: ' . $e->getMessage()];

        }
    }

    static function InitiateSuccess($transaction_id): int {
        Invoice::where(['transaction_id' => $transaction_id, 'validation_id' => 0])->update(['payment_status' => 'success']);
        return 1;
    }

    static function InitiateFail($transaction_id): int {
        Invoice::where(['transaction_id' => $transaction_id, 'validation_id' => 0])->update(['payment_status' => 'fail']);
        return 1;
    }

    static function InitiateCancel($transaction_id): int {
        Invoice::where(['transaction_id' => $transaction_id, 'validation_id' => 0])->update(['payment_status' => 'cancel']);
        return 1;
    }

    static function InitiateIPN($transaction_id, $status, $validation_id): int {
        Invoice::where(['transaction_id' => $transaction_id, 'validation_id' => 0])->update(['payment_status' => $status, 'validation_id' => $validation_id]);
        return 1;
    }
}