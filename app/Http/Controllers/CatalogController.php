<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
class CatalogController {
    public function index(Request $request) {
        $query=Product::with('category')->where('status','published');
        if($request->filled('q')) $query->where('name','like','%'.$request->input('q').'%');
        if($request->filled('kategori')) $query->whereHas('category',fn($q)=>$q->where('slug',$request->input('kategori')));
        $products=$query->orderBy('sort_order')->latest()->paginate(12)->withQueryString();
        $categories=Category::orderBy('name')->get();
        return view('catalog.index',compact('products','categories'));
    }
    public function show(Product $product) {
        abort_unless($product->status==='published',404);
        $product->load('category','images');
        return view('catalog.show',compact('product'));
    }
}
