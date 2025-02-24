<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        // Only admin users can list all users
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $users = User::all();
        // Make pay_rate visible for admin users
        $users->makeVisible('pay_rate');
        return response()->json($users);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'billable_rate' => 'nullable|numeric|min:0',
            'pay_rate' => 'nullable|numeric|min:0',
            'is_admin' => 'boolean'
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json($user, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);
        // Make pay_rate visible for admin users
        $user->makeVisible('pay_rate');
        return response()->json($user);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $id,
            'password' => 'nullable|string|min:8',
            'billable_rate' => 'nullable|numeric|min:0',
            'pay_rate' => 'nullable|numeric|min:0',
            'is_admin' => 'boolean'
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);
        
        // Make pay_rate visible in the response for admin users
        $user->makeVisible('pay_rate');
        return response()->json($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === Auth::id()) {
            return response()->json(['message' => 'Cannot delete your own account'], 400);
        }

        $user->delete();

        return response()->json(null, 204);
    }

    /**
     * Get the clients associated with the user.
     */
    public function getClients(string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Allow users to view their own clients or admin to view any user's clients
        if (Auth::id() === $user->id || Auth::user()->is_admin) {
            return response()->json($user->clients);
        }
        
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Update the clients associated with the user.
     */
    public function updateClients(Request $request, string $id): JsonResponse
    {
        $user = User::findOrFail($id);
        
        // Allow users to update their own clients or admin to update any user's clients
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