<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Topic extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'category',
        'circle_type',
        'building_id',
        'extra_fields',
        'view_count',
        'reply_count',
        'is_pinned',
        'status',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'status' => 'integer',
        'view_count' => 'integer',
        'reply_count' => 'integer',
        'extra_fields' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function knowledgeCard()
    {
        return $this->hasOne(KnowledgeCard::class);
    }

    public function scopeEligibleForKnowledgeCard($query)
    {
        return $query->whereIn('category', ['broadband', 'school', 'parking', 'renovation'])
            ->whereDoesntHave('knowledgeCard');
    }

    public function scopeByCircle($query, $circleType, $buildingId = null)
    {
        $query->where('circle_type', $circleType);

        if ($circleType !== 'public' && !is_null($buildingId)) {
            $query->where('building_id', $buildingId);
        }

        return $query;
    }

    public function scopeByAccessibleCircles($query, ?User $user)
    {
        if (!$user) {
            return $query->where('circle_type', 'public');
        }

        $circles = $user->getAccessibleCircleTypes();

        if (in_array('public', $circles) && count($circles) === 1) {
            return $query->where('circle_type', 'public');
        }

        return $query->where(function ($q) use ($circles, $user) {
            foreach ($circles as $circle) {
                if ($circle === 'public') {
                    $q->orWhere(function ($subQ) {
                        $subQ->where('circle_type', 'public');
                    });
                } else {
                    $q->orWhere(function ($subQ) use ($circle, $user) {
                        $subQ->where('circle_type', $circle)
                            ->where('building_id', $user->building_id);
                    });
                }
            }
        });
    }

    public function getVisibleFieldsForUser(?User $user): array
    {
        $fields = [
            'id',
            'title',
            'content',
            'category',
            'circle_type',
            'building_id',
            'user_id',
            'view_count',
            'reply_count',
            'is_pinned',
            'status',
            'created_at',
            'updated_at',
        ];

        if ($this->circle_type === 'public') {
            return array_merge($fields, $this->getExtraFieldsForCategory('public', $user));
        }

        if (!$user || !$user->canAccessCircle($this->circle_type, $this->building_id)) {
            return $fields;
        }

        return array_merge($fields, $this->getExtraFieldsForCategory($this->category, $user));
    }

    protected function getExtraFieldsForCategory(string $category, ?User $user): array
    {
        $extra = $this->extra_fields ?? [];

        if (is_null($user)) {
            return [];
        }

        return match ($category) {
            'maintenance' => $this->getMaintenanceFields($extra, $user),
            'conflict' => $this->getConflictFields($extra, $user),
            'fee' => $this->getFeeFields($extra, $user),
            default => $extra,
        };
    }

    protected function getMaintenanceFields(array $extra, User $user): array
    {
        $baseFields = ['description', 'status', 'reported_at'];

        if ($user->isOwner() || $user->isCommittee() || $user->isAdmin()) {
            return array_merge($baseFields, ['unit_number', 'contact_name', 'contact_phone', 'assigned_to', 'resolved_at', 'cost']);
        }

        if ($user->isTenant()) {
            return array_merge($baseFields, ['unit_number', 'contact_name', 'assigned_to']);
        }

        return $baseFields;
    }

    protected function getConflictFields(array $extra, User $user): array
    {
        $baseFields = ['title', 'description', 'status', 'reported_at'];

        if ($user->isCommittee() || $user->isAdmin()) {
            return array_merge($baseFields, ['involved_parties', 'unit_numbers', 'contact_info', 'mediator', 'resolution', 'resolved_at']);
        }

        if ($user->isOwner()) {
            return array_merge($baseFields, ['involved_parties', 'unit_numbers']);
        }

        return $baseFields;
    }

    protected function getFeeFields(array $extra, User $user): array
    {
        $baseFields = ['fee_type', 'amount', 'due_date', 'status'];

        if ($user->isOwner() || $user->isCommittee() || $user->isAdmin()) {
            return array_merge($baseFields, ['unit_number', 'payment_method', 'paid_at', 'receipt_number', 'late_fee', 'total_amount']);
        }

        if ($user->isTenant()) {
            return array_merge($baseFields, ['unit_number']);
        }

        return $baseFields;
    }

    public function toArrayForUser(?User $user): array
    {
        $data = $this->toArray();
        $visibleFields = $this->getVisibleFieldsForUser($user);

        $filtered = array_intersect_key($data, array_flip($visibleFields));

        if (isset($data['extra_fields']) && is_array($data['extra_fields'])) {
            $categoryFields = $this->getExtraFieldsForCategory($this->category, $user);
            $filtered['extra_fields'] = array_intersect_key(
                $data['extra_fields'],
                array_flip($categoryFields)
            );
        }

        if (isset($data['user'])) {
            $filtered['user'] = [
                'id' => $data['user']['id'],
                'username' => $data['user']['username'],
                'avatar' => $data['user']['avatar'],
                'resident_type' => $data['user']['resident_type'] ?? null,
            ];
        }

        if (isset($data['building']) && isset($data['building']['name'])) {
            $filtered['building_name'] = $data['building']['name'];
        }

        return $filtered;
    }
}
