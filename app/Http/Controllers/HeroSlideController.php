<?php

namespace App\Http\Controllers;

use App\Models\HeroSlide;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HeroSlideController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth')->except(['index', 'show']);
        $this->middleware('can:manage hero slides')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $slides = HeroSlide::orderBy('order')->get();

        return Inertia::render('HeroSlides/Index', [
            'slides' => $slides,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('HeroSlides/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:image,video',
            'media_url' => 'required|string',
            'cta_text' => 'nullable|string|max:255',
            'cta_link' => 'nullable|string|max:255',
            'overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        HeroSlide::create($validated);

        return redirect()->route('hero-slides.index')
            ->with('success', 'Slide créé avec succès.');
    }

    /**
     * Display the specified resource.
     */
    public function show(HeroSlide $heroSlide)
    {
        return Inertia::render('HeroSlides/Show', [
            'slide' => $heroSlide,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(HeroSlide $heroSlide)
    {
        return Inertia::render('HeroSlides/Edit', [
            'slide' => $heroSlide,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, HeroSlide $heroSlide)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'media_type' => 'required|in:image,video',
            'media_url' => 'required|string',
            'cta_text' => 'nullable|string|max:255',
            'cta_link' => 'nullable|string|max:255',
            'overlay_opacity' => 'nullable|numeric|min:0|max:1',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean',
        ]);

        $heroSlide->update($validated);

        return redirect()->route('hero-slides.index')
            ->with('success', 'Slide mis à jour avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HeroSlide $heroSlide)
    {
        $heroSlide->delete();

        return redirect()->route('hero-slides.index')
            ->with('success', 'Slide supprimé avec succès.');
    }

    /**
     * Get active slides for public display
     */
    public function activeSlides()
    {
        $slides = HeroSlide::active()->get();

        return response()->json($slides);
    }
}
