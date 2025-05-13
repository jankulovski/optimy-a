<?php

namespace App\Http\Controllers\API;

use App\Models\Campaign;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\User;

class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResource
    {
        return JsonResource::collection(Campaign::with('user:id,name')->latest()->paginate(10));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'goal_amount' => 'required|numeric|min:1',
            'start_date' => 'nullable|date|after_or_equal:today',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $user = $request->user();
        assert($user instanceof User);
        $campaign = $user->campaigns()->create($validated);

        return response()->json(['data' => $campaign], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Campaign $campaign): JsonResponse
    {
        return response()->json(['data' => $campaign->load('user:id,name')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Campaign $campaign): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);
        if ($user->cannot('update', $campaign)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'goal_amount' => 'sometimes|required|numeric|min:1',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'sometimes|required|in:active,inactive,completed,cancelled',
        ]);

        $campaign->update($validated);

        return response()->json(['data' => $campaign]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Campaign $campaign): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);
        if ($user->cannot('delete', $campaign)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $campaign->delete();

        return response()->json(null, 204);
    }
}
