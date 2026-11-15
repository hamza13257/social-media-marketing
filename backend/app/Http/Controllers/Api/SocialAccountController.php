<?php

namespace App\Http\Controllers\Api;

use App\Enums\SocialConnectionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\SocialAccount\UpdateSocialAccountRequest;
use App\Http\Resources\SocialAccountResource;
use App\Models\SocialAccount;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialAccountController extends Controller
{
    public function __construct(private CurrentTenant $currentTenant) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', SocialAccount::class);

        $accounts = SocialAccount::query()
            ->with('brand:id,name')
            ->where('tenant_id', $this->currentTenant->id())
            ->when($request->filled('brand_id'), fn ($q) => $q->where('brand_id', $request->integer('brand_id')))
            ->when($request->filled('platform'), fn ($q) => $q->where('platform', $request->string('platform')))
            ->orderBy('platform')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => SocialAccountResource::collection($accounts),
            'meta' => [
                'meta_configured' => app(\App\Services\Meta\MetaOAuthService::class)->isConfigured(),
            ],
        ]);
    }

    public function show(SocialAccount $socialAccount): JsonResponse
    {
        $this->authorize('view', $socialAccount);

        $socialAccount->load('brand:id,name');

        return response()->json([
            'data' => new SocialAccountResource($socialAccount),
        ]);
    }

    public function update(UpdateSocialAccountRequest $request, SocialAccount $socialAccount): JsonResponse
    {
        $this->authorize('update', $socialAccount);

        $socialAccount->fill($request->validated());
        $socialAccount->save();
        $socialAccount->load('brand:id,name');

        return response()->json([
            'data' => new SocialAccountResource($socialAccount),
        ]);
    }

    public function destroy(SocialAccount $socialAccount): JsonResponse
    {
        $this->authorize('delete', $socialAccount);

        $socialAccount->access_token = '';
        $socialAccount->connection_status = SocialConnectionStatus::Disconnected;
        $socialAccount->save();
        $socialAccount->delete();

        return response()->json(null, 204);
    }
}