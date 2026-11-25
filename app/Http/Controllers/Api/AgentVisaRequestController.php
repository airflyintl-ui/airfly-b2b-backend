<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VisaInformation;
use App\Models\VisaRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AgentVisaRequestController extends Controller
{
    /**
     * Submit a visa application.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'visa_information_id' => 'required|integer|exists:visa_informations,id',
            'passenger_name' => 'required|string|max:255',
            'passport_number' => 'required|string|max:100',
            'email' => 'nullable|email|max:255',
            'mobile' => 'required|string|max:30',
            'remarks' => 'nullable|string|max:3000',
            'documents' => 'required|array|min:1|max:10',
            'documents.*' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $visa = VisaInformation::where('id', $request->visa_information_id)
            ->where('destination_country', 'China')
            ->where('is_active', true)
            ->first();

        if (!$visa) {
            return response()->json([
                'success' => false,
                'message' => 'Selected China visa information is unavailable.',
            ], 404);
        }

        $agentId = $request->user()->id;

        $uploadedDocuments = [];

        foreach ($request->file('documents') as $document) {
            $uploaded = $this->uploadToCloudinary($document);

            if (!$uploaded) {
                return response()->json([
                    'success' => false,
                    'message' => 'Document upload failed. Please try again.',
                ], 500);
            }

            $uploadedDocuments[] = [
                'name' => $document->getClientOriginalName(),
                'url' => $uploaded['url'],
                'public_id' => $uploaded['public_id'],
                'resource_type' => $uploaded['resource_type'],
            ];
        }

        $visaRequest = VisaRequest::create([
            'agent_id' => $agentId,
            'visa_information_id' => $visa->id,
            'destination_country' => 'China',
            'visa_type' => $visa->visa_type,
            'passenger_name' => $request->passenger_name,
            'passport_number' => $request->passport_number,
            'email' => $request->email,
            'mobile' => $request->mobile,
            'remarks' => $request->remarks,
            'documents' => $uploadedDocuments,
            'visa_fee' => $visa->visa_fee,
            'currency' => $visa->currency,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Visa application submitted successfully.',
            'data' => $visaRequest,
        ], 201);
    }

    /**
     * Show only the authenticated agent's requests.
     */
    public function index(Request $request)
    {
        $requests = VisaRequest::where(
            'agent_id',
            $request->user()->id
        )
            ->latest()
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    /**
     * Show one request belonging to the authenticated agent.
     */
    public function show(Request $request, $id)
    {
        $visaRequest = VisaRequest::where(
            'agent_id',
            $request->user()->id
        )->find($id);

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
     * Upload a document to Cloudinary.
     */
    private function uploadToCloudinary($file)
    {
        $cloudName = config('services.cloudinary.cloud_name')
            ?: env('CLOUDINARY_CLOUD_NAME');

        $apiKey = config('services.cloudinary.api_key')
            ?: env('CLOUDINARY_API_KEY');

        $apiSecret = config('services.cloudinary.api_secret')
            ?: env('CLOUDINARY_API_SECRET');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            return null;
        }

        $timestamp = time();

        $folder = 'airfly/visa-documents';

        $signatureParameters = [
            'folder' => $folder,
            'timestamp' => $timestamp,
        ];

        ksort($signatureParameters);

        $signatureString = '';

        foreach ($signatureParameters as $key => $value) {
            $signatureString .= $key . '=' . $value . '&';
        }

        $signatureString = rtrim($signatureString, '&');

        $signature = sha1($signatureString . $apiSecret);

        $response = Http::timeout(60)
            ->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )
            ->post(
                "https://api.cloudinary.com/v1_1/{$cloudName}/auto/upload",
                [
                    'api_key' => $apiKey,
                    'timestamp' => $timestamp,
                    'folder' => $folder,
                    'signature' => $signature,
                ]
            );

        if (!$response->successful()) {
            return null;
        }

        $result = $response->json();

        if (
            empty($result['secure_url']) ||
            empty($result['public_id'])
        ) {
            return null;
        }

        return [
            'url' => $result['secure_url'],
            'public_id' => $result['public_id'],
            'resource_type' => $result['resource_type'] ?? 'raw',
        ];
    }
}
