<?php
namespace App\Http\Controllers\Admin;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class ProductController {
    public function index(Request $request){
        $query=Product::with('category');
        if($request->filled('q'))$query->where('name','like','%'.$request->input('q').'%');
        if($request->filled('status'))$query->where('status',$request->input('status'));
        return view('admin.products-index',['products'=>$query->latest()->paginate(20)->withQueryString()]);
    }
    public function create(){return view('admin.product-form',['product'=>new Product,'categories'=>Category::orderBy('name')->get()]);}
    public function edit(Product $product){return view('admin.product-form',['product'=>$product->load('images'),'categories'=>Category::orderBy('name')->get()]);}
    private function validated(Request $request,?Product $product=null):array {
        return $request->validate(['name'=>'required|string|max:180','category_name'=>'required|string|max:100','description'=>'nullable|string|max:20000','condition_notes'=>'nullable|string|max:5000','price'=>'required|integer|min:0|max:999999999999','market_price'=>'nullable|integer|min:0|max:999999999999','stock'=>'required|integer|min:0|max:1000000','status'=>['required',Rule::in(['draft','published','sold'])],'sort_order'=>'nullable|integer|min:-100000|max:100000','cover'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:5120','images'=>'nullable|array|max:8','images.*'=>'image|mimes:jpg,jpeg,png,webp|max:5120']);
    }
    private function save(Request $request,?Product $product=null):Product {
        $data=$this->validated($request,$product);
        $category=Category::firstOrCreate(['slug'=>Str::slug($data['category_name'])],['name'=>$data['category_name']]);
        unset($data['category_name'],$data['cover'],$data['images']);
        $data['category_id']=$category->id;
        if(!$product){$product=new Product;$base=Str::slug($data['name'])?:'item';$data['slug']=$base.'-'.strtolower(Str::random(6));}
        $data['sort_order']=$data['sort_order']??0;
        $old=$product->cover_path;
        if($request->hasFile('cover'))$data['cover_path']=$request->file('cover')->store('products','public');
        $product->fill($data)->save();
        if($old&&$old!==$product->cover_path)Storage::disk('public')->delete($old);
        foreach($request->file('images',[]) as $image)$product->images()->create(['path'=>$image->store('products','public')]);
        return $product;
    }
    public function store(Request $request){$this->save($request);return redirect()->route('admin.products.index')->with('success','Produk dibuat.');}
    public function update(Request $request,Product $product){$this->save($request,$product);return redirect()->route('admin.products.index')->with('success','Produk diperbarui.');}
    public function destroy(Product $product){
        if($product->orders()->exists())return back()->withErrors(['product'=>'Produk dengan riwayat pesanan tidak bisa dihapus. Ubah status menjadi draft.']);
        $paths=array_filter([$product->cover_path,...$product->images()->pluck('path')->all()]);$product->delete();Storage::disk('public')->delete($paths);
        return back()->with('success','Produk dihapus.');
    }
    public function removeImage(ProductImage $image){$path=$image->path;$image->delete();Storage::disk('public')->delete($path);return back()->with('success','Foto dihapus.');}
    public function importForm(){return view('admin.import');}
    public function import(Request $request){
        $request->validate(['csv'=>'required|file|mimes:csv,txt|max:2048']);
        $handle=fopen($request->file('csv')->getRealPath(),'rb');
        $header=fgetcsv($handle);
        if(!$header){fclose($handle);throw ValidationException::withMessages(['csv'=>'CSV kosong.']);}
        $header=array_map(fn($h)=>strtolower(trim(preg_replace('/^\xEF\xBB\xBF/','',$h))),$header);
        $required=['sku','nama','kategori','harga','stok','status'];
        if(array_diff($required,$header)){fclose($handle);throw ValidationException::withMessages(['csv'=>'Kolom wajib: '.implode(', ',$required)]);}
        $rows=[];$line=1;
        while(($values=fgetcsv($handle))!==false){
            $line++;
            if(count($values)===1&&trim($values[0])==='')continue;
            if(count($values)!==count($header)){fclose($handle);throw ValidationException::withMessages(['csv'=>"Baris $line: jumlah kolom berbeda."]);}
            $r=array_combine($header,$values);
            if(!preg_match('/^[A-Za-z0-9_-]{1,40}$/',trim($r['sku']))||trim($r['nama'])===''||trim($r['kategori'])===''||!ctype_digit(trim($r['harga']))||!ctype_digit(trim($r['stok']))||!in_array(trim($r['status']),['draft','published','sold'],true)){
                fclose($handle);throw ValidationException::withMessages(['csv'=>"Baris $line: data tidak valid."]);
            }
            $rows[]=$r;
            if(count($rows)>500){fclose($handle);throw ValidationException::withMessages(['csv'=>'Maksimal 500 baris sekali impor.']);}
        }
        fclose($handle);
        if(count(array_unique(array_column($rows,'sku')))!==count($rows))throw ValidationException::withMessages(['csv'=>'Ada SKU duplikat dalam CSV.']);
        DB::transaction(function()use($rows){foreach($rows as $r){
            $category=Category::firstOrCreate(['slug'=>Str::slug($r['kategori'])],['name'=>trim($r['kategori'])]);
            $sku=trim($r['sku']);$product=Product::firstOrNew(['slug'=>'sku-'.strtolower($sku)]);
            $product->fill(['name'=>trim($r['nama']),'category_id'=>$category->id,'price'=>(int)$r['harga'],'market_price'=>isset($r['harga_pasar'])&&ctype_digit(trim($r['harga_pasar']))?(int)$r['harga_pasar']:null,'stock'=>(int)$r['stok'],'status'=>trim($r['status']),'description'=>$r['deskripsi']??null,'condition_notes'=>$r['kondisi']??null]);
            $product->save();
        }});
        return redirect()->route('admin.products.index')->with('success',count($rows).' produk diimpor/diperbarui.');
    }
}
