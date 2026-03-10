<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LibraryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): void
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): void
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:libraries',
            'description' => 'nullable|string',
            'image' => 'nullable',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('libraries', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Image has already been uploaded via TUS to libraries directory
            $validated['image'] = 'libraries/' . $request->image;
        }

        // Create library logic here
        \App\Models\Library::create($validated);

        return redirect()->back()->with('success', 'Library created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id): void
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable',
        ]);

        // Get library instance
        // $library = Library::findOrFail($id);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if it exists
            // if ($library->image) {
            //     \Storage::disk('public')->delete($library->image);
            // }
            $validated['image'] = $request->file('image')->store('libraries', 'public');
        }
        // Handle image from TUS upload (just filename)
        elseif ($request->filled('image') && is_string($request->image)) {
            // Delete old image if it exists
            // if ($library->image) {
            //     \Storage::disk('public')->delete($library->image);
            // }
            // Image has already been uploaded via TUS to libraries directory
            $validated['image'] = 'libraries/' . $request->image;
        }

        // Update library logic here
        // $library->update($validated);

        return redirect()->back()->with('success', 'Library updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): void
    {
        //
    }
}
