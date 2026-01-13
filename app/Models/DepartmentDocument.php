<?php

namespace App\Models;

use App\Traits\ClearsCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * @property int $id
 * @property string $uuid
 * @property int $department_id
 * @property int $uploaded_by
 * @property string $original_name
 * @property string $file_name
 * @property string $file_path
 * @property string $mime_type
 * @property int $file_size
 * @property string $extension
 * @property int $year
 * @property int $month
 * @property string|null $title
 * @property string|null $description
 * @property string|null $category
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Department $department
 * @property-read \App\Models\User $uploader
 */
class DepartmentDocument extends Model
{
    use HasFactory, LogsActivity, ClearsCache, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'uuid',
        'department_id',
        'uploaded_by',
        'original_name',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'extension',
        'year',
        'month',
        'title',
        'description',
        'category',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'year' => 'integer',
            'month' => 'integer',
        ];
    }

    /**
     * Configure activity log options.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }

            // Auto-set year and month from current date if not set
            if (empty($model->year)) {
                $model->year = now()->year;
            }
            if (empty($model->month)) {
                $model->month = now()->month;
            }
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * Get the department that owns the document.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the user who uploaded the document.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the file URL (direct access via storage).
     */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Get the preview URL (secure API endpoint).
     */
    public function getPreviewUrlAttribute(): string
    {
        return url("/api/departments/{$this->department->uuid}/documents/{$this->uuid}/preview");
    }

    /**
     * Get the formatted file size.
     */
    public function getFormattedFileSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Get month name.
     */
    public function getMonthNameAttribute(): string
    {
        $months = [
            1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
            5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
            9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
        ];

        return $months[$this->month] ?? '';
    }

    /**
     * Get the file type category based on extension.
     */
    public function getFileTypeAttribute(): string
    {
        $extension = strtolower($this->extension);

        $types = [
            'pdf' => 'pdf',
            'doc' => 'word',
            'docx' => 'word',
            'xls' => 'excel',
            'xlsx' => 'excel',
            'ppt' => 'powerpoint',
            'pptx' => 'powerpoint',
            'jpg' => 'image',
            'jpeg' => 'image',
            'png' => 'image',
            'gif' => 'image',
            'webp' => 'image',
            'svg' => 'image',
            'bmp' => 'image',
            'txt' => 'text',
            'csv' => 'excel',
            'zip' => 'archive',
            'rar' => 'archive',
            '7z' => 'archive',
            // Video
            'mp4' => 'video',
            'webm' => 'video',
            'mov' => 'video',
            'avi' => 'video',
            'mkv' => 'video',
            // Audio
            'mp3' => 'audio',
            'wav' => 'audio',
            'ogg' => 'audio',
            'm4a' => 'audio',
        ];

        return $types[$extension] ?? 'other';
    }

    /**
     * Check if the document can be previewed.
     */
    public function getCanPreviewAttribute(): bool
    {
        $previewableTypes = ['pdf', 'image', 'video', 'audio', 'text'];
        return in_array($this->file_type, $previewableTypes);
    }

    /**
     * Get the preview type for determining how to render the preview.
     */
    public function getPreviewTypeAttribute(): string
    {
        $fileType = $this->file_type;

        // Map file types to preview types
        $previewTypes = [
            'pdf' => 'pdf',
            'image' => 'image',
            'video' => 'video',
            'audio' => 'audio',
            'text' => 'text',
            'word' => 'office',
            'excel' => 'office',
            'powerpoint' => 'office',
        ];

        return $previewTypes[$fileType] ?? 'none';
    }

    /**
     * Scope to filter by year.
     */
    public function scopeByYear($query, int $year)
    {
        return $query->where('year', $year);
    }

    /**
     * Scope to filter by month.
     */
    public function scopeByMonth($query, int $year, int $month)
    {
        return $query->where('year', $year)->where('month', $month);
    }

    /**
     * Get documents organized as a tree structure with category subfolders.
     * Automatically ensures the 'rapports' category exists for the current month.
     */
    public static function getTreeForDepartment(int $departmentId): array
    {
        // Ensure rapports category exists for current month
        $currentYear = now()->year;
        $currentMonth = now()->month;
        DepartmentDocumentCategory::ensureRapportsCategory($departmentId, $currentYear, $currentMonth);

        $documents = self::where('department_id', $departmentId)
            ->with(['department', 'uploader:id,first_name,last_name'])
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Get all categories from database
        $allCategories = DepartmentDocumentCategory::where('department_id', $departmentId)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get()
            ->groupBy(fn($cat) => "{$cat->year}-{$cat->month}");

        $tree = [];

        // First, create structure from categories (ensures empty categories show)
        foreach ($allCategories as $periodKey => $categories) {
            [$year, $month] = explode('-', $periodKey);
            $year = (int) $year;
            $month = (int) $month;

            if (!isset($tree[$year])) {
                $tree[$year] = [
                    'year' => $year,
                    'months' => [],
                    'document_count' => 0,
                ];
            }

            if (!isset($tree[$year]['months'][$month])) {
                $monthNames = [
                    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
                    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
                    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre',
                ];

                $tree[$year]['months'][$month] = [
                    'month' => $month,
                    'month_name' => $monthNames[$month] ?? '',
                    'categories' => [],
                    'document_count' => 0,
                ];
            }

            foreach ($categories as $category) {
                $tree[$year]['months'][$month]['categories'][$category->slug] = [
                    'uuid' => $category->uuid,
                    'name' => $category->name,
                    'key' => $category->slug,
                    'is_system' => $category->is_system,
                    'documents' => [],
                ];
            }
        }

        // Then, add documents to their categories
        foreach ($documents as $document) {
            $year = $document->year;
            $month = $document->month;
            // Documents without category go to a special 'uncategorized' virtual folder
            $category = $document->category ?? '_uncategorized';

            if (!isset($tree[$year])) {
                $tree[$year] = [
                    'year' => $year,
                    'months' => [],
                    'document_count' => 0,
                ];
            }

            if (!isset($tree[$year]['months'][$month])) {
                $tree[$year]['months'][$month] = [
                    'month' => $month,
                    'month_name' => $document->month_name,
                    'categories' => [],
                    'document_count' => 0,
                ];
            }

            // Ensure category exists (from document that may not have a DB category)
            if (!isset($tree[$year]['months'][$month]['categories'][$category])) {
                // Try to find the category in the database
                $dbCategory = DepartmentDocumentCategory::where('department_id', $departmentId)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->where('slug', $category)
                    ->first();

                $tree[$year]['months'][$month]['categories'][$category] = [
                    'uuid' => $dbCategory?->uuid,
                    'name' => $dbCategory?->name ?? self::getCategoryLabel($category),
                    'key' => $category,
                    'is_system' => $dbCategory?->is_system ?? false,
                    'documents' => [],
                ];
            }

            $tree[$year]['months'][$month]['categories'][$category]['documents'][] = [
                'uuid' => $document->uuid,
                'title' => $document->title ?? $document->original_name,
                'original_name' => $document->original_name,
                'file_name' => $document->file_name,
                'file_url' => $document->file_url,
                'preview_url' => $document->preview_url,
                'file_size' => $document->file_size,
                'formatted_file_size' => $document->formatted_file_size,
                'mime_type' => $document->mime_type,
                'extension' => $document->extension,
                'file_type' => $document->file_type,
                'can_preview' => $document->can_preview,
                'preview_type' => $document->preview_type,
                'description' => $document->description,
                'category' => $document->category,
                'created_at' => $document->created_at->toISOString(),
                'uploader' => $document->uploader ? [
                    'id' => $document->uploader->id,
                    'name' => $document->uploader->first_name . ' ' . $document->uploader->last_name,
                ] : null,
            ];

            $tree[$year]['months'][$month]['document_count']++;
            $tree[$year]['document_count']++;
        }

        // Convert categories and months from associative arrays to indexed arrays and sort
        foreach ($tree as &$yearData) {
            foreach ($yearData['months'] as &$monthData) {
                // Sort categories: rapports first (is_system=true), then others alphabetically
                $categories = $monthData['categories'];
                uksort($categories, function($a, $b) use ($categories) {
                    $aIsSystem = $categories[$a]['is_system'] ?? false;
                    $bIsSystem = $categories[$b]['is_system'] ?? false;
                    if ($aIsSystem && !$bIsSystem) return -1;
                    if (!$aIsSystem && $bIsSystem) return 1;
                    return strcmp($a, $b);
                });
                $monthData['categories'] = array_values($categories);
            }
            $yearData['months'] = array_values($yearData['months']);
            usort($yearData['months'], fn($a, $b) => $b['month'] - $a['month']);
        }

        // Convert tree to indexed array and sort by year desc
        $result = array_values($tree);
        usort($result, fn($a, $b) => $b['year'] - $a['year']);

        return $result;
    }

    /**
     * Get the label for a category.
     */
    public static function getCategoryLabel(string $category): string
    {
        $labels = [
            '_uncategorized' => 'Non classés',
            'rapports' => 'Rapports',
            'documents' => 'Documents',
            'comptes_rendus' => 'Comptes-rendus',
            'factures' => 'Factures',
            'contrats' => 'Contrats',
            'autres' => 'Autres',
        ];

        return $labels[$category] ?? ucfirst($category);
    }
}
