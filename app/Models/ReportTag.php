<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'tag',
    ];

    // Relations
    public function report(): BelongsTo
    {
        return $this->belongsTo(DepartmentReport::class, 'report_id');
    }

    // Scopes
    public function scopeForReport($q, int $id)
    {
        return $q->where('report_id', $id);
    }

    public function scopeWithTag($q, string $tag)
    {
        return $q->where('tag', $tag);
    }

    // Static methods
    public static function getPopularTags(int $limit = 10): \Illuminate\Support\Collection
    {
        return static::query()
            ->selectRaw('tag, COUNT(*) as count')
            ->groupBy('tag')
            ->orderByDesc('count')
            ->limit($limit)
            ->pluck('count', 'tag');
    }

    public static function searchTags(string $query): \Illuminate\Support\Collection
    {
        return static::query()
            ->where('tag', 'like', "%{$query}%")
            ->distinct()
            ->pluck('tag');
    }
}
