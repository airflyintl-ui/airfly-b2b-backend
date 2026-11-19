<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WalletController extends Controller
{
    /**
     * AGENT WALLET STATEMENT
     * Agent can see only their own transactions.
     */
    public function statement(Request $request)
    {
        $agent = $request->user();

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        $statement = WalletTransaction::where('agent_id', $agent->id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'agent_id' => $agent->id,
            'wallet' => $agent->wallet,
            'statement' => $statement,
        ]);
    }

    /**
     * ADMIN: ALL AGENTS WALLET LIST
     */
    public function adminIndex()
    {
        $agents = Agent::select(
            'id',
            'agency_name',
            'owner_name',
            'email',
            'mobile',
            'wallet',
            'status'
        )
        ->orderByDesc('id')
        ->paginate(20);

        return response()->json([
            'success' => true,
            'agents' => $agents,
        ]);
    }

    /**
     * ADMIN: SINGLE AGENT WALLET HISTORY
     */
    public function adminStatement($id)
    {
        $agent = Agent::findOrFail($id);

        $transactions = WalletTransaction::where('agent_id', $agent->id)
            ->latest()
            ->paginate(30);

        return response()->json([
            'success' => true,
            'agent' => [
                'id' => $agent->id,
                'agency_name' => $agent->agency_name,
                'owner_name' => $agent->owner_name,
                'wallet' => $agent->wallet,
            ],
            'transactions' => $transactions,
        ]);
    }

    /**
     * ADMIN: ADD MONEY OR DEDUCT MONEY
     */
    public function adminAdjust(Request $request, $id)
    {
        $validated = $request->validate([
            'type' => 'required|in:Credit,Debit',
            'amount' => [
                'required',
                'regex:/^\d+(\.\d{1,2})?$/',
            ],
            'remarks' => 'nullable|string|max:255',
            'reference' => 'nullable|string|max:255',
        ]);

        if ($validated['type'] === 'Debit' &&
            empty(trim($validated['remarks'] ?? ''))) {
            throw ValidationException::withMessages([
                'remarks' => ['Debit করার কারণ লিখতে হবে।'],
            ]);
        }

        $amount = $this->toCents($validated['amount']);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Amount অবশ্যই শূন্যের বেশি হতে হবে।'],
            ]);
        }

        $result = DB::transaction(function () use (
            $id,
            $validated,
            $amount
        ) {
            // Lock agent row to prevent simultaneous balance changes.
            $agent = Agent::where('id', $id)
                ->lockForUpdate()
                ->firstOrFail();

            $currentBalance = $this->toCents(
                (string) $agent->wallet
            );

            if ($validated['type'] === 'Credit') {
                $newBalance = $currentBalance + $amount;
                $remarks = 'Admin Added Money';
            } else {
                if ($currentBalance < $amount) {
                    throw ValidationException::withMessages([
                        'amount' => [
                            'Insufficient wallet balance. টাকা কাটার জন্য পর্যাপ্ত ব্যালেন্স নেই।',
                        ],
                    ]);
                }

                $newBalance = $currentBalance - $amount;
                $remarks = trim($validated['remarks']);
            }

            $agent->wallet = $this->fromCents($newBalance);
            $agent->save();

            $transaction = WalletTransaction::create([
                'agent_id' => $agent->id,
                'type' => $validated['type'],
                'amount' => $this->fromCents($amount),
                'balance_after' => $this->fromCents($newBalance),
                'reference' => $validated['reference'] ?? null,
                'remarks' => $remarks,
            ]);

            return [
                'agent_id' => $agent->id,
                'agency_name' => $agent->agency_name,
                'type' => $validated['type'],
                'amount' => $this->fromCents($amount),
                'wallet' => $this->fromCents($newBalance),
                'transaction' => $transaction,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => $validated['type'] === 'Credit'
                ? 'Money added successfully.'
                : 'Money deducted successfully.',
            'data' => $result,
        ]);
    }

    /**
     * Convert decimal money string to integer paisa/cents.
     */
    private function toCents(string $amount): int
    {
        $parts = explode('.', $amount, 2);

        $whole = (int) $parts[0];
        $fraction = str_pad($parts[1] ?? '', 2, '0');

        return ($whole * 100) + (int) substr($fraction, 0, 2);
    }

    /**
     * Convert integer paisa/cents to decimal money string.
     */
    private function fromCents(int $amount): string
    {
        return intdiv($amount, 100)
            . '.'
            . str_pad((string) ($amount % 100), 2, '0', STR_PAD_LEFT);
    }
}
