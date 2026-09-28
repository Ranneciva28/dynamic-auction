<?php
namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController
{
    public function store(Request $request)
    {
        $data=$request->validate([
            'mode'=>['nullable',Rule::in(['single','cart'])],
            'product_id'=>'required_unless:mode,cart|nullable|integer|exists:products,id',
            'quantity'=>'required_unless:mode,cart|nullable|integer|min:1|max:10',
            'customer_name'=>'required|string|max:120',
            'customer_phone'=>'required|string|max:30',
            'shipping_address'=>'required|string|min:10|max:2000',
            'shipping_method'=>['required',Rule::in(array_keys(Order::SHIPPING_METHODS))],
        ]);
        $fromCart=($data['mode']??'single')==='cart';
        $quantities=$fromCart?$request->session()->get('cart',[]):[(int)$data['product_id']=>(int)$data['quantity']];
        if(!is_array($quantities)||!$quantities)throw ValidationException::withMessages(['cart'=>'Keranjang kosong.']);
        $order=DB::transaction(function()use($data,$quantities){
            $items=[];$total=0;$totalQuantity=0;
            foreach($quantities as $id=>$qty){
                if(!ctype_digit((string)$id)||!is_numeric($qty)||(int)$qty<1||(int)$qty>10)throw ValidationException::withMessages(['cart'=>'Isi keranjang tidak valid.']);
            }
            $ids=array_map('intval',array_keys($quantities));sort($ids,SORT_NUMERIC);
            foreach($ids as $id){
                $qty=(int)$quantities[$id];
                $product=Product::query()->lockForUpdate()->findOrFail($id);
                if($product->status!=='published'||$product->stock<$qty)throw ValidationException::withMessages(['cart'=>$product->name.' sudah tidak cukup stok.']);
                $product->decrement('stock',$qty);
                $items[]=['product_id'=>$product->id,'product_name'=>$product->name,'quantity'=>$qty,'unit_price'=>$product->price,'subtotal'=>$product->price*$qty];
                $total+=$product->price*$qty;$totalQuantity+=$qty;
            }
            $one=count($items)===1?$items[0]:null;
            $order=Order::create([
                'code'=>'LD-'.now()->format('ymd').'-'.strtoupper(Str::random(10)),
                'public_token'=>Str::random(48),
                'product_id'=>$one['product_id']??null,
                'customer_name'=>$data['customer_name'],
                'customer_phone'=>$data['customer_phone'],
                'shipping_address'=>$data['shipping_address'],
                'shipping_method'=>$data['shipping_method'],
                'quantity'=>$totalQuantity,
                'unit_price'=>$one['unit_price']??0,
                'total'=>$total,
                'status'=>'pending',
            ]);
            foreach($items as $item)$order->items()->create($item);
            return $order;
        });
        if($fromCart)$request->session()->forget('cart');
        return redirect()->route('orders.show',['order'=>$order->code,'token'=>$order->public_token]);
    }

    public function show(Order $order,string $token)
    {
        abort_unless(hash_equals($order->public_token,$token),404);
        $order->load('product','items');
        $payment=['qris'=>Setting::valueOf('payment_qris'),'name'=>Setting::valueOf('payment_name'),'instructions'=>Setting::valueOf('payment_instructions')];
        return view('orders.show',compact('order','payment'));
    }

    public function proof(Request $request,Order $order,string $token)
    {
        abort_unless(hash_equals($order->public_token,$token),404);
        abort_unless(in_array($order->status,['pending','review'],true),403);
        $request->validate(['proof'=>'required|image|mimes:jpg,jpeg,png,webp|max:4096']);
        $path=$request->file('proof')->store('payment-proofs','local');
        if($order->proof_path)Storage::disk('local')->delete($order->proof_path);
        $order->update(['proof_path'=>$path,'status'=>'review']);
        return back()->with('success','Bukti pembayaran diterima. Admin akan memverifikasinya.');
    }
}
