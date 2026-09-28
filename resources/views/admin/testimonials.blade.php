<x-admin-layout title="Testimoni pelanggan">
    <div class="page-header">
        <div>
            <div class="eyebrow">HOMEPAGE</div>
            <h1>Bagaimana Kata Pelanggan Kami</h1>
            <p>Kelola hingga 5 testimoni. Slot kosong tidak tampil di situs.</p>
        </div>
        <a class="button outline" href="{{ route('home') }}#testimoni" target="_blank" rel="noopener">Lihat di homepage ↗</a>
    </div>

    <form action="{{ route('admin.testimonials.update') }}" method="post" enctype="multipart/form-data" class="stack">
        @csrf
        @method('PUT')
        <div class="testimonial-editor-grid">
            @foreach(range(1,5) as $slot)
                @php($item = $testimonials[$slot] ?? [])
                <div class="panel stack">
                    <h2>Slot {{ $slot }}</h2>
                    @if(!empty($item['photo']))
                        <img class="testimonial-editor-photo" src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($item['photo']) }}" alt="Foto {{ $item['name'] ?? 'pelanggan' }}">
                        <label class="check-line"><input type="checkbox" name="testimonials[{{ $slot }}][remove_photo]" value="1" @checked(old('testimonials.'.$slot.'.remove_photo'))> Hapus foto saat ini</label>
                    @endif
                    <label>Foto pelanggan
                        <input type="file" name="testimonials[{{ $slot }}][photo]" accept="image/jpeg,image/png,image/webp">
                    </label>
                    <label>Nama
                        <input type="text" name="testimonials[{{ $slot }}][name]" maxlength="100" value="{{ old('testimonials.'.$slot.'.name', $item['name'] ?? '') }}" placeholder="Nama pelanggan">
                    </label>
                    <label>Bintang
                        <select name="testimonials[{{ $slot }}][rating]">
                            @foreach(range(1,5) as $rating)
                                <option value="{{ $rating }}" @selected((int) old('testimonials.'.$slot.'.rating', $item['rating'] ?? 5) === $rating)>{{ $rating }} bintang</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Pesan
                        <textarea name="testimonials[{{ $slot }}][message]" rows="5" maxlength="1000" placeholder="Tulis pengalaman pelanggan">{{ old('testimonials.'.$slot.'.message', $item['message'] ?? '') }}</textarea>
                    </label>
                    <p class="fine-print">JPG, PNG, atau WebP, maksimal 4 MB. Kosongkan nama dan pesan untuk menyembunyikan slot ini.</p>
                </div>
            @endforeach
        </div>
        <button class="button accent testimonial-save" type="submit">Simpan testimoni</button>
    </form>
</x-admin-layout>
