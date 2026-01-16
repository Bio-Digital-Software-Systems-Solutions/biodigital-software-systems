<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Get user settings from user preferences or use defaults
        $settings = [
            'email_notifications' => $user->email_notifications ?? true,
            'sms_notifications' => $user->sms_notifications ?? false,
            'push_notifications' => $user->push_notifications ?? true,
            'newsletter' => $user->newsletter ?? false,
            'event_reminders' => $user->event_reminders ?? true,
            'training_updates' => $user->training_updates ?? true,
            'message_notifications' => $user->message_notifications ?? true,
        ];

        return Inertia::render('Settings/Index', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update user settings
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
            'push_notifications' => 'boolean',
            'newsletter' => 'boolean',
            'event_reminders' => 'boolean',
            'training_updates' => 'boolean',
            'message_notifications' => 'boolean',
        ]);

        $user = $request->user();

        // Update user settings
        $user->update($validated);

        return back()->with('message', 'Settings updated successfully.');
    }

    /**
     * Display the homepage settings page for managing hero slides
     */
    public function homepage(Request $request): Response
    {
        $slides = HeroSlide::orderBy('order')->get();

        return Inertia::render('Settings/Homepage', [
            'slides' => $slides,
        ]);
    }

    /**
     * Store a new hero slide
     */
    public function storeSlide(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_type' => 'required|in:image,video',
            'media_file' => 'required_without:media_url|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,mov|max:102400',
            'media_url' => 'required_without:media_file|nullable|string',
            'cta_text' => 'nullable|string|max:255',
            'cta_link' => 'nullable|string|max:255',
            'overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle file upload
        if ($request->hasFile('media_file')) {
            $file = $request->file('media_file');
            $path = $file->store('hero-slides', 'public');
            $validated['media_url'] = Storage::url($path);
        }

        // Set order to be last
        $maxOrder = HeroSlide::max('order') ?? 0;
        $validated['order'] = $maxOrder + 1;

        // Set default values if not provided
        $validated['title'] = $validated['title'] ?? '';
        $validated['description'] = $validated['description'] ?? '';
        $validated['overlay_opacity'] = $validated['overlay_opacity'] ?? 0.5;
        $validated['is_active'] = $validated['is_active'] ?? true;

        unset($validated['media_file']);

        HeroSlide::create($validated);

        return redirect()->route('settings.homepage')
            ->with('success', 'Slide créé avec succès.');
    }

    /**
     * Update an existing hero slide
     */
    public function updateSlide(Request $request, HeroSlide $heroSlide): RedirectResponse
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'media_type' => 'required|in:image,video',
            'media_file' => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,webm,mov|max:102400',
            'media_url' => 'nullable|string',
            'cta_text' => 'nullable|string|max:255',
            'cta_link' => 'nullable|string|max:255',
            'overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        // Handle file upload
        if ($request->hasFile('media_file')) {
            // Delete old file if it was stored locally
            if ($heroSlide->media_url && str_starts_with($heroSlide->media_url, '/storage/')) {
                $oldPath = str_replace('/storage/', '', $heroSlide->media_url);
                Storage::disk('public')->delete($oldPath);
            }

            $file = $request->file('media_file');
            $path = $file->store('hero-slides', 'public');
            $validated['media_url'] = Storage::url($path);
        }

        unset($validated['media_file']);

        $heroSlide->update($validated);

        return redirect()->route('settings.homepage')
            ->with('success', 'Slide mis à jour avec succès.');
    }

    /**
     * Delete a hero slide
     */
    public function deleteSlide(HeroSlide $heroSlide): RedirectResponse
    {
        // Delete file if it was stored locally
        if ($heroSlide->media_url && str_starts_with($heroSlide->media_url, '/storage/')) {
            $oldPath = str_replace('/storage/', '', $heroSlide->media_url);
            Storage::disk('public')->delete($oldPath);
        }

        $heroSlide->delete();

        return redirect()->route('settings.homepage')
            ->with('success', 'Slide supprimé avec succès.');
    }

    /**
     * Reorder hero slides
     */
    public function reorderSlides(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slides' => 'required|array',
            'slides.*.id' => 'required|exists:hero_slides,id',
            'slides.*.order' => 'required|integer|min:0',
        ]);

        foreach ($validated['slides'] as $slideData) {
            HeroSlide::where('id', $slideData['id'])->update(['order' => $slideData['order']]);
        }

        return redirect()->route('settings.homepage')
            ->with('success', 'Ordre des slides mis à jour.');
    }
}
