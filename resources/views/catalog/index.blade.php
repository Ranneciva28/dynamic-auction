<x-layout title="Katalog">
<section class="catalog-heading">
  <div class="container heading-grid">
    <div class="heading-copy">
      <div class="eyebrow"><span class="eyebrow-line"></span> KATALOG BARANG PILIHAN</div>
      <h1>Temukan barang yang<br><em>pas buat kamu.</em></h1>
      <p>{{ \App\Models\Setting::valueOf('site_intro','Lihat detail, kondisi, dan harga setiap barang dengan jelas. Pilih barang yang kamu mau, lalu pesan secara online.') }}</p>
      <a class="button accent hero-button" href="#produk">Jelajahi produk <span aria-hidden="true">↗</span></a>
    </div>
    <div class="feature-panel">
      @if($featured?->cover_path)
        <a href="{{ route('products.show',$featured) }}" class="feature-photo"><img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($featured->cover_path) }}" alt="{{ $featured->name }}"></a>
        <div class="feature-caption"><span>PRODUK PILIHAN</span><strong>{{ $featured->name }}</strong><small>Rp {{ number_format($featured->price,0,',','.') }}</small></div>
      @else
        <div class="feature-empty"><span class="feature-monogram">L<span>.</span></span><span>BARANG PILIHAN<br>HARGA JELAS</span></div>
        <div class="feature-caption"><span>JELAJAHI KATALOG</span><strong>Temukan pilihanmu</strong><small>{{ number_format($products->total(),0,',','.') }} produk ditampilkan</small></div>
      @endif
    </div>
  </div>
</section>
@if($categories->count())
  <nav class="category-strip" aria-label="Kategori produk"><div class="container category-scroller">
    <a href="{{ route('home') }}#produk" class="category-pill {{ request('kategori')?'':'active' }}">Semua kategori</a>
    @foreach($categories as $category)<a href="{{ route('home',['kategori'=>$category->slug]) }}#produk" class="category-pill {{ request('kategori')===$category->slug?'active':'' }}">{{ $category->name }}</a>@endforeach
  </div></nav>
@endif
<div class="container catalog-body" id="produk">
  <div class="catalog-intro"><div><div class="eyebrow">{{ request('status')==='sold'?'SUDAH TERJUAL':'PILIHAN TERSEDIA' }}</div><h2>{{ request('status')==='sold'?'Produk Sold':'Jelajahi katalog' }}</h2></div><span>{{ number_format($products->total(),0,',','.') }} produk</span></div>
  <form class="catalog-tools" method="get" action="{{ route('home') }}#produk">
    <label class="search-box"><span class="sr-only">Cari produk</span><input name="q" value="{{ request('q') }}" placeholder="Cari nama barang..."></label>
    <select name="kategori" aria-label="Kategori"><option value="">Semua kategori</option>@foreach($categories as $category)<option value="{{ $category->slug }}" @selected(request('kategori')===$category->slug)>{{ $category->name }}</option>@endforeach</select>
    <select name="sort" aria-label="Urutkan produk"><option value="newest" @selected(request('sort','newest')==='newest')>Terbaru</option><option value="price_asc" @selected(request('sort')==='price_asc')>Harga terendah</option><option value="price_desc" @selected(request('sort')==='price_desc')>Harga tertinggi</option><option value="name" @selected(request('sort')==='name')>Nama A–Z</option></select>
    <button class="button dark" type="submit">Cari</button>
  </form>
  <div class="catalog-tabs"><a class="{{ request('status')==='sold'?'':'active' }}" href="{{ route('home',array_filter(request()->except('status','page'))) }}#produk">Semua</a><a class="{{ request('status')==='available'?'active':'' }}" href="{{ route('home',array_merge(request()->except('page'),['status'=>'available'])) }}#produk">Tersedia</a><a class="{{ request('status')==='sold'?'active':'' }}" href="{{ route('home',array_merge(request()->except('page'),['status'=>'sold'])) }}#produk">Terjual</a></div>
  @if($products->count())
    <div class="product-grid">@foreach($products as $product)
      <a class="product-card" href="{{ route('products.show',$product) }}"><div class="product-photo">@if($product->cover_path)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->cover_path) }}" alt="{{ $product->name }}" loading="lazy">@else<div class="photo-empty">FOTO PRODUK</div>@endif @if($product->status==='sold'||$product->stock===0)<span class="sold-tag">{{ $product->status==='sold'?'Terjual':'Stok habis' }}</span>@endif</div><div class="product-card-content"><span class="category-label">{{ $product->category?->name ?? 'Lainnya' }}</span><h3>{{ $product->name }}</h3><span class="price-label">Harga tebus</span><div class="price">Rp {{ number_format($product->price,0,',','.') }}</div><div class="card-bottom"><span>{{ $product->status==='sold'?'Terjual':'Stok: '.$product->stock }}</span><span>Lihat detail <span aria-hidden="true">↗</span></span></div></div></a>
    @endforeach</div><div class="pagination">{{ $products->links('vendor.pagination.compact') }}</div>
  @else<div class="empty-panel"><h3>Belum ada produk di sini</h3><p>Ubah pencarian atau pilih kategori lain.</p><a href="{{ route('home') }}#produk" class="button outline">Lihat semua produk</a></div>@endif
</div>
</x-layout>
