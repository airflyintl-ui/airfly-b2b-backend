<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recharge;
use App\Models\Agent;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class RechargeController extends Controller
{
    /**
     * AGENT RECHARGE LIST
     */
    public function index(Request $request)
    {
        $agent = $request->user();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $recharges = Recharge::where('agent_id', $agent->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'recharges' => $recharges
        ]);
    }

    /**
     * UPLOAD PAYMENT SLIP TO CLOUDINARY
     */
    private function uploadSlipToCloudinary($file)
    {
        $cloudName = env('CLOUDINARY_CLOUD_NAME');
        $apiKey = env('CLOUDINARY_API_KEY');
        $apiSecret = env('CLOUDINARY_API_SECRET');

        if (!$cloudName || !$apiKey || !$apiSecret) {
            throw new \Exception('Cloudinary configuration is missing.');
        }

        $timestamp = time();
        $folder = 'airfly/recharge-slips';

        // Cloudinary signature
        $stringToSign = 'folder=' . $folder
            . '&timestamp=' . $timestamp;

        $signature = sha1($stringToSign . $apiSecret);

        $url = "https://api.cloudinary.com/v1_1/{$cloudName}/image/upload";

        $response = Http::timeout(60)
            ->attach(
                'file',
                file_get_contents($file->getRealPath()),
                $file->getClientOriginalName()
            )
            ->post($url, [
                'api_key' => $apiKey,
                'timestamp' => $timestamp,
                'folder' => $folder,
                'signature' => $signature,
            ]);

        if (!$response->successful()) {
            throw new \Exception('Cloudinary upload failed.');
        }

        $result = $response->json();

        if (empty($result['secure_url'])) {
            throw new \Exception('Cloudinary did not return a file URL.');
        }

        return $result['secure_url'];
    }

    /**
     * SUBMIT RECHARGE REQUEST
     */
    public function store(Request $request)
    {
        $agent = $request->user();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $validated = $request->validate([
            'amount' => [
                'required',
                'numeric',
                'min:1'
            ],

            'payment_method' => [
                'required',
                'string',
                'max:255'
            ],

            'transaction_id' => [
                'required',
                'string',
                'max:255'
            ],

            'slip' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120'
            ],
        ]);

        // Upload slip if provided
        $slipUrl = null;

        if ($request->hasFile('slip')) {
            try {
                $slipUrl = $this->uploadSlipToCloudinary(
                    $request->file('slip')
                );
            } catch (\Throwable $e) {
                \Log::error('Airfly Cloudinary upload error: ' . $e->getMessage());

                return response()->json([
                    'success' => false,
                    'message' => 'Payment slip upload failed. Please try again.'
                ], 500);
            }
        }

        $recharge = Recharge::create([
            'agent_id' => $agent->id,
            'amount' => $validated['amount'],
            'payment_method' => $validated['payment_method'],
            'transaction_id' => $validated['transaction_id'],
            'slip' => $slipUrl,
            'status' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Recharge Request Submitted Successfully',
            'recharge' => $recharge
        ], 201);
    }

    /**
     * SINGLE AGENT RECHARGE
     */
    public function show(Request $request, $id)
    {
        $agent = $request->user();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated'
            ], 401);
        }

        $recharge = Recharge::where('id', $id)
            ->where('agent_id', $agent->id)
            ->first();

        if (!$recharge) {
            return response()->json([
                'success' => false,
                'message' => 'Recharge not found or unauthorized'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'recharge' => $recharge
        ]);
    }

    /**
     * ADMIN RECHARGE LIST
     */
    public function all()
    {
        $recharges = Recharge::with('agent')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'recharges' => $recharges
        ]);
    }

    /**
     * APPROVE RECHARGE
     */
    public function approve($id)
    {
        return DB::transaction(function () use ($id) {

            $recharge = Recharge::where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$recharge) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recharge request not found'
                ], 404);
            }

            if ($recharge->status !== 'Pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Pending recharge can be approved.',
                    'current_status' => $recharge->status
                ], 422);
            }

            $agent = Agent::where('id', $recharge->agent_id)
                ->lockForUpdate()
                ->first();

            if (!$agent) {
                return response()->json([
                    'success' => false,
                    'message' => 'Agent not found'
                ], 404);
            }

            $agent->wallet = (float) $agent->wallet
                + (float) $recharge->amount;

            $agent->save();

            $recharge->status = 'Approved';
            $recharge->save();

            WalletTransaction::create([
                'agent_id' => $agent->id,
                'type' => 'Credit',
                'amount' => $recharge->amount,
                'balance_after' => $agent->wallet,
                'reference' => 'Recharge-' . $recharge->id,
                'remarks' => 'Wallet Recharge Approved',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recharge Approved Successfully',
                'recharge' => $recharge,
                'wallet_balance' => $agent->wallet,
            ]);
        });
    }

    /**
     * REJECT RECHARGE
     */
    public function reject($id)
    {
        return DB::transaction(function () use ($id) {

            $recharge = Recharge::where('id', $id)
                ->lockForUpdate()
                ->first();

            if (!$recharge) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recharge request not found'
                ], 404);
            }

            if ($recharge->status !== 'Pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only Pending recharge can be rejected.',
                    'current_status' => $recharge->status
                ], 422);
            }

            $recharge->status = 'Rejected';
            $recharge->save();

            return response()->json([
                'success' => true,
                'message' => 'Recharge Rejected Successfully',
                'recharge' => $recharge,
            ]);
        });
    }
}
