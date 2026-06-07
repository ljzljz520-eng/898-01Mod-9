<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BuildingUserHistory extends Model
{
    use HasFactory;

    protected $table = 'building_user_history';

    protected $fillable = [
        'user_id',
        'building_id',
        'unit_number',
        'resident_type',
        'move_in_at',
        'move_out_at',
        'remark',
    ];

    protected $casts = [
        'move_in_at' => 'datetime',
        'move_out_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function building()
    {
        return $this->belongsTo(Building::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('move_out_at');
    }
}
