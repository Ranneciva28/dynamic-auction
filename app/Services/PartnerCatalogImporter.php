<?php
namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class PartnerCatalogImporter
{
    private const BASE = 'https://www.pusatlelangindonesia.com';
    private const MAX_PAGES = 200;
    private const MAX_IMAGE_BYTES = 5_242_880;

    public function run(bool $publish, int $limit, bool $skipImages, bool $skipGallery, callable $report): array
    {
        $saved = 0;
        $failed = 0;
        $pages = 0;
        foreach (['available','sold'] as $feed) {
            $page = 1;
            $lastPage = 1;
            do {
                $props = $this->page(self::BASE.'/products?status='.$feed.'&page='.$page, 'Products/index');
                $listing = $props['products'] ?? [];
                $lastPage = min(self::MAX_PAGES, max(1, (int)($listing['last_page'] ?? 1)));
                $rows = $listing['data'] ?? [];
                if (!is_array($rows)) throw new RuntimeException('Daftar produk sumber tidak ditemukan.');
                $pages++;
                $report(ucfirst($feed)." $page/$lastPage: ".count($rows).' produk ditemukan.');
                foreach ($rows as $row) {
                    if ($limit > 0 && $saved + $failed >= $limit) break 3;
                    try {
                        $this->saveProduct($row, $publish, $skipImages, $skipGallery, $feed);
                        $saved++;
                    } catch (\Throwable $e) {
                        $failed++;
                        $report('Gagal '.($row['slug'] ?? 'produk tanpa slug').': '.$e->getMessage());
                    }
                    usleep(150000);
                }
                $page++;
                usleep(250000);
            } while ($page <= $lastPage);
        }
        return ['saved'=>$saved,'failed'=>$failed,'pages'=>$pages];
    }

    private function saveProduct(array $row, bool $publish, bool $skipImages, bool $skipGallery, string $feed): void
    {
        $id = filter_var($row['id'] ?? null, FILTER_VALIDATE_INT);
        $sourceSlug = (string)($row['slug'] ?? '');
        $name = trim((string)($row['title'] ?? ''));
        $categoryName = trim((string)($row['category']['name'] ?? 'Lainnya'));
        $price = filter_var($row['price'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || !preg_match('/^[a-zA-Z0-9-]{1,220}$/', $sourceSlug) || $name === '' || $price === false || $price < 0) {
            throw new RuntimeException('Identitas atau harga produk tidak valid.');
        }
        $sourceUrl = self::BASE.'/products/'.$sourceSlug;
        $product = Product::firstOrNew(['source_url'=>$sourceUrl]);
        // An admin-edited product keeps its local details, stock, and images on later imports.
        if ($product->exists && $product->catalog_locked) return;
        if (!$product->exists) $product->slug = 'mitra-'.Str::slug(Str::limit($name, 100, '')).'-'.$id;
        $category = Category::firstOrCreate(['slug'=>Str::slug($categoryName) ?: 'lainnya'], ['name'=>$categoryName]);
        $sourceStock = max(0, min(1000000, (int)($row['stock'] ?? 0)));
        $hasOrders = $product->exists && ($product->orders()->exists() || $product->orderItems()->exists());
        $product->name = Str::limit($name, 180, '');
        $product->category_id = $category->id;
        $product->description = $this->plainText((string)($row['description'] ?? ''));
        $product->price = $price;
        $product->market_price = is_numeric($row['harga_pasar'] ?? null) ? max(0, (int)$row['harga_pasar']) : null;
        // An active local order reserves units; a re-import must never replenish those units.
        $product->stock = $hasOrders ? min($product->stock, $sourceStock) : $sourceStock;
        if (!$product->exists) $product->status = 'draft';
        if ($publish) $product->status = ($feed !== 'sold' && $product->stock > 0 && ($row['is_active'] ?? true)) ? 'published' : 'sold';
        elseif ($product->exists && $product->stock === 0 && $product->status === 'published') $product->status = 'sold';
        $product->source_synced_at = now();
        if (!$skipImages) {
            $cover = $this->imagePath($row['main_image'] ?? null);
            if ($cover && (!$product->cover_path || str_starts_with($product->cover_path, 'partner/'))) {
                $product->cover_path = $this->downloadImage($cover);
            }
        }
        $product->save();
        if ($skipImages || $skipGallery) return;
        $detail = $this->page($sourceUrl, 'Products/show')['product'] ?? [];
        if (!$detail || (int)($detail['id'] ?? 0) !== $id) throw new RuntimeException('Detail produk sumber tidak sesuai. Produk utama sudah tersimpan.');
        foreach (array_slice($detail['images'] ?? [], 0, 30) as $image) {
            $path = $this->imagePath($image['image_url'] ?? null);
            if (!$path) continue;
            $stored = $this->downloadImage($path);
            if ($stored !== $product->cover_path) $product->images()->firstOrCreate(['path'=>$stored]);
        }
    }

    private function page(string $url, string $component): array
    {
        $response = Http::timeout(30)->retry(2, 1000)->withHeaders([
            'User-Agent'=>'DynamicAuctionPartnerCatalog/1.0 (catalog partner; contact site operator)',
            'Accept'=>'text/html',
        ])->get($url);
        if (!$response->successful()) throw new RuntimeException('Sumber mengembalikan HTTP '.$response->status().' pada '.$url);
        $html = $response->body();
        if (strlen($html) > 6_000_000) throw new RuntimeException('Halaman sumber terlalu besar.');
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML($html); } finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        $node = (new \DOMXPath($dom))->query('//*[@id="app"]')->item(0);
        $page = $node ? json_decode($node->getAttribute('data-page'), true, 512, JSON_THROW_ON_ERROR) : null;
        if (!is_array($page) || ($page['component'] ?? null) !== $component) {
            throw new RuntimeException('Format halaman sumber berubah pada '.$url);
        }
        return $page['props'] ?? [];
    }

    private function plainText(string $html): string
    {
        $html = preg_replace('~<br\s*/?>|</p>|</li>~i', "\n", $html);
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/u', ' ', $text);
        $text = preg_replace('/\n{3,}/u', "\n\n", $text);
        return Str::limit(trim($text), 20000, '');
    }

    private function imagePath(mixed $source): ?string
    {
        if (!is_string($source) || $source === '') return null;
        $parts = parse_url($source);
        if ($parts === false) return null;
        if (isset($parts['host']) && $parts['host'] !== 'www.pusatlelangindonesia.com') return null;
        $path = $parts['path'] ?? '';
        if (!preg_match('~^/uploads/products/[A-Za-z0-9._-]+\.(jpe?g|png|webp)$~i', $path)) return null;
        return $path;
    }

    private function downloadImage(string $path): string
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $target = 'partner/'.hash('sha256', $path).'.'.$extension;
        if (Storage::disk('public')->exists($target)) return $target;
        $response = Http::timeout(30)->retry(2, 1000)->get(self::BASE.$path);
        if (!$response->successful()) throw new RuntimeException('Foto tidak tersedia: '.$path);
        $bytes = $response->body();
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($bytes);
        if (strlen($bytes) > self::MAX_IMAGE_BYTES || !in_array($mime, ['image/jpeg','image/png','image/webp'], true)) {
            throw new RuntimeException('Foto tidak valid atau melebihi 5 MB: '.$path);
        }
        if (!Storage::disk('public')->put($target, $bytes)) {
            throw new RuntimeException('Gagal menyimpan foto di server: '.$path);
        }
        return $target;
    }
}
