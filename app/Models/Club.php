<?php

namespace App\Models;

use Database\Factories\ClubFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Club extends Model
{
    /** @use HasFactory<ClubFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'owner_name',
        'phone',
        'address',
        'logo',
    ];

    public function user()
    {
        return $this->hasOne(User::class);
    }

    public function members()
    {
        return $this->hasMany(Member::class);
    }
}
