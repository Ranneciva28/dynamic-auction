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

Artisan::command('partner:import {--publish : Tampilkan produk tersedia dan tandai stok habis sebagai terjual} {--limit=0 : Batasi jumlah produk untuk uji coba} {--skip-images : Abaikan seluruh unduhan foto} {--skip-gallery : Ambil foto utama saja}',function(){
    $limit=max(0,(int)$this->option('limit'));
    $this->info('Mengambil katalog mitra. Proses bisa berjalan beberapa menit.');
    $result=app(\App\Services\PartnerCatalogImporter::class)->run(
        (bool)$this->option('publish'),$limit,(bool)$this->option('skip-images'),(bool)$this->option('skip-gallery'),
        fn(string $message)=>$this->line($message)
    );
    $this->info("Selesai: {$result['saved']} produk tersimpan, {$result['failed']} gagal, {$result['pages']} halaman dibaca.");
    return $result['failed']>0?1:0;
})->purpose('Impor katalog produk dari mitra yang diizinkan');
