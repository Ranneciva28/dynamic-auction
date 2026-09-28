<x-layout :title="'Pesanan '.$order->code">
<div class="container order-page"><a class="back-link" href="{{ route('home') }}">← Kembali ke katalog</a><div class="eyebrow">PESANAN {{ $order->code }}</div><h1>Detail pesanan</h1>
@if(session('success'))<div class="notice success">{{ session('success') }}</div>@endif
<div class="order-columns"><div class="panel"><h2>Barang dan pengiriman</h2>
@if($order->items->isNotEmpty())
  @foreach($order->items as $item)<div class="order-item"><strong>{{ $item->product_name }}</strong><span>{{ $item->quantity }} × Rp {{ number_format($item->unit_price,0,',','.') }} = Rp {{ number_format($item->subtotal,0,',','.') }}</span></div>@endforeach
@else<div class="order-item"><strong>{{ $order->product?->name ?? 'Produk tidak tersedia' }}</strong><span>{{ $order->quantity }} × Rp {{ number_format($order->unit_price,0,',','.') }}</span></div>@endif
<dl class="summary-list"><div><dt>Nama penerima</dt><dd>{{ $order->customer_name }}</dd></div><div><dt>WhatsApp</dt><dd>{{ $order->customer_phone }}</dd></div><div><dt>Alamat</dt><dd>{{ $order->shipping_address ?: 'Belum dicatat' }}</dd></div><div><dt>Pengiriman</dt><dd>{{ \App\Models\Order::SHIPPING_METHODS[$order->shipping_method] ?? 'Belum dipilih' }}</dd></div><div><dt>Subtotal barang</dt><dd><strong>Rp {{ number_format($order->total,0,',','.') }}</strong></dd></div><div><dt>Status</dt><dd><span class="badge">{{ ['pending'=>'Menunggu pembayaran','review'=>'Bukti sedang diperiksa','paid'=>'Pembayaran diterima','rejected'=>'Ditolak','cancelled'=>'Dibatalkan'][$order->status] ?? $order->status }}</span></dd></div></dl>
<p class="fine-print">Biaya pengiriman belum termasuk. Pengelola akan mengonfirmasi ongkir sesuai alamat dan layanan pilihan.</p>
@if($order->admin_note)<p class="notice">Catatan admin: {{ $order->admin_note }}</p>@endif</div>
<div class="panel payment-panel"><h2>Payment Section</h2>
@if(in_array($order->status,['pending','review']))
  @if($payment['qris'])<p>Scan QRIS dan bayar subtotal barang yang tertera. Ongkir dikonfirmasi terpisah.</p><div class="qris-frame"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment['qris']) }}" alt="Kode QRIS untuk pembayaran pesanan"></div>@if($payment['name'])<p class="merchant-name">Atas nama {{ $payment['name'] }}</p>@endif
  @else<p>QRIS belum dipasang. Hubungi pengelola untuk instruksi pembayaran.</p>@endif
  @if($payment['instructions'])<p class="payment-instructions">{{ $payment['instructions'] }}</p>@endif
  <form method="post" action="{{ route('orders.proof',[$order->code,$order->public_token]) }}" enctype="multipart/form-data" class="proof-form">@csrf<label>Unggah bukti pembayaran<input type="file" name="proof" accept="image/png,image/jpeg,image/webp" required></label>@error('proof')<div class="notice error">{{ $message }}</div>@enderror<button type="submit" class="button dark full">Kirim bukti pembayaran</button><p class="fine-print">JPG, PNG, atau WebP, maksimal 4 MB. Pembayaran diperiksa manual.</p></form>
@else<p>Status pesanan ini sudah final.</p>@endif</div></div>
<p class="fine-print order-link-note">Simpan tautan halaman ini untuk melihat status pesanan. Jangan bagikan kepada orang lain.</p></div>
</x-layout>
