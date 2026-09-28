<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class OrderItem extends Model {
    protected $fillable=['product_id','product_name','quantity','unit_price','subtotal'];
    protected function casts(): array { return ['quantity'=>'integer','unit_price'=>'integer','subtotal'=>'integer']; }
    public function order(){return $this->belongsTo(Order::class);}
    public function product(){return $this->belongsTo(Product::class);}
}
