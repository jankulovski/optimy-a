<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Donation;
use Illuminate\Http\Request;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Events\DonationReceived;

class DonationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResource
    {
        $user = $request->user();
        assert($user instanceof User);
        $donations = $user->donations()->with('campaign:id,title')->latest()->paginate(10);
        return JsonResource::collection($donations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'amount' => 'required|numeric|min:1',
        ]);

        /** @var Campaign $campaign */
        $campaign = Campaign::findOrFail($validated['campaign_id']);

        // Basic check if campaign is active, can be expanded
        if ($campaign->status !== 'active') {
            return response()->json(['message' => 'Campaign is not active.'], 400);
        }

        $user = $request->user();
        assert($user instanceof User);
        $donation = $user->donations()->create([
            'campaign_id' => $campaign->id,
            'amount' => $validated['amount'],
            'status' => 'succeeded', // Assuming direct success for now, payment integration would change this
        ]);

        // Update campaign's current amount
        $campaign->increment('current_amount', $validated['amount']);

        // Here you would typically trigger an event to send a confirmation email/notification
        event(new DonationReceived($donation));

        return response()->json(['data' => $donation], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Donation $donation): JsonResponse
    {
        $user = $request->user();
        assert($user instanceof User);
        if ($user->cannot('view', $donation)) {
            return response()->json(['message' => 'Forbidden'], 403);
        }
        return response()->json(['data' => $donation->load('campaign:id,title', 'user:id,name')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Donation $donation): JsonResponse
    {
        // TODO: Implement update logic
        return response()->json(['message' => 'Not implemented'], 501);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Donation $donation): JsonResponse
    {
        // TODO: Implement destroy logic
        return response()->json(null, 204); // Or 501 if not implemented
    }
}
