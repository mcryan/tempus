<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Get user's own projects and projects from their clients
        $projects = Project::with(['client'])
            ->where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->orWhereIn('client_id', $user->clients()->pluck('clients.id'));
            })
            ->get();
        
        return response()->json($projects);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_billable' => 'boolean',
            'billable_rate' => 'nullable|numeric|min:0',
            'client_id' => 'nullable|exists:clients,id'
        ]);

        // Create the project directly, not through the user relationship
        $project = Project::create([
            ...$validated,
            'user_id' => $user->id // Keep track of who created it
        ]);
        
        $project->load('client');

        return response()->json($project, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Allow access if user created the project or if it belongs to one of their clients
        $project = Project::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereIn('client_id', $user->clients()->pluck('clients.id'));
        })->with(['client'])->findOrFail($id);
        
        return response()->json($project);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Allow update if user created the project or if it belongs to one of their clients
        $project = Project::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereIn('client_id', $user->clients()->pluck('clients.id'));
        })->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|required|string|max:7',
            'description' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
            'is_billable' => 'sometimes|boolean',
            'billable_rate' => 'nullable|numeric|min:0',
            'client_id' => 'nullable|exists:clients,id'
        ]);

        $project->update($validated);
        $project->load('client');

        return response()->json($project);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();
        
        // Allow deletion if user created the project or if it belongs to one of their clients
        $project = Project::where(function($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhereIn('client_id', $user->clients()->pluck('clients.id'));
        })->findOrFail($id);
        
        $project->delete();

        return response()->json(null, 204);
    }
}
