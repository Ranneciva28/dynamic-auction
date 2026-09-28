<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class TestimonialController
{
    public function edit()
    {
        $values = Setting::whereIn('key', array_map(fn (int $slot) => 'testimonial_'.$slot, range(1, 5)))
            ->pluck('value', 'key');
        $testimonials = [];
        foreach (range(1, 5) as $slot) {
            $decoded = json_decode($values['testimonial_'.$slot] ?? '{}', true);
            $testimonials[$slot] = is_array($decoded) ? $decoded : [];
        }

        return view('admin.testimonials', compact('testimonials'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'testimonials' => 'required|array|size:5',
            'testimonials.*.name' => 'nullable|string|max:100',
            'testimonials.*.rating' => 'required|integer|between:1,5',
            'testimonials.*.message' => 'nullable|string|max:1000',
            'testimonials.*.photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:4096',
            'testimonials.*.remove_photo' => 'nullable|boolean',
        ]);

        foreach (range(1, 5) as $slot) {
            $item = $request->input('testimonials.'.$slot, []);
            if (!is_array($item)) {
                throw ValidationException::withMessages(['testimonials' => 'Data testimoni tidak valid.']);
            }
            $name = trim((string) ($item['name'] ?? ''));
            $message = trim((string) ($item['message'] ?? ''));
            if (($name === '') !== ($message === '')) {
                throw ValidationException::withMessages(['testimonials.'.$slot.'.message' => "Slot $slot: isi nama dan pesan sekaligus, atau kosongkan keduanya."]);
            }
            if ($request->hasFile('testimonials.'.$slot.'.photo') && $name === '') {
                throw ValidationException::withMessages(['testimonials.'.$slot.'.name' => "Slot $slot: isi nama dan pesan sebelum mengunggah foto."]);
            }
        }

        foreach (range(1, 5) as $slot) {
            $item = $request->input('testimonials.'.$slot);
            $old = json_decode(Setting::valueOf('testimonial_'.$slot, '{}'), true);
            $oldPhoto = is_array($old) ? ($old['photo'] ?? null) : null;
            $photo = $oldPhoto;
            $remove = (bool) ($item['remove_photo'] ?? false) || trim((string) ($item['name'] ?? '')) === '';

            if ($request->hasFile('testimonials.'.$slot.'.photo')) {
                $photo = $request->file('testimonials.'.$slot.'.photo')->store('testimonials', 'public');
            } elseif ($remove) {
                $photo = null;
            }

            Setting::updateOrCreate(['key' => 'testimonial_'.$slot], ['value' => json_encode([
                'name' => trim((string) ($item['name'] ?? '')),
                'rating' => (int) ($item['rating'] ?? 5),
                'message' => trim((string) ($item['message'] ?? '')),
                'photo' => $photo,
            ], JSON_THROW_ON_ERROR)]);

            if ($oldPhoto && $oldPhoto !== $photo) {
                Storage::disk('public')->delete($oldPhoto);
            }
        }

        return back()->with('success', 'Testimoni berhasil disimpan.');
    }
}
