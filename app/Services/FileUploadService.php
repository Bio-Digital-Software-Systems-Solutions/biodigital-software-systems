<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

class FileUploadService
{
    private const MAX_IMAGE_SIZE = 102400; // 100MB in KB

    private const MAX_VIDEO_SIZE = 2097152; // 2GB in KB

    private const ALLOWED_IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    private const ALLOWED_VIDEO_MIMES = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv', 'video/x-flv', 'video/webm'];

    /**
     * Upload et sécuriser une image
     */
    public function uploadImage(UploadedFile $file, string $directory = 'images'): string
    {
        // 1. Valider le type MIME réel (pas seulement l'extension)
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, self::ALLOWED_IMAGE_MIMES)) {
            throw new \InvalidArgumentException('Type de fichier non autorisé. Types acceptés : JPEG, PNG, GIF, WebP');
        }

        // 2. Valider la taille
        if ($file->getSize() > self::MAX_IMAGE_SIZE * 1024) {
            throw new \InvalidArgumentException('Fichier trop volumineux. Taille maximale : '.self::MAX_IMAGE_SIZE.' KB');
        }

        // 3. Vérifier le contenu réel de l'image (Intervention Image 3.x)
        try {
            $manager = new ImageManager(new Driver);
            $image = $manager->read($file->getRealPath());

            // Limiter les dimensions
            if ($image->width() > 10000 || $image->height() > 10000) {
                throw new \InvalidArgumentException('Dimensions de l\'image trop grandes. Maximum : 10000x10000 pixels');
            }
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Fichier image invalide : '.$e->getMessage());
        }

        // 4. Générer un nom de fichier unique et sécurisé
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;

        // 5. Corriger l'orientation automatiquement si nécessaire (Intervention Image 3.x)
        // Note: L'orientation EXIF est automatiquement appliquée lors de la lecture

        // 6. Stocker le fichier de manière sécurisée
        $path = $directory.'/'.$filename;
        Storage::disk('public')->put($path, $image->encode());

        return $path;
    }

    /**
     * Upload et sécuriser une vidéo
     */
    public function uploadVideo(UploadedFile $file, string $directory = 'videos'): string
    {
        // 1. Valider le type MIME réel
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, self::ALLOWED_VIDEO_MIMES)) {
            throw new \InvalidArgumentException('Type de vidéo non autorisé. Types acceptés : MP4, MOV, AVI, WMV, FLV, WebM');
        }

        // 2. Valider la taille
        if ($file->getSize() > self::MAX_VIDEO_SIZE * 1024) {
            throw new \InvalidArgumentException('Vidéo trop volumineuse. Taille maximale : '.self::MAX_VIDEO_SIZE.' KB');
        }

        // 3. Générer un nom unique
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;

        // 4. Stocker de manière sécurisée
        $path = $directory.'/'.$filename;
        Storage::disk('public')->putFileAs($directory, $file, $filename);

        return $path;
    }

    /**
     * Supprimer un fichier de manière sécurisée
     */
    public function deleteFile(?string $path): bool
    {
        if (! $path) {
            return false;
        }

        // Vérifier que le chemin ne contient pas de path traversal
        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            throw new \InvalidArgumentException('Chemin de fichier invalide');
        }

        return Storage::disk('public')->delete($path);
    }
}
