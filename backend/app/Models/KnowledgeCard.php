<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class KnowledgeCard extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_DISABLED = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_NEEDS_REVIEW = 2;
    const STATUS_EXPIRED = 3;

    const CATEGORY_BROADBAND = 'broadband';
    const CATEGORY_SCHOOL = 'school';
    const CATEGORY_PARKING = 'parking';
    const CATEGORY_RENOVATION = 'renovation';

    protected $fillable = [
        'topic_id',
        'moderator_id',
        'title',
        'summary',
        'category',
        'tags',
        'expire_date',
        'last_reviewed_at',
        'status',
        'view_count',
    ];

    protected $casts = [
        'status' => 'integer',
        'view_count' => 'integer',
        'expire_date' => 'date',
        'last_reviewed_at' => 'date',
    ];

    public static function categoryLabels(): array
    {
        return [
            self::CATEGORY_BROADBAND => '宽带办理',
            self::CATEGORY_SCHOOL => '学区材料',
            self::CATEGORY_PARKING => '停车证',
            self::CATEGORY_RENOVATION => '装修流程',
        ];
    }

    public static function statusLabels(): array
    {
        return [
            self::STATUS_DISABLED => '禁用',
            self::STATUS_ACTIVE => '正常',
            self::STATUS_NEEDS_REVIEW => '待复核',
            self::STATUS_EXPIRED => '已过期',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categoryLabels()[$this->category] ?? $this->category;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? '未知';
    }

    public function getTagsArrayAttribute(): array
    {
        return $this->tags ? array_filter(array_map('trim', explode(',', $this->tags))) : [];
    }

    public function topic()
    {
        return $this->belongsTo(Topic::class);
    }

    public function moderator()
    {
        return $this->belongsTo(User::class, 'moderator_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeNeedsReview($query)
    {
        return $query->where('status', self::STATUS_NEEDS_REVIEW);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', self::STATUS_EXPIRED);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeSearch($query, string $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('summary', 'like', "%{$keyword}%")
              ->orWhere('tags', 'like', "%{$keyword}%");
        });
    }

    public function scopeCheckExpiry($query)
    {
        $today = Carbon::today();
        $thirtyDaysLater = Carbon::today()->addDays(30);

        return $query->where(function ($q) use ($today, $thirtyDaysLater) {
            $q->where('expire_date', '<=', $today)
              ->where('status', '!=', self::STATUS_EXPIRED);
        })->orWhere(function ($q) use ($today, $thirtyDaysLater) {
            $q->whereBetween('expire_date', [$today, $thirtyDaysLater])
              ->where('status', self::STATUS_ACTIVE);
        });
    }

    public function isExpired(): bool
    {
        return $this->expire_date && Carbon::today()->gt($this->expire_date);
    }

    public function needsReviewSoon(): bool
    {
        return $this->expire_date && Carbon::today()->addDays(30)->gte($this->expire_date) && !$this->isExpired();
    }

    public function updateStatusByExpiry(): void
    {
        if ($this->isExpired()) {
            $this->status = self::STATUS_EXPIRED;
        } elseif ($this->needsReviewSoon()) {
            $this->status = self::STATUS_NEEDS_REVIEW;
        }
        $this->save();
    }

    public function markAsReviewed(): void
    {
        $this->last_reviewed_at = Carbon::today();
        $this->status = self::STATUS_ACTIVE;
        $this->save();
    }

    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }
}
