<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Base class for business rule violations while booking.
 * API requests get a JSON error with a stable machine-readable code.
 */
abstract class BookingException extends RuntimeException
{
    abstract public function errorCode(): string;

    public function status(): int
    {
        return 409;
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson() && ! $request->is('api/*')) {
            return null; // fall back to the default handler (web routes catch these themselves)
        }

        return response()->json([
            'message' => $this->getMessage(),
            'error' => $this->errorCode(),
        ], $this->status());
    }
}
