<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHeroSliderRequest;
use App\Http\Requests\Admin\UpdateHeroSliderRequest;
use App\Models\HeroSlider;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class HeroSliderController extends Controller
{
    public function index(): View
    {
        $sliders = HeroSlider::orderBy('sort_order')->get();

        return view('admin.hero-sliders.index', compact('sliders'));
    }

    public function create(): View
    {
        return view('admin.hero-sliders.create');
    }

    public function store(StoreHeroSliderRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('hero-sliders', 'public');
        }

        HeroSlider::create([
            'title' => ['en' => $data['title_en'], 'am' => $data['title_am'] ?? ''],
            'subtitle' => ['en' => $data['subtitle_en'] ?? '', 'am' => $data['subtitle_am'] ?? ''],
            'button_text' => ['en' => $data['button_text_en'] ?? '', 'am' => $data['button_text_am'] ?? ''],
            'button_link' => $data['button_link'] ?? null,
            'image_path' => $imagePath,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => isset($data['is_active']),
        ]);

        return redirect()->route('admin.hero-sliders.index')
            ->with('success', __('messages.saved'));
    }

    public function edit(HeroSlider $heroSlider): View
    {
        return view('admin.hero-sliders.edit', compact('heroSlider'));
    }

    public function update(UpdateHeroSliderRequest $request, HeroSlider $heroSlider): RedirectResponse
    {
        $data = $request->validated();

        $updates = [
            'title' => ['en' => $data['title_en'], 'am' => $data['title_am'] ?? ''],
            'subtitle' => ['en' => $data['subtitle_en'] ?? '', 'am' => $data['subtitle_am'] ?? ''],
            'button_text' => ['en' => $data['button_text_en'] ?? '', 'am' => $data['button_text_am'] ?? ''],
            'button_link' => $data['button_link'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => isset($data['is_active']),
        ];

        if ($request->hasFile('image')) {
            if ($heroSlider->image_path) {
                Storage::disk('public')->delete($heroSlider->image_path);
            }
            $updates['image_path'] = $request->file('image')->store('hero-sliders', 'public');
        }

        $heroSlider->update($updates);

        return redirect()->route('admin.hero-sliders.index')
            ->with('success', __('messages.updated'));
    }

    public function destroy(HeroSlider $heroSlider): RedirectResponse
    {
        if ($heroSlider->image_path) {
            Storage::disk('public')->delete($heroSlider->image_path);
        }

        $heroSlider->delete();

        return redirect()->route('admin.hero-sliders.index')
            ->with('success', __('messages.deleted'));
    }
}
