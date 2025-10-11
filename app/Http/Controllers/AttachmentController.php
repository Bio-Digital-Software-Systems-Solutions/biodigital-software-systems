<?php

namespace App\Http\Controllers;

use App\Models\Attachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AttachmentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'attachable_type' => 'required|string',
            'attachable_id' => 'required|integer',
            'file' => 'required|file|max:51200', // 50MB max
        ]);

        $file = $request->file('file');
        $mimeType = $file->getMimeType();

        // Determine file type
        $fileType = 'document';
        if (str_starts_with($mimeType, 'image/')) {
            $fileType = 'image';
        } elseif (str_starts_with($mimeType, 'video/')) {
            $fileType = 'video';
        }

        // Store file
        $fileName = Str::uuid().'.'.$file->getClientOriginalExtension();
        $filePath = $file->storeAs('attachments', $fileName, 'public');

        // Create attachment record
        $attachment = Attachment::create([
            'attachable_type' => $validated['attachable_type'],
            'attachable_id' => $validated['attachable_id'],
            'name' => $file->getClientOriginalName(),
            'file_path' => $filePath,
            'file_type' => $fileType,
            'mime_type' => $mimeType,
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
        ]);

        return back()->with('success', 'Fichier ajouté avec succès.');
    }

    public function destroy(Attachment $attachment)
    {
        // Check if user can delete
        if ($attachment->uploaded_by !== auth()->id() && ! auth()->user()->can('delete attachments')) {
            abort(403);
        }

        // Delete file from storage
        Storage::disk('public')->delete($attachment->file_path);

        // Delete record
        $attachment->delete();

        return back()->with('success', 'Fichier supprimé avec succès.');
    }

    public function download(Attachment $attachment)
    {
        if (! Storage::disk('public')->exists($attachment->file_path)) {
            abort(404, 'Fichier introuvable.');
        }

        return Storage::disk('public')->download($attachment->file_path, $attachment->name);
    }
}
