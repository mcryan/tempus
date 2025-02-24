<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $user = Auth::user();
        
        // Admin users can see all clients
        if ($user->is_admin) {
            $clients = Client::with('projects')->get();
        } else {
            // Regular users can only see their assigned clients
            $clients = $user->clients()->with('projects')->get();
        }
        
        return response()->json($clients);
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
            'default_billable_rate' => 'nullable|numeric|min:0',
            'is_billable_by_default' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        $client = Client::create($validated);

        return response()->json($client, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        $client = Client::with('projects')->findOrFail($id);
        
        // Admin users can see any client
        if ($user->is_admin) {
            return response()->json($client);
        }
        
        // Regular users can only see their assigned clients
        if ($user->clients()->where('clients.id', $id)->exists()) {
            return response()->json($client);
        }
        
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $client = Client::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'default_billable_rate' => 'nullable|numeric|min:0',
            'is_billable_by_default' => 'boolean',
            'notes' => 'nullable|string'
        ]);

        $client->update($validated);

        return response()->json($client);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        if (!Auth::user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $client = Client::findOrFail($id);
        $client->delete();

        return response()->json(null, 204);
    }
} 