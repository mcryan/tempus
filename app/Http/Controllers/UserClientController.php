<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class UserClientController extends Controller
{
    /**
     * Display a listing of the user's clients.
     */
    public function index(string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        if (Auth::id() === $user->id || Auth::user()->is_admin) {
            return response()->json($user->clients);
        }
        
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Update the user's client associations.
     */
    public function update(Request $request, string $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        
        if (Auth::id() === $user->id || Auth::user()->is_admin) {
            $validated = $request->validate([
                'client_ids' => 'required|array',
                'client_ids.*' => 'exists:clients,id'
            ]);

            $user->clients()->sync($validated['client_ids']);
            return response()->json($user->clients);
        }
        
        return response()->json(['message' => 'Unauthorized'], 403);
    }
} 