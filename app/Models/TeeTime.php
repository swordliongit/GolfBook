<?php

namespace App\Models;

use Database\Factories\TeeTimeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['golf_course_id', 'starts_at', 'capacity', 'booked_players'])]
class TeeTime extends Model
{
    /** @use HasFactory<TeeTimeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['starts_at' => 'datetime'];
    }

    public function golfCourse(): BelongsTo
    {
        return $this->belongsTo(GolfCourse::class);
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(BookingRound::class);
    }

    public function freeSpots(): int
    {
        return max(0, $this->capacity - $this->booked_players);
    }

    /** @param Builder<TeeTime> $query */
    public function scopeOnDate(Builder $query, string $date): void
    {
        $query->whereDate('starts_at', $date)->orderBy('starts_at');
    }
}
