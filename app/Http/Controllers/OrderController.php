<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Exceptions\Renderer\Exception;
use Illuminate\Support\Facades\Log;
use Midtrans\snap;
use Midtrans\config;


class OrderController extends Controller
{
    public function showCheckout (Order $order){
        return view('home.checkout' ,compact ('order'));
    }
    public function checkout(Request $request)
    {
        $request->request->add(['total_price'=> $request->qty * 825000,'status'=>'Unpaid']);

        // Ambil data produk berdasarkan product_id
        //$product = Product::findOrFail($request->product_id);
        $products = Product::all();
        // // Hitung total harga
        //$totalHarga = $product->price * $request->qty;

        // Simpan data pesanan ke database
        
        $order = Order::create($request->all());

        // Konfigurasi Midtrans

        Config::$serverKey = config('midtrans.serverKey');
        Config::$clientKey = config('midtrans.clientKey');
        Config::$isProduction = false;
        Config::$isSanitized = true;
        Config::$is3ds = true;

        // Parameter transaksi Midtrans
        $params = [
            'transaction_details' => [
                'order_id' => $order->id, // Gunakan ID order
                'gross_amount' => $order->total_price, // Total harga
            ],
            'customer_details' => [
                'first_name' => $order->name,
                'email' => $order->email,
                'phone' => $order->phone,
                'address' => $order->address,
            ],
        ];

        // Dapatkan Snap Token Midtrans
        $snapToken = Snap::getSnapToken($params);

        // Tampilkan halaman pembayaran
        return view('customer.midtranSnap', compact('snapToken', 'order','products'));
    }

    public function callback(Request $request){

            $serverKey = config('midtrans.serverKey');
            $hashed = hash("sha512", $request->order_id.$request->status_code.$request->gross_amount.$serverKey);
            if($hashed == $request->signature_key){
                // if($request->transaction_status == 'capture'){
                //     $order = Order::find($request->order_id);
                //     $order->update(['status'=> 'Paid']);
                // }
                if($request->transaction_status == 'capture' || $request->transaction_status == 'settlement'){
                    $order = Order::find($request->order_id);
                    $order->update(['status'=> 'Paid']);
                }
                
            }

    }
}
    


