<x-admin-layout title="Pengaturan & QRIS">
<div class="page-header"><div class="eyebrow">KONFIGURASI SITUS</div><h1>Pengaturan & Payment Section</h1></div>
<form method="post" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data" class="editor-grid">
  @csrf
  @method('PUT')
  <div class="stack">
    <div class="panel stack">
      <h2>Identitas situs</h2>
      <label>Nama situs<input name="site_name" required value="{{ old('site_name',$values['site_name']??'Lelang Dinamis') }}"></label>
      <label>Kalimat pembuka katalog<textarea name="site_intro" rows="3">{{ old('site_intro',$values['site_intro']??'') }}</textarea></label>
      <label>Nomor WhatsApp pengelola (angka saja)<input name="contact_whatsapp" inputmode="numeric" value="{{ old('contact_whatsapp',$values['contact_whatsapp']??'') }}" placeholder="62812..."></label>
    </div>
    <div class="panel stack">
      <h2>Notifikasi pembelian</h2>
      <p class="fine-print">Notifikasi hanya menampilkan pesanan yang telah ditandai lunas oleh admin. Nama pelanggan tidak ditampilkan.</p>
      <input type="hidden" name="purchase_notice_enabled" value="0">
      <label class="check-line"><input type="checkbox" name="purchase_notice_enabled" value="1" @checked(old('purchase_notice_enabled',$values['purchase_notice_enabled']??'1')=='1')> Tampilkan notifikasi</label>
      <label>Muncul di halaman<select name="purchase_notice_scope" required>
        <option value="all" @selected(old('purchase_notice_scope',$values['purchase_notice_scope']??'all')==='all')>Semua halaman publik</option>
        <option value="product" @selected(old('purchase_notice_scope',$values['purchase_notice_scope']??'all')==='product')>Halaman produk</option>
        <option value="checkout" @selected(old('purchase_notice_scope',$values['purchase_notice_scope']??'all')==='checkout')>Halaman pesanan / checkout</option>
      </select></label>
      <label>Teks notifikasi<textarea name="purchase_notice_text" rows="3" required maxlength="300">{{ old('purchase_notice_text',$values['purchase_notice_text']??'Seorang pembeli telah berhasil membeli {produk} senilai {harga}.') }}</textarea></label>
      <p class="fine-print">Gunakan {produk} dan {harga} untuk menampilkan data transaksi asli. Notifikasi muncul di kiri bawah ketika ada pesanan lunas.</p>
    </div>
  </div>
  <div class="panel stack">
    <h2>Payment Section</h2>
    <p class="fine-print">Gambar QRIS tampil di halaman pesanan.</p>
    @if(!empty($values['payment_qris']))
      <div class="qris-preview"><span>QRIS aktif</span><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($values['payment_qris']) }}" alt="QRIS saat ini"></div>
      <label class="check-line"><input type="checkbox" name="remove_qris" value="1"> Hapus QRIS saat ini</label>
    @else
      <div class="notice">Belum ada QRIS terpasang. Unggah gambar untuk mengaktifkan pembayaran.</div>
    @endif
    <label>Unggah / ganti gambar QRIS<input type="file" name="payment_qris" accept="image/png,image/jpeg,image/webp"><small>PNG, JPG, atau WebP. Maksimal 4 MB.</small></label>
    <label>Nama merchant / penerima<input name="payment_name" value="{{ old('payment_name',$values['payment_name']??'') }}" placeholder="Nama yang muncul pada QRIS"></label>
    <label>Instruksi pembayaran<textarea name="payment_instructions" rows="4" placeholder="Bayar sesuai nominal, lalu unggah bukti pembayaran.">{{ old('payment_instructions',$values['payment_instructions']??'') }}</textarea></label>
    <h2>Countdown QRIS</h2>
    <label>Durasi pembayaran (menit)<input name="payment_countdown_minutes" type="number" min="1" max="1440" required value="{{ old('payment_countdown_minutes',$values['payment_countdown_minutes']??'30') }}"></label>
    <label>Teks countdown<textarea name="payment_countdown_text" rows="3" required maxlength="300">{{ old('payment_countdown_text',$values['payment_countdown_text']??'Silahkan bayar dalam waktu {time} untuk mengamankan tebus gadai ini') }}</textarea></label>
    <p class="fine-print">{time} diganti hitung mundur langsung. Pengaturan baru berlaku untuk pesanan yang dibuat setelah pengaturan disimpan.</p>
    <button class="button accent full" type="submit">Simpan pengaturan</button>
  </div>
</form>
</x-admin-layout>
