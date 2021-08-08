<?php

namespace NathanHeffley\LaravelWatermelon;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SyncController extends Controller
{
    public function pull(SyncService $watermelon, Request $request): JsonResponse
    {
        return $watermelon->pull($request);
    }

    public function push(SyncService $watermelon, Request $request): JsonResponse
    {
        return $watermelon->push($request);
    }
}
