<?php
namespace App\Http\Controllers\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
class AuthController {
    public function form(){ return Auth::check()?redirect()->route('admin.dashboard'):view('auth.login'); }
    public function login(Request $request){
        $credentials=$request->validate(['email'=>'required|email','password'=>'required|string']);
        if(!Auth::attempt($credentials)) return back()->withErrors(['email'=>'Email atau kata sandi tidak sesuai.'])->onlyInput('email');
        $request->session()->regenerate(); return redirect()->intended(route('admin.dashboard'));
    }
    public function logout(Request $request){Auth::logout();$request->session()->invalidate();$request->session()->regenerateToken();return redirect()->route('login');}
}
