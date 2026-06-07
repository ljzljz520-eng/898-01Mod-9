<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'avatar',
        'role',
        'status',
        'building_id',
        'unit_number',
        'verification_status',
        'resident_type',
        'real_name',
        'id_card',
        'verification_documents',
        'verified_at',
        'moved_at',
        'verification_remark',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'id_card',
        'verification_documents',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'verified_at' => 'datetime',
        'moved_at' => 'datetime',
        'verification_documents' => 'array',
    ];

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function replies()
    {
        return $this->hasMany(Reply::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function buildingHistory()
    {
        return $this->hasMany(BuildingUserHistory::class);
    }

    public function knowledgeCards()
    {
        return $this->hasMany(KnowledgeCard::class, 'moderator_id');
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isModerator()
    {
        return $this->isAdmin();
    }

    public function isVerified()
    {
        return $this->verification_status === 'verified';
    }

    public function isMoved()
    {
        return !is_null($this->moved_at);
    }

    public function isOwner()
    {
        return $this->isVerified() && !$this->isMoved() && $this->resident_type === 'owner';
    }

    public function isTenant()
    {
        return $this->isVerified() && !$this->isMoved() && $this->resident_type === 'tenant';
    }

    public function isCommittee()
    {
        return $this->isVerified() && !$this->isMoved() && $this->resident_type === 'committee';
    }

    public function canAccessCircle($circleType, $buildingId = null)
    {
        if ($circleType === 'public') {
            return true;
        }

        if ($this->isMoved()) {
            return false;
        }

        if (!$this->isVerified()) {
            return false;
        }

        if (!is_null($buildingId) && $this->building_id !== $buildingId) {
            return false;
        }

        return match ($circleType) {
            'building' => true,
            'committee' => $this->isCommittee() || $this->isAdmin(),
            'tenant' => $this->isTenant() || $this->isCommittee() || $this->isAdmin(),
            default => false,
        };
    }

    public function getAccessibleCircleTypes()
    {
        $circles = ['public'];

        if (!$this->isMoved() && $this->isVerified()) {
            $circles[] = 'building';

            if ($this->isCommittee() || $this->isAdmin()) {
                $circles[] = 'committee';
            }

            if ($this->isTenant() || $this->isCommittee() || $this->isAdmin()) {
                $circles[] = 'tenant';
            }
        }

        return $circles;
    }

    public function markAsMoved($remark = null)
    {
        $this->update([
            'moved_at' => now(),
        ]);

        $this->buildingHistory()
            ->active()
            ->update([
                'move_out_at' => now(),
                'remark' => $remark,
            ]);

        return $this;
    }

    public function verify($residentType, $buildingId, $unitNumber = null)
    {
        $this->update([
            'verification_status' => 'verified',
            'resident_type' => $residentType,
            'building_id' => $buildingId,
            'unit_number' => $unitNumber,
            'verified_at' => now(),
            'moved_at' => null,
        ]);

        BuildingUserHistory::create([
            'user_id' => $this->id,
            'building_id' => $buildingId,
            'unit_number' => $unitNumber,
            'resident_type' => $residentType,
            'move_in_at' => now(),
        ]);

        return $this;
    }
}
