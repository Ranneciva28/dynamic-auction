<?php
namespace App\Http\Controllers\Admin;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class OrderController {
    public function index(Request $request){
        $query=Order::with('product')->latest();
        if($request->filled('status'))$query->where('status',$request->input('status'));
        return view('admin.orders-index',['orders'=>$query->paginate(20)->withQueryString()]);
    }
    public function show(Order $order){return view('admin.order-show',['order'=>$order->load('product')]);}
    public function proof(Order $order){abort_unless($order->proof_path,404);return Storage::disk('local')->response($order->proof_path);}
    public function update(Request $request,Order $order){
        $data=$request->validate(['status'=>['required',Rule::in(['pending','review','paid','rejected','cancelled'])],'admin_note'=>'nullable|string|max:2000']);
        DB::transaction(function()use($order,$data){
            $order=Order::query()->lockForUpdate()->findOrFail($order->id);
            $from=$order->status;$to=$data['status'];
            if(in_array($from,['paid','rejected','cancelled'])&&$from!==$to)throw ValidationException::withMessages(['status'=>'Status akhir tidak dapat diubah.']);
            if($to==='paid'&&!$order->proof_path)throw ValidationException::withMessages(['status'=>'Minta bukti pembayaran sebelum verifikasi.']);
            if(in_array($to,['rejected','cancelled'])&&!in_array($from,['rejected','cancelled'])){
                $product=Product::query()->lockForUpdate()->find($order->product_id);
                if($product)$product->increment('stock',$order->quantity);
            }
            $order->update(['status'=>$to,'admin_note'=>$data['admin_note']??null,'paid_at'=>$to==='paid'?now():$order->paid_at]);
        });
        return back()->with('success','Status pesanan diperbarui.');
    }
}
