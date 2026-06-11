<?php

namespace Scarneros\JapanPostalCodes\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Scarneros\JapanPostalCodes\JapanPostalCodes;

class PostalCodeController extends Controller
{
    public function __construct(
        protected JapanPostalCodes $service,
    ) {}

    public function show(string $postalCode): JsonResponse
    {
        $normalized = $this->service::normalize($postalCode);

        if (mb_strlen($normalized) !== 7) {
            return response()->json([
                'error' => 'Invalid postal code format.',
                'message' => 'A Japanese postal code must contain exactly 7 digits.',
            ], 422);
        }

        $results = $this->service->lookup($normalized);

        if ($results->isEmpty()) {
            return response()->json([
                'data' => [],
            ]);
        }

        $data = $results->map(fn (array $row) => [
            'postal_code' => $row['postal_code'],
            'formatted' => $row['postal_code_formatted'],
            'prefecture' => $row['prefecture'],
            'city' => $row['city'],
            'town' => $row['town'],
            'address' => $row['address'],
            'kana' => $row['kana'],
            'romaji' => $row['romaji'],
        ])->values();

        return response()->json([
            'data' => $data,
        ]);
    }
}
