<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GroupFareRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AdminGroupFareRequestController extends Controller
{
    /**
     * Admin: View All Group Fare Requests
     */
    public function index()
    {
        $requests = GroupFareRequest::with(['offer', 'agent'])
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'requests' => $requests
        ]);
    }

    /**
     * Admin: View Single Request
     */
    public function show($id)
    {
        $groupRequest = GroupFareRequest::with(['offer', 'agent'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'request' => $groupRequest
        ]);
    }

    /**
     * Admin: Approve or Reject Request
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'status' => [
                'required',
                Rule::in(['approved', 'rejected'])
            ],
            'admin_note' => 'nullable|string|max:2000',
        ]);

        return DB::transaction(function () use ($validated, $id) {

            $groupRequest = GroupFareRequest::with('offer')
                ->lockForUpdate()
                ->findOrFail($id);

            if ($groupRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'This request has already been processed.'
                ], 422);
            }

            if ($validated['status'] === 'approved') {
                $offer = $groupRequest->offer()
                    ->lockForUpdate()
                    ->firstOrFail();

                if (
                    $offer->status !== 'published' ||
                    $offer->available_seats < $groupRequest->passenger_count
                ) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Offer is unavailable or seats are insufficient.'
                    ], 422);
                }

                $offer->decrement(
                    'available_seats',
                    $groupRequest->passenger_count
                );
            }

            $groupRequest->status = $validated['status'];
            $groupRequest->admin_note = $validated['admin_note'] ?? null;
            $groupRequest->save();

            return response()->json([
                'success' => true,
                'message' => 'Group Fare Request ' .
                    ucfirst($validated['status']) . ' Successfully',
                'request' => $groupRequest->fresh(['offer', 'agent'])
            ]);
        });
    }
}
