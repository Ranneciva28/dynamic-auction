<?php
namespace App\Http\Controllers;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
class OrderController {
    public function store(Request $request) {
        $data=$request->validate(['product_id'=>'required|exists:products,id','customer_name'=>'required|string|max:120','customer_phone'=>'required|string|max:30','customer_email'=>'nullable|email|max:160','quantity'=>'required|integer|min:1|max:10','customer_note'=>'nullable|string|max:1000']);
        $order=DB::transaction(function() use($data) {
            $product=Product::query()->lockForUpdate()->findOrFail($data['product_id']);
            if($product->status!=='published'||$product->stock<$data['quantity']) throw ValidationException::withMessages(['quantity'=>'Stok saat ini tidak mencukupi.']);
            $product->decrement('stock',$data['quantity']);
            return Order::create([...$data,'code'=>'LD-'.now()->format('ymd').'-'.strtoupper(Str::random(6)),'public_token'=>Str::random(48),'unit_price'=>$product->price,'total'=>$product->price*$data['quantity'],'status'=>'pending']);
        });
        return redirect()->route('orders.show',[$order->code,$order->public_token]);
    }
    public function show(Order $order,string $token) {
        abort_unless(hash_equals($order->public_token,$token),404);
        $order->load('product');
        $payment=['qris'=>Setting::valueOf('payment_qris'),'name'=>Setting::valueOf('payment_name'),'instructions'=>Setting::valueOf('payment_instructions')];
        return view('orders.show',compact('order','payment'));
    }
    public function proof(Request $request,Order $order,string $token) {
        abort_unless(hash_equals($order->public_token,$token),404);
        abort_unless(in_array($order->status,['pending','review'],true),403);
        $request->validate(['proof'=>'required|image|mimes:jpg,jpeg,png,webp|max:4096']);
        $path=$request->file('proof')->store('payment-proofs','local');
        if($order->proof_path) \Illuminate\Support\Facades\Storage::disk('local')->delete($order->proof_path);
        $order->update(['proof_path'=>$path,'status'=>'review']);
        return back()->with('success','Bukti pembayaran diterima. Admin akan memverifikasinya.');
    }
}
