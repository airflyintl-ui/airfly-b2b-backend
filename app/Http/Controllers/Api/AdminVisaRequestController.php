<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminVisaRequestController extends Controller
{
    /**
     * List all visa requests.
     */
    public function index(Request $request)
    {
        $query = VisaRequest::query()->latest();

        if ($request->filled('status')) {
            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,approved,rejected',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid status filter.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($builder) use ($search) {
                $builder->where('passenger_name', 'like', "%{$search}%")
                    ->orWhere('passport_number', 'like', "%{$search}%")
                    ->orWhere('mobile', 'like', "%{$search}%");
            });
        }

        $requests = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Show one visa request.
     */
    public function show($id)
    {
        $visaRequest = VisaRequest::find($id);

        if (!$visaRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Visa request not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $visaRequest,
        ]);
    }

    /**
     * Approve or reject a pending visa request.
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:approved,rejected',
            'admin_note' => 'nullable|string|max:3000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $visaRequest = VisaRequest::find($id);

        if (!$visaRequest) {
            return response()->json([
                'success' => false,
                'message' => 'Visa request not found.',
            ], 404);
        }

        if ($visaRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending visa requests can be reviewed.',
            ], 422);
        }

        $visaRequest->status = $request->status;
        $visaRequest->admin_note = $request->admin_note;
        $visaRequest->reviewed_at = now();
        $visaRequest->save();

        return response()->json([
            'success' => true,
            'message' => 'Visa request ' . $request->status . ' successfully.',
            'data' => $visaRequest->fresh(),
        ]);
    }
}
