<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
class CatalogController {
    public function index(Request $request) {
        $status=$request->input('status')==='sold'?'sold':'published';
        $query=Product::with('category')->where('status',$status);
        if($request->filled('q')) $query->where('name','like','%'.$request->input('q').'%');
        if($request->filled('kategori')) $query->whereHas('category',fn($q)=>$q->where('slug',$request->input('kategori')));
        if($status==='published') $query->where('stock','>',0);
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
        $values=Setting::whereIn('key',array_map(fn(int $slot)=>'testimonial_'.$slot,range(1,5)))->pluck('value','key');
        $testimonials=collect(range(1,5))->map(function(int $slot)use($values){
            $value=json_decode($values['testimonial_'.$slot]??'{}',true);
            return is_array($value)?$value:[];
        })->filter(fn(array $item)=>filled($item['name']??null)&&filled($item['message']??null));
        return view('catalog.index',compact('products','categories','featured','testimonials'));
    }
    public function show(Product $product) {
        abort_unless(in_array($product->status,['published','sold'],true),404);
        $product->load('category','images');
        return view('catalog.show',compact('product'));
    }
}
