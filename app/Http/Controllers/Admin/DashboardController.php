<?php
namespace App\Http\Controllers\Admin;
use App\Models\Order;
use App\Models\Product;
class DashboardController {
    public function index(){return view('admin.dashboard',['productCount'=>Product::count(),'publishedCount'=>Product::where('status','published')->count(),'pendingCount'=>Order::whereIn('status',['pending','review'])->count(),'orders'=>Order::with('product')->latest()->limit(8)->get()]);}
}
