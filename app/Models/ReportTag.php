<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $report_id
 * @property string $tag
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\DepartmentReport $report
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag forReport(int $id)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag whereReportId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag whereTag($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReportTag withTag(string $tag)
 * @mixin \Eloquent
 */
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
