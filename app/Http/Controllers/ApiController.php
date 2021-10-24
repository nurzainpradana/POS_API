<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;


use App\Models\Product;
use App\Models\Customer;
use App\Models\LogStockProduct;
use App\Models\User;
use App\Models\Transaction;
use App\Models\TransactionDetail;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ApiController extends Controller {

    protected $isSuccess    = false;
    protected $message      = "";
    protected $data         = null;
    protected $error        = null;

    public function getDataProduct(Request $request){

        if($request->id)
            $data   = Product::where('id', $request->id)->get();
        else
            $data   = Product::get();

        if($data->isEmpty()){
            $this->isSuccess    = false;
            $this->message      = "Data tidak ditemukan";
            $this->data         = null;

            return $this->commit_response();
        }

        $this->isSuccess    = true;
        $this->message      = "Data Berhasil Didapatkan";
        $this->data         = $data;

        return $this->commit_response();
    }

    public function getDataCustomer(Request $request){

        if($request->id)
            $data   = Customer::where('id', $request->id)->get();
        else
            $data   = Customer::get();

        if($data->isEmpty()){
            $this->isSuccess    = false;
            $this->message      = "Data tidak ditemukan";
            $this->data         = null;

            return $this->commit_response();
        }

        $this->isSuccess    = true;
        $this->message      = "Data Berhasil Didapatkan";
        $this->data         = $data;

        return $this->commit_response();
    } 

    public function postTransaction(Request $request){
        $validation     = Validator::make($request->all(),  [
            'id_user'           => ['required'],
            'id_customer'       => ['required'],
            'id_product'        => ['required'],
            'qty_product'       => ['required'],   
            'payment_method'    => ['required']   
        ]);

        if($validation->fails()) {
            $this->message  = "Something wrong";
            $this->error   = $validation->errors();

            return $this->commit_response();
        }

        $customer       = Customer::where('id', $request->id_customer)->first();

        if(!$customer) {
            $this->message  = "Customer tidak ditemukan";
            
            return $this->commit_response();
        }

        $user           = User::where('id', $request->id_user)->first();

        if(!$user) {
            $this->message  = "User tidak ditemukan";
            
            return $this->commit_response();
        }

        $transaction    = new Transaction();

        
        $transaction->id_user           = $request->id_user;
        $transaction->id_customer       = $request->id_customer;
        $transaction->code              = $this->generateRandomString(5);
        $transaction->note              = $request->note;
        $transaction->payment_method    = $request->payment_method;

        $transaction->save();

        // SAVE DETAIL
        $id_product     = $request->id_product;
        $qty_product    = $request->qty_product;

        $total_price    = 0;

        foreach($id_product as $i => $v) {
            $product        = Product::where('id', $id_product[$i])->first();
        

            if(!$product){
                $this->message  = "Product with id $id_product[$i] Not Found";
                return $this->commit_response();
            } else {
                $product_stock  = 0;
                $cek_stock      = $this->checkStockProduct($product->id);

                if($cek_stock) $product_stock = $cek_stock;

                if($product_stock < $qty_product[$i]) {
                    $this->message  = "Stock product $product->product tidak cukup";
                    return $this->commit_response();
                }
            }

            $detail                     = new TransactionDetail();

            $detail->id_transaction     = $transaction->id;
            $detail->id_product         = $id_product[$i];
            $detail->product            = $product->product;
            $detail->price              = $product->price;
            $detail->qty                = $qty_product[$i];
            $detail->total              = ((int) $product->price * $qty_product[$i]);
            $detail->save();

            $total_price += $detail->total;

            $product_stock  = 0;
            $cek_stock      = $this->checkStockProduct($product->id);

            if($cek_stock) $product_stock = $cek_stock;

            $update_stock               = new LogStockProduct();
            $update_stock->id_product   = $id_product[$i];
            $update_stock->status       = "OUT";
            $update_stock->qty          = $qty_product[$i];
            $update_stock->stock_before = $product_stock;
            $update_stock->stock_after  = ((int) $product_stock - $qty_product[$i]);

            $update_stock->save();
        }

        $transaction        = Transaction::where('id', $transaction->id)->first();

        if($transaction){
            $transaction->total_price   = $total_price;

            $transaction->update( );

            if($transaction){
                $this->isSuccess    = true;
                $this->message      = "Success Transaction";
                return $this->commit_response();
            }
            
        }

        
    }

    public function getTransaction(Request $request){

        if($request->id)
            $data   = Transaction::with('detail')->where('id', $request->id)->get();
        else
            $data   = Transaction::with('detail')->get();

        if($data->isEmpty()){
            $this->isSuccess    = false;
            $this->message      = "Data tidak ditemukan";
            $this->data         = null;

            return $this->commit_response();
        }

        $this->isSuccess    = true;
        $this->message      = "Data Berhasil Didapatkan";
        $this->data         = $data;

        return $this->commit_response();
    }

    public function checkStockProduct($id_product){
        $cek_stock  = LogStockProduct::where('id_product', $id_product)->get();

        $stock  = 0;

        if(!$cek_stock->isEmpty()){
            foreach($cek_stock as $c) {
                if($c->status == "IN"){
                    $stock += $c->qty;
                } else {
                    $stock -= $c->qty;
                }
            }
        }

        return $stock;
    }

    public function generateRandomString($length    = 10)
    {
        $characters         = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength   = strlen($characters);
        $randString         = '';
        for ($i = 0; $i < $length; $i++) {
            $randString .= $characters[rand(0, $charactersLength -1)];
        } 
        return $randString;
    }

    public function commit_response()
    {
        $response   = [
            'isSuccess' => $this->isSuccess,
            'message'   => $this->message,
            'data'      => $this->data,
            'errors'    => $this->error
        ];

        return response()->json($response);
    }
}