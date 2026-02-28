<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Event\EventProgramme;
use App\Services\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventProgrammeController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:edit events')->except(['showShared', 'downloadShared']);
    }

    /**
     * Upload or replace the event programme.
     */
    public function store(Request $request, Event $event): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                'max:51200', // 50MB
                'mimes:pdf,jpeg,jpg,png,gif,webp',
            ],
        ]);

        $file = $request->file('file');

        // Delete old programme if exists
        $oldProgramme = $event->programme;
        if ($oldProgramme) {
            Storage::disk('public')->delete($oldProgramme->file_path);
            $oldProgramme->forceDelete();
        }

        $path = $file->store('events/programmes/'.$event->id, 'public');

        $programme = EventProgramme::create([
            'event_id' => $event->id,
            'uploaded_by' => Auth::id(),
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
        ]);

        return response()->json([
            'message' => 'Programme uploadé avec succès.',
            'programme' => $programme->append(['file_url', 'file_size_for_humans', 'is_pdf', 'is_image', 'can_preview']),
        ], 201);
    }

    /**
     * Delete the event programme.
     */
    public function destroy(Event $event): JsonResponse
    {
        $programme = $event->programme;

        if (! $programme) {
            return response()->json(['message' => 'Aucun programme trouvé.'], 404);
        }

        Storage::disk('public')->delete($programme->file_path);
        $programme->delete();

        return response()->json(['message' => 'Programme supprimé avec succès.']);
    }

    /**
     * Generate a share link with QR code for the programme.
     */
    public function generateShareLink(Request $request, Event $event, QrCodeService $qrCodeService): JsonResponse
    {
        $programme = $event->programme;

        if (! $programme) {
            return response()->json(['message' => 'Aucun programme trouvé.'], 422);
        }

        $programme->generateShareToken(24);
        $programme->refresh();

        $shareUrl = route('events.programme.shared', $programme->share_token);

        try {
            $qrCode = $qrCodeService->generateBase64($shareUrl);
        } catch (\Throwable $e) {
            \Log::error('QR Code generation failed for programme share link', [
                'programme_id' => $programme->id,
                'error' => $e->getMessage(),
            ]);
            $qrCode = null;
        }

        return response()->json([
            'url' => $shareUrl,
            'token' => $programme->share_token,
            'expires_at' => $programme->share_token_expires_at->toISOString(),
            'qr_code' => $qrCode,
        ]);
    }

    /**
     * Renew the share link for another 24 hours.
     */
    public function renewShareLink(Event $event): JsonResponse
    {
        $programme = $event->programme;

        if (! $programme || ! $programme->share_token) {
            return response()->json(['message' => 'Aucun lien de partage trouvé.'], 422);
        }

        $programme->renewShareToken(24);
        $programme->refresh();

        return response()->json([
            'url' => route('events.programme.shared', $programme->share_token),
            'token' => $programme->share_token,
            'expires_at' => $programme->share_token_expires_at->toISOString(),
        ]);
    }

    /**
     * Revoke the share link.
     */
    public function revokeShareLink(Event $event): JsonResponse
    {
        $programme = $event->programme;

        if (! $programme) {
            return response()->json(['message' => 'Aucun programme trouvé.'], 422);
        }

        $programme->revokeShareToken();

        return response()->json(['message' => 'Lien de partage révoqué.']);
    }

    /**
     * Display the shared programme (public, no auth).
     */
    public function showShared(string $token): Response
    {
        $programme = EventProgramme::findByValidToken($token);

        if (! $programme) {
            return Inertia::render('Events/Programme/SharedExpired', [
                'message' => 'Ce lien de programme a expiré ou n\'est plus valide. Veuillez demander un nouveau lien à l\'organisateur.',
            ]);
        }

        $programme->load('event');

        return Inertia::render('Events/Programme/SharedView', [
            'programme' => $programme->append(['file_url', 'file_size_for_humans', 'is_pdf', 'is_image', 'can_preview']),
            'eventTitle' => $programme->event->title,
            'downloadUrl' => route('events.programme.shared.download', $token),
        ]);
    }

    /**
     * Download the shared programme file (public, no auth).
     */
    public function downloadShared(string $token): StreamedResponse|JsonResponse
    {
        $programme = EventProgramme::findByValidToken($token);

        if (! $programme) {
            return response()->json(['message' => 'Lien expiré ou invalide.'], 403);
        }

        if (! Storage::disk('public')->exists($programme->file_path)) {
            return response()->json(['message' => 'Fichier introuvable.'], 404);
        }

        return Storage::disk('public')->download($programme->file_path, $programme->file_name);
    }
}
