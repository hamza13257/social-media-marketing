<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::query()
            ->latest('id')
            ->paginate(25);

        return response()->json([
            'data' => $users->getCollection()->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_super_admin' => $user->is_super_admin,
                'created_at' => $user->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'total' => $users->total(),
            ],
        ]);
    }

    public function updateSuperAdmin(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'is_super_admin' => ['required', 'boolean'],
        ]);

        if ($user->is($request->user()) && $data['is_super_admin'] === false) {
            return response()->json(['message' => 'You cannot remove your own super admin flag.'], 422);
        }

        $user->is_super_admin = $data['is_super_admin'];
        $user->save();

        return response()->json([
            'message' => 'User updated.',
            'data' => [
                'id' => $user->id,
                'is_super_admin' => $user->is_super_admin,
            ],
        ]);
    }
}