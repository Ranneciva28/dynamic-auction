<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('inspire', function () { $this->comment('Katalog lelang siap dikelola.'); });
Artisan::command('lelang:admin',function(){
    $name=$this->ask('Nama admin');
    $email=$this->ask('Email admin');
    $password=$this->secret('Kata sandi baru (minimal 12 karakter)');
    if(!filter_var($email,FILTER_VALIDATE_EMAIL)||mb_strlen((string)$password)<12){$this->error('Email tidak valid atau kata sandi kurang dari 12 karakter.');return 1;}
    \App\Models\User::updateOrCreate(['email'=>$email],['name'=>$name,'password'=>$password]);
    $this->info('Admin siap digunakan.');
})->purpose('Buat atau atur ulang akun admin secara interaktif');
