<?php

namespace App\Http\Controllers;

use App\Actions\SubmitClaimAction;
use App\Http\Requests\EstimateCostRequest;
use App\Http\Requests\StoreClaimRequest;
use App\Services\BatchingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class ClaimController extends Controller
{
    public function store(StoreClaimRequest $request): RedirectResponse
    {
        try {
            $validated = $request->validated();
            $claim = SubmitClaimAction::run($validated);

            Log::info('Claim submitted successfully', [
                'claim_id' => $claim->id,
                'insurer_id' => $claim->insurer_id,
                'batch_id' => $claim->batch_id,
                'total_amount' => $claim->total_amount,
            ]);

            $batchIdentifier = $claim->batch ? $claim->batch->identifier : 'Pending';
            
            return redirect()
                ->route('submit-claim')
                ->with('success', 'Claim submitted successfully! Batch: ' . $batchIdentifier);
        } catch (\Exception $e) {
            Log::error('Failed to submit claim', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->back()
                ->withErrors(['error' => 'Failed to submit claim. Please try again.'])
                ->withInput();
        }
    }

    public function estimateCost(EstimateCostRequest $request)
    {
        try {
            $validated = $request->validated();
            $insurer = \App\Models\Insurer::where('code', $validated['insurer_code'])->firstOrFail();
            
            $batchingService = new BatchingService();
            $estimatedCost = $batchingService->estimateClaimCost(
                $validated['total_amount'],
                $validated['priority_level'],
                $validated['specialty'],
                $validated['encounter_date'],
                $insurer
            );

            return response()->json([
                'estimated_cost' => round($estimatedCost, 2),
                'estimated_cost_formatted' => '$' . number_format($estimatedCost, 2),
            ]);
        } catch (\Exception $e) {
            Log::error('Cost estimation failed', [
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ]);
            
            return response()->json(['error' => 'Unable to estimate cost'], 422);
        }
    }
}
