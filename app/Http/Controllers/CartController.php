<?php
namespace App\Http\Controllers;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
class CartController {
    public function index(Request $request){
        $quantities=$request->session()->get('cart',[]);
        $products=Product::whereIn('id',array_keys($quantities))->with('category')->get();
        $subtotal=$products->sum(fn($product)=>$product->price*(int)($quantities[$product->id]??0));
        return view('cart.index',compact('products','quantities','subtotal'));
    }
    public function add(Request $request){
        $data=$request->validate(['product_id'=>'required|integer|exists:products,id','quantity'=>'required|integer|min:1|max:10']);
        $product=Product::findOrFail($data['product_id']);
        $cart=$request->session()->get('cart',[]);
        $quantity=(int)($cart[$product->id]??0)+(int)$data['quantity'];
        if($product->status!=='published'||$quantity>$product->stock||$quantity>10)throw ValidationException::withMessages(['quantity'=>'Jumlah barang melebihi stok tersedia.']);
        $cart[$product->id]=$quantity;
        $request->session()->put('cart',$cart);
        return redirect()->route('cart.index')->with('success','Barang ditambahkan ke keranjang.');
    }
    public function update(Request $request,Product $product){
        $data=$request->validate(['quantity'=>'required|integer|min:1|max:10']);
        $cart=$request->session()->get('cart',[]);
        abort_unless(isset($cart[$product->id]),404);
        if($product->status!=='published'||$data['quantity']>$product->stock)throw ValidationException::withMessages(['quantity'=>'Jumlah barang melebihi stok tersedia.']);
        $cart[$product->id]=(int)$data['quantity'];
        $request->session()->put('cart',$cart);
        return back()->with('success','Jumlah barang diperbarui.');
    }
    public function remove(Request $request,Product $product){
        $cart=$request->session()->get('cart',[]);
        unset($cart[$product->id]);
        $request->session()->put('cart',$cart);
        return back()->with('success','Barang dihapus dari keranjang.');
    }
}
