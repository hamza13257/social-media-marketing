<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublishingLogResource;
use App\Models\Content;
use App\Models\PublishingLog;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublishingLogController extends Controller
{
    public function __construct(private CurrentTenant $currentTenant) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Content::class);

        $logs = PublishingLog::query()
            ->where('tenant_id', $this->currentTenant->id())
            ->when($request->filled('content_id'), fn ($q) => $q->where('content_id', $request->integer('content_id')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderByDesc('id')
            ->limit(100)
            ->get();

        return response()->json([
            'data' => PublishingLogResource::collection($logs),
        ]);
    }
}