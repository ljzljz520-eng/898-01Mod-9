<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Building extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'community_name',
        'total_floors',
        'total_units',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
        'total_floors' => 'integer',
        'total_units' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function topics()
    {
        return $this->hasMany(Topic::class);
    }

    public function userHistory()
    {
        return $this->hasMany(BuildingUserHistory::class);
    }

    public function verifiedResidents()
    {
        return $this->hasMany(User::class)
            ->where('verification_status', 'verified')
            ->whereNull('moved_at');
    }

    public function movedOutResidents()
    {
        return $this->hasMany(User::class)
            ->whereNotNull('moved_at');
    }

    public function getResidentCountAttribute()
    {
        return $this->users()
            ->where('verification_status', 'verified')
            ->whereNull('moved_at')
            ->count();
    }
}
