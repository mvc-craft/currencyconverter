<?php

namespace App\Http\Controllers;

use App\ExchangeRate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CacheController extends Controller
{
    public function clearCacheForRates(): JsonResponse
    {
        ExchangeRate::whereNotNull('id')->delete();

        return response()->json([
            'error' => 0,
            'msg' => 'OK'
        ], JsonResponse::HTTP_OK);
    }
}
