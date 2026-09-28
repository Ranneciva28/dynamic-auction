<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Order extends Model {
    public const SHIPPING_METHODS=['jne'=>'JNE','jnt'=>'J&T Express','pos'=>'Pos Indonesia','grab'=>'GrabExpress','gosend'=>'GoSend'];
    protected $fillable=['code','public_token','product_id','customer_name','customer_phone','customer_email','quantity','unit_price','total','status','proof_path','customer_note','admin_note','paid_at','shipping_address','shipping_method','payment_expires_at'];
    protected $hidden=['public_token'];
    protected function casts(): array { return ['paid_at'=>'datetime','payment_expires_at'=>'datetime','unit_price'=>'integer','total'=>'integer']; }
    public function product(){ return $this->belongsTo(Product::class); }
    public function items(){ return $this->hasMany(OrderItem::class); }
}
