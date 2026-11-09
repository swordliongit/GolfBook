<?php

namespace App\Models;

use Database\Factories\PartnerFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * A tour operator (UK, Germany, ...) that books through the REST API.
 */
#[Fillable(['name', 'country', 'api_key_hash', 'active'])]
#[Hidden(['api_key_hash'])]
class Partner extends Model
{
    /** @use HasFactory<PartnerFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['active' => 'boolean'];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public static function hashKey(string $plainKey): string
    {
        return hash('sha256', $plainKey);
    }

    /**
     * Creates a partner and returns the plain API key once; only its hash is stored.
     *
     * @return array{0: Partner, 1: string}
     */
    public static function register(string $name, string $country): array
    {
        $plainKey = 'gb_'.Str::random(40);

        $partner = static::create([
            'name' => $name,
            'country' => strtoupper($country),
            'api_key_hash' => static::hashKey($plainKey),
        ]);

        return [$partner, $plainKey];
    }
}
