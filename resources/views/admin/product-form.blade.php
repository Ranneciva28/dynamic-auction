<x-admin-layout :title="$product->exists ? 'Edit produk' : 'Produk baru'">
    <div class="page-header">
        <div>
            <a class="back-link" href="{{ route('admin.products.index') }}">← Kembali ke produk</a>
            <h1>{{ $product->exists ? 'Edit produk' : 'Tambah produk' }}</h1>
        </div>
    </div>

    <form class="editor-grid" method="post" action="{{ $product->exists ? route('admin.products.update', $product->id) : route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @if($product->exists) @method('PUT') @endif
        <div class="panel stack">
            <h2>Informasi barang</h2>
            <label>Nama produk<input name="name" required maxlength="180" value="{{ old('name', $product->name) }}"></label>
            <label>Kategori
                <input name="category_name" required maxlength="100" list="category-options" value="{{ old('category_name', $product->category?->name) }}" placeholder="Contoh: Handphone">
                <datalist id="category-options">
                    @foreach($categories as $category) <option value="{{ $category->name }}"> @endforeach
                </datalist>
            </label>
            <label>Deskripsi<textarea name="description" rows="7">{{ old('description', $product->description) }}</textarea></label>
            <label>Kondisi & kelengkapan<textarea name="condition_notes" rows="4">{{ old('condition_notes', $product->condition_notes) }}</textarea></label>
            <div class="form-grid">
                <label>Harga tebus (Rp)<input name="price" type="number" min="0" required value="{{ old('price', $product->price) }}"></label>
                <label>Harga pasar (opsional)<input name="market_price" type="number" min="0" value="{{ old('market_price', $product->market_price) }}"></label>
                <label>Stok<input name="stock" type="number" min="0" required value="{{ old('stock', $product->stock ?? 1) }}"></label>
                <label>Urutan tampil<input name="sort_order" type="number" value="{{ old('sort_order', $product->sort_order ?? 0) }}"></label>
            </div>
        </div>
        <div class="stack">
            <div class="panel stack">
                <h2>Foto produk</h2>
                @if($product->cover_path)
                    <img class="edit-preview" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($product->cover_path) }}" alt="Foto utama">
                    <a class="button outline" href="{{ route('admin.products.download-cover', $product->id) }}">Unduh foto utama</a>
                @endif
                <label>Ganti foto utama<input type="file" name="cover" accept="image/png,image/jpeg,image/webp"></label>
                <label>Tambah foto lain (maks. 8 per unggahan)<input type="file" name="images[]" multiple accept="image/png,image/jpeg,image/webp"></label>
                <p class="fine-print">JPG, PNG, atau WebP, maksimal 5 MB per gambar.</p>
                @if($product->exists && $product->images->count())
                    <div class="edit-thumbs">
                        @foreach($product->images as $image)
                            <div>
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($image->path) }}" alt="Foto tambahan">
                                <a class="text-button" href="{{ route('admin.images.download', $image->id) }}">Unduh</a>
                                <button type="submit" form="delete-image-{{ $image->id }}" class="text-button danger" onclick="return confirm('Hapus foto ini?')">Hapus</button>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="panel stack">
                <h2>Publikasi</h2>
                <label>Status
                    <select name="status">
                        <option value="draft" @selected(old('status', $product->status ?? 'draft') === 'draft')>Draft — tidak tampil</option>
                        <option value="published" @selected(old('status', $product->status) === 'published')>Published — tampil di katalog</option>
                        <option value="sold" @selected(old('status', $product->status) === 'sold')>Sold — tampil di Produk Sold</option>
                    </select>
                </label>
                @if($product->exists)
                    <p class="fine-print">Perubahan yang disimpan di sini tetap berlaku saat impor katalog berikutnya.</p>
                @endif
                <button class="button accent full" type="submit">Simpan produk</button>
            </div>
        </div>
    </form>

    @if($product->exists)
        @foreach($product->images as $image)
            <form id="delete-image-{{ $image->id }}" action="{{ route('admin.images.destroy', $image->id) }}" method="post">@csrf @method('DELETE')</form>
        @endforeach
        <form method="post" action="{{ route('admin.products.destroy', $product->id) }}" onsubmit="return confirm('Hapus produk ini?')" class="danger-zone">
            @csrf @method('DELETE')
            <button class="text-button danger">Hapus produk</button>
        </form>
    @endif
</x-admin-layout>
