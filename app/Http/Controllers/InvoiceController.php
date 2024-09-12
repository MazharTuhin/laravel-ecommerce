<?php

namespace App\Http\Controllers;

use App\Helper\ResponseHelper;
use App\Helper\SSLCommerz;
use App\Models\CustomerProfile;
use App\Models\Invoice;
use App\Models\InvoiceProduct;
use App\Models\ProductCart;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function InvoiceCreate(Request $request) {
        DB::beginTransaction();
        try {
            $user_id = $request->header('id');
            $user_email = $request->header('email');

            $transaction_id = uniqid();
            $delivery_status = 'pending';
            $payment_status = 'pending';

            $Profile = CustomerProfile::where('user_id', $user_id)->first();
            $customer_details = "Name: $Profile->customer_name, Address: $Profile->customer_address, City: $Profile->customer_city, Phone: $Profile->customer_phone";
            $ship_details = "Name: $Profile->ship_name, Address: $Profile->ship_address, City: $Profile->customer_city, Phone: $Profile->ship_phone";

            // Payable Calculation
            $total = 0;
            $CartList = ProductCart::where('user_id', $user_id)->get();
            foreach($CartList as $cartItem) {
                $total = $total + $cartItem->price;
            }

            $vat = ($total * 3) / 100;
            $payable = $total + $vat;

            $invoice = Invoice::create([
                'total' => $total, 
                'vat' => $vat,
                'payable' => $payable,
                'customer_details' => $customer_details,
                'ship_details' => $ship_details,
                'transaction_id' => $transaction_id,
                'delivery_status' => $delivery_status,
                'payment_status' => $payment_status,
                'user_id' => $user_id,
            ]);

            $invoiceID = $invoice->id;

            foreach($CartList as $eachProduct) {
                InvoiceProduct::create([
                    'invoice_id' => $invoiceID,
                    'product_id' => $eachProduct['product_id'],
                    'quantity' => $eachProduct['quantity'],
                    'sale_price' => $eachProduct['price'],
                    'user_id' => $user_id,
                ]);
            }

            $paymentMethod = SSLCommerz::InitiatePayment($Profile, $payable, $transaction_id, $user_email);

            DB::commit();

            return ResponseHelper::Out('success', array(['paymentMethod' => $paymentMethod, 'total' => $total, 'vat' => $vat, 'payable' => $payable]), 200);
        }
        catch(Exception $e) {
            DB::rollBack();
            return ResponseHelper::Out('failed', $e, 500);
        }
    }

    function InvoiceList(Request $request) {
        $user_id = $request->header('id');
        return Invoice::where('user_id', $user_id)->get();
    }

    function InvoiceProductList(Request $request) {
        $user_id = $request->header('id');
        $invoice_id = $request->invoice_id;
        return InvoiceProduct::where(['user_id' => $user_id, 'invoice_id' => $invoice_id])->with('product')->get();
    }

    function PaymentSuccess(Request $request) {
        return SSLCommerz::InitiateSuccess($request->query('transaction_id'));
    }

    function PaymentFailed(Request $request) {
        return SSLCommerz::InitiateFail($request->query('transaction_id'));
    }

    function PaymentCancel(Request $request) {
        return SSLCommerz::InitiateCancel($request->query('transaction_id'));
    }

    function PaymnetIPN(Request $request) {
        return SSLCommerz::InitiateIPN($request->input('transaction_id'), $request->input('status'), $request->input('validation_id'));
    }

}
