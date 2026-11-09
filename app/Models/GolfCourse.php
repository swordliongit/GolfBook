<?php

namespace App\Models;

use Database\Factories\GolfCourseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'region', 'holes', 'par', 'green_fee_cents', 'active'])]
class GolfCourse extends Model
{
    /** @use HasFactory<GolfCourseFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function teeTimes(): HasMany
    {
        return $this->hasMany(TeeTime::class);
    }
}
