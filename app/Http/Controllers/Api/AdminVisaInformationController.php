<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisaInformation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminVisaInformationController extends Controller
{
    /**
     * Show all visa information.
     */
    public function index()
    {
        $visaInformation = VisaInformation::orderBy('destination_country')
            ->orderBy('visa_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $visaInformation,
        ]);
    }

    /**
     * Show one visa information record.
     */
    public function show($id)
    {
        $visa = VisaInformation::find($id);

        if (!$visa) {
            return response()->json([
                'success' => false,
                'message' => 'Visa information not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $visa,
        ]);
    }

    /**
     * Create visa information.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'destination_country' => 'required|string|max:100',
            'visa_type' => 'required|string|max:100',
            'processing_time' => 'nullable|string|max:100',
            'validity' => 'nullable|string|max:100',
            'entry_type' => 'nullable|string|max:100',
            'visa_fee' => 'required|numeric|min:0',
            'currency' => 'required|string|max:10',
            'required_documents' => 'nullable|array',
            'required_documents.*' => 'required|string|max:255',
            'description' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $validator->validated();

        $data['is_active'] = $data['is_active'] ?? true;

        $visa = VisaInformation::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Visa information created successfully.',
            'data' => $visa,
        ], 201);
    }

    /**
     * Update visa information.
     */
    public function update(Request $request, $id)
    {
        $visa = VisaInformation::find($id);

        if (!$visa) {
            return response()->json([
                'success' => false,
                'message' => 'Visa information not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'destination_country' => 'sometimes|required|string|max:100',
            'visa_type' => 'sometimes|required|string|max:100',
            'processing_time' => 'sometimes|nullable|string|max:100',
            'validity' => 'sometimes|nullable|string|max:100',
            'entry_type' => 'sometimes|nullable|string|max:100',
            'visa_fee' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|max:10',
            'required_documents' => 'sometimes|nullable|array',
            'required_documents.*' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'terms_conditions' => 'sometimes|nullable|string',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $visa->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Visa information updated successfully.',
            'data' => $visa->fresh(),
        ]);
    }

    /**
     * Delete visa information.
     */
    public function destroy($id)
    {
        $visa = VisaInformation::find($id);

        if (!$visa) {
            return response()->json([
                'success' => false,
                'message' => 'Visa information not found.',
            ], 404);
        }

        $visa->delete();

        return response()->json([
            'success' => true,
            'message' => 'Visa information deleted successfully.',
        ]);
    }

    /**
     * Public active visa information for all countries.
     */
    public function publicIndex()
    {
        $visaInformation = VisaInformation::where('is_active', true)
            ->orderBy('destination_country')
            ->orderBy('visa_type')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $visaInformation,
        ]);
    }
}
