<?php
namespace App\Http\Controllers\Admin;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
class SettingController {
    public function edit(){return view('admin.settings',['values'=>Setting::pluck('value','key')]);}
    public function update(Request $request){
        $data=$request->validate([
            'site_name'=>'required|string|max:100','site_intro'=>'nullable|string|max:250',
            'contact_whatsapp'=>'nullable|regex:/^[0-9]{8,20}$/',
            'payment_name'=>'nullable|string|max:150','payment_instructions'=>'nullable|string|max:2000',
            'payment_qris'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:4096','remove_qris'=>'nullable|boolean',
            'payment_countdown_minutes'=>'required|integer|min:1|max:1440',
            'payment_countdown_text'=>'required|string|max:300',
            'purchase_notice_enabled'=>'nullable|boolean',
            'purchase_notice_scope'=>['required',Rule::in(['all','product','checkout'])],
            'purchase_notice_text'=>'required|string|max:300',
        ]);
        if(!str_contains($data['payment_countdown_text'],'{time}'))throw ValidationException::withMessages(['payment_countdown_text'=>'Teks countdown harus memuat {time}.']);
        if(!str_contains($data['purchase_notice_text'],'{produk}')||!str_contains($data['purchase_notice_text'],'{harga}'))throw ValidationException::withMessages(['purchase_notice_text'=>'Teks notifikasi harus memuat {produk} dan {harga}.']);
        foreach(['site_name','site_intro','contact_whatsapp','payment_name','payment_instructions','payment_countdown_minutes','payment_countdown_text','purchase_notice_scope','purchase_notice_text'] as $key) Setting::updateOrCreate(['key'=>$key],['value'=>$data[$key]??'']);
        Setting::updateOrCreate(['key'=>'purchase_notice_enabled'],['value'=>$request->boolean('purchase_notice_enabled')?'1':'0']);
        $old=Setting::valueOf('payment_qris');
        if($request->boolean('remove_qris')||$request->hasFile('payment_qris')) {
            $path=$request->hasFile('payment_qris')?$request->file('payment_qris')->store('settings','public'):null;
            Setting::updateOrCreate(['key'=>'payment_qris'],['value'=>$path]);
            if($old) Storage::disk('public')->delete($old);
        }
        return back()->with('success','Pengaturan berhasil disimpan.');
    }
}
