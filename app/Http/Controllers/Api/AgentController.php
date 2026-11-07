<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    /**
     * GET /api/agents
     * Get all agents
     */
    public function index()
    {
        $agents = Agent::latest()->get();

        return response()->json([
            'success' => true,
            'agents' => $agents,
        ]);
    }

    /**
     * GET /api/agents/{id}
     * Get single agent
     */
    public function show($id)
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'agent' => $agent,
        ]);
    }

    /**
     * PUT /api/agents/{id}
     * Update agent and approval status
     */
    public function update(Request $request, $id)
    {
        $agent = Agent::find($id);

        if (!$agent) {
            return response()->json([
                'success' => false,
                'message' => 'Agent not found.',
            ], 404);
        }

        $validated = $request->validate([
            'agency_name' => 'sometimes|required|string|max:255',
            'owner_name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|max:255|unique:agents,email,' . $agent->id,
            'phone' => 'sometimes|required|string|max:30',
            'address' => 'sometimes|required|string',
            'trade_license' => 'sometimes|required|string|max:255|unique:agents,trade_license,' . $agent->id,
            'status' => 'sometimes|required|in:pending,approved,rejected',
        ]);

        // Convert phone to database column mobile
        if (isset($validated['phone'])) {
            $validated['mobile'] = $validated['phone'];
            unset($validated['phone']);
        }

        // Save validated data directly
        $agent->forceFill($validated);
        $agent->save();

        // Reload saved database record
        $agent->refresh();

        return response()->json([
            'success' => true,
            'message' => 'Agent updated successfully.',
            'agent' => $agent,
        ]);
    }
}
