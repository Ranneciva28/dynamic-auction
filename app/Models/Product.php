<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Product extends Model {
    protected $fillable=['category_id','name','slug','description','condition_notes','price','market_price','stock','status','cover_path','sort_order'];
    protected function casts(): array { return ['price'=>'integer','market_price'=>'integer','stock'=>'integer','sort_order'=>'integer']; }
    public function category(){ return $this->belongsTo(Category::class); }
    public function images(){ return $this->hasMany(ProductImage::class)->orderBy('id'); }
    public function orders(){ return $this->hasMany(Order::class); }
    public function getRouteKeyName(){ return 'slug'; }
}
