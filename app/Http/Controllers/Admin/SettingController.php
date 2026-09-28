<?php
namespace App\Http\Controllers\Admin;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
class SettingController {
    public function edit(){return view('admin.settings',['values'=>Setting::pluck('value','key')]);}
    public function update(Request $request){
        $data=$request->validate(['site_name'=>'required|string|max:100','site_intro'=>'nullable|string|max:250','contact_whatsapp'=>'nullable|regex:/^[0-9]{8,20}$/','payment_name'=>'nullable|string|max:150','payment_instructions'=>'nullable|string|max:2000','payment_qris'=>'nullable|image|mimes:jpg,jpeg,png,webp|max:4096','remove_qris'=>'nullable|boolean']);
        foreach(['site_name','site_intro','contact_whatsapp','payment_name','payment_instructions'] as $key) Setting::updateOrCreate(['key'=>$key],['value'=>$data[$key]??'']);
        $old=Setting::valueOf('payment_qris');
        if($request->boolean('remove_qris')||$request->hasFile('payment_qris')) {
            $path=$request->hasFile('payment_qris')?$request->file('payment_qris')->store('settings','public'):null;
            Setting::updateOrCreate(['key'=>'payment_qris'],['value'=>$path]);
            if($old) Storage::disk('public')->delete($old);
        }
        return back()->with('success','Pengaturan berhasil disimpan.');
    }
}
