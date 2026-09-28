<x-layout :title="$product->name">
<div class="container detail-wrap">
  <div class="breadcrumbs"><a href="{{ route('home') }}">Beranda</a><span>/</span><a href="{{ route('home',['kategori'=>$product->category?->slug]) }}">{{ $product->category?->name ?? 'Produk' }}</a><span>/</span><span>{{ $product->name }}</span></div>
  <div class="detail-grid">
    <div><div class="detail-image">@if($product->cover_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->cover_path) }}" alt="{{ $product->name }}">@else<div class="photo-empty">FOTO PRODUK</div>@endif</div>
      @if($product->images->count())<div class="thumb-grid">@foreach($product->images as $image)<a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" target="_blank" rel="noopener"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" alt="Foto tambahan {{ $product->name }}"></a>@endforeach</div>@endif
    </div>
    <div class="detail-info"><span class="category-label">{{ $product->category?->name ?? 'Lainnya' }}</span><h1>{{ $product->name }}</h1>
      @if($product->source_url)<p class="partner-note">Barang disediakan dan dikirim oleh mitra. <a href="{{ $product->source_url }}" target="_blank" rel="noopener noreferrer">Lihat sumber produk ↗</a></p>@endif
      @if($product->market_price)<p class="market-price">Estimasi harga pasar Rp {{ number_format($product->market_price,0,',','.') }}</p>@endif
      <div class="detail-price"><span>Harga tebus</span><strong>Rp {{ number_format($product->price,0,',','.') }}</strong></div>
      <div class="stock-line">{{ $product->status==='published'&&$product->stock>0?'Tersedia · stok '.$product->stock:'Tidak tersedia' }}</div>
      @if($product->description)<div class="description"><h2>Deskripsi barang</h2><p>{{ $product->description }}</p></div>@endif
      @if($product->condition_notes)<div class="description"><h2>Kondisi & kelengkapan</h2><p>{{ $product->condition_notes }}</p></div>@endif
      @if($product->status==='published'&&$product->stock>0)
        <form method="post" action="{{ route('orders.store') }}" class="order-form">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><input type="hidden" name="mode" value="single">
          <h2>Pesan barang ini</h2><p class="fine-print">Isi tujuan pengiriman sebelum membayar dengan QRIS.</p>
          <div class="form-grid"><label>Nama penerima<input name="customer_name" required maxlength="120" value="{{ old('customer_name') }}" autocomplete="name"></label><label>Nomor WhatsApp<input name="customer_phone" required maxlength="30" value="{{ old('customer_phone') }}" inputmode="tel" autocomplete="tel"></label></div>
          <label class="field-gap">Alamat lengkap<textarea name="shipping_address" rows="3" required minlength="10" maxlength="2000" placeholder="Nama jalan, nomor rumah, RT/RW, kelurahan, kecamatan, kota, provinsi, dan kode pos">{{ old('shipping_address') }}</textarea></label>
          <div class="form-grid field-gap"><label>Pengiriman<select name="shipping_method" required><option value="">Pilih layanan</option>@foreach(\App\Models\Order::SHIPPING_METHODS as $code=>$label)<option value="{{ $code }}" @selected(old('shipping_method')===$code)>{{ $label }}</option>@endforeach</select></label><label>Jumlah<input name="quantity" type="number" min="1" max="{{ min(10,$product->stock) }}" value="{{ old('quantity',1) }}" required></label></div>
          @if($errors->any())<div class="notice error" role="alert">{{ $errors->first() }}</div>@endif
          <div class="checkout-buttons"><button class="button outline" type="submit" formaction="{{ route('cart.add') }}" formnovalidate>Masukkan Keranjang</button><button class="button accent" type="submit">Beli Sekarang →</button></div>
          <p class="fine-print">Setelah Beli Sekarang, halaman pembayaran menampilkan QRIS. Biaya pengiriman dikonfirmasi pengelola setelah alamat diterima.</p>
        </form>
      @endif
    </div>
  </div>
</div>
</x-layout>
