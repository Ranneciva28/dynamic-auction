<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
class CatalogController {
    public function index(Request $request) {
        $status=$request->input('status')==='sold'?'sold':'published';
        $query=Product::with('category')->where('status',$status);
        if($request->filled('q')) $query->where('name','like','%'.$request->input('q').'%');
        if($request->filled('kategori')) $query->whereHas('category',fn($q)=>$q->where('slug',$request->input('kategori')));
        if($status==='published'&&$request->input('status')==='available') $query->where('stock','>',0);
        $sort=$request->input('sort','newest');
        match($sort){
            'price_asc'=>$query->orderBy('price'),
            'price_desc'=>$query->orderByDesc('price'),
            'name'=>$query->orderBy('name'),
            default=>$query->orderBy('sort_order')->latest(),
        };
        $products=$query->paginate(12)->withQueryString();
        $categories=Category::orderBy('name')->get();
        $featured=Product::where('status','published')->where('stock','>',0)->orderBy('sort_order')->latest()->first();
        return view('catalog.index',compact('products','categories','featured'));
    }
    public function show(Product $product) {
        abort_unless(in_array($product->status,['published','sold'],true),404);
        $product->load('category','images');
        return view('catalog.show',compact('product'));
    }
}
