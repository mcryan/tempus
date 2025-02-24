<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TimeEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        $timeEntries = Auth::user()->timeEntries()
            ->with(['project', 'tags'])
            ->orderBy('start_time', 'desc')
            ->get();
        
        return response()->json($timeEntries);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        // Stop any currently running time entry
        $runningEntry = Auth::user()->timeEntries()
            ->where('is_running', true)
            ->first();

        if ($runningEntry) {
            $this->stopTimeEntry($runningEntry);
        }

        $validated = $request->validate([
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'start_time' => 'nullable|date',
            'is_running' => 'boolean',
            'is_billable' => 'boolean',
        ]);

        // Set default values
        $validated['start_time'] = $validated['start_time'] ?? Carbon::now();
        $validated['is_running'] = true;
        $validated['description'] = $validated['description'] ?? '';

        // Create the time entry
        $timeEntry = Auth::user()->timeEntries()->create($validated);

        return response()->json($timeEntry->load(['project', 'tags']), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $timeEntry = Auth::user()->timeEntries()
            ->with(['project', 'tags'])
            ->findOrFail($id);
        
        return response()->json($timeEntry);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $timeEntry = Auth::user()->timeEntries()->findOrFail($id);

        $validated = $request->validate([
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'start_time' => 'required|date',
            'end_time' => 'required|date',
            'is_running' => 'sometimes|boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'exists:tags,id'
        ]);

        // Calculate duration
        $duration = Carbon::parse($validated['end_time'])
            ->diffInSeconds(Carbon::parse($validated['start_time']));
        
        $validated['duration'] = $duration;
        $validated['is_running'] = false;

        $timeEntry->update($validated);

        // Sync tags if provided
        if (isset($validated['tags'])) {
            $timeEntry->tags()->sync($validated['tags']);
        }

        return response()->json($timeEntry->load(['project', 'tags']));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $timeEntry = Auth::user()->timeEntries()->findOrFail($id);
        $timeEntry->delete();

        return response()->json(null, 204);
    }

    public function stop(string $id): JsonResponse
    {
        $timeEntry = Auth::user()->timeEntries()->findOrFail($id);
        
        // If the entry is not running, just return it as is
        if (!$timeEntry->is_running) {
            return response()->json($timeEntry->load(['project', 'tags']));
        }

        // Validate and update the description and project_id if provided
        $validated = request()->validate([
            'description' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id'
        ]);

        $endTime = Carbon::now();
        $duration = $endTime->diffInSeconds(Carbon::parse($timeEntry->start_time));

        $timeEntry->update(array_merge($validated, [
            'end_time' => $endTime,
            'duration' => $duration,
            'is_running' => false
        ]));

        return response()->json($timeEntry->load(['project', 'tags']));
    }

    public function current(): JsonResponse
    {
        $timeEntry = Auth::user()->timeEntries()
            ->with(['project', 'tags'])
            ->where('is_running', true)
            ->first();

        return response()->json($timeEntry);
    }

    protected function stopTimeEntry(TimeEntry $timeEntry): void
    {
        if (!$timeEntry->is_running) {
            return;
        }

        $endTime = Carbon::now();
        $duration = $endTime->diffInSeconds(Carbon::parse($timeEntry->start_time));

        $timeEntry->update([
            'end_time' => $endTime,
            'duration' => $duration,
            'is_running' => false
        ]);
    }
}
