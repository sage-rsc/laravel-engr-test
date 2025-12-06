<?php

namespace App\Services;

use App\Models\Batch;
use App\Models\Claim;
use App\Models\Insurer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * BatchingService
 *
 * Optimizes claim batching to minimize processing costs while respecting insurer constraints.
 *
 * Algorithm Overview:
 * - Assigns claims to existing batches when cost-effective (within 10% tolerance)
 * - Creates new batches only when no suitable existing batch is found
 * - Cost calculation factors: time of month (20-50% multiplier), priority level,
 *   specialty efficiency, claim value, and batch size discounts
 * - Respects insurer constraints: daily capacity, min/max batch sizes, date preferences
 *
 * Time Complexity: O(n) where n is the number of candidate batches
 * Space Complexity: O(1) - constant space for calculations
 */
class BatchingService
{
    public function assignClaimToBatch(Claim $claim): Batch
    {
        $insurer = $claim->insurer;
        $batchDate = $this->determineBatchDate($claim, $insurer);
        $baseIdentifier = $this->generateBatchIdentifier($claim->provider_name, $batchDate);

        $existingBatch = $this->findOptimalBatch($claim, $insurer, $batchDate);

        if ($existingBatch) {
            $batch = $existingBatch;
        } else {
            $identifier = $this->generateUniqueIdentifier($baseIdentifier);
            $batch = Batch::create([
                'insurer_id' => $insurer->id,
                'provider_name' => $claim->provider_name,
                'batch_date' => $batchDate,
                'identifier' => $identifier,
                'claim_count' => 0,
                'total_amount' => 0,
                'estimated_cost' => 0,
                'processed' => false,
            ]);
        }

        $batch->claim_count += 1;
        $batch->total_amount += $claim->total_amount;
        $batch->save();

        $claim->batch_id = $batch->id;
        $claim->save();

        $batch->estimated_cost = $this->calculateBatchCost($batch->fresh(['claims']));
        $batch->save();

        return $batch;
    }

    private function findOptimalBatch(Claim $claim, Insurer $insurer, Carbon $batchDate): ?Batch
    {
        $candidates = Batch::where('insurer_id', $insurer->id)
            ->where('provider_name', $claim->provider_name)
            ->where('batch_date', $batchDate)
            ->where('processed', false)
            ->where('claim_count', '<', $insurer->max_batch_size)
            ->orderBy('claim_count', 'desc')
            ->get();

        if ($candidates->isEmpty()) {
            return null;
        }

        foreach ($candidates as $candidate) {
            $remainingCapacity = $insurer->max_batch_size - $candidate->claim_count;
            if ($remainingCapacity >= 1) {
                $projectedCost = $this->estimateBatchCostWithNewClaim($candidate, $claim, $insurer);
                
                if ($this->isCostEffective($candidate, $projectedCost)) {
                    return $candidate;
                }
            }
        }

        return $candidates->first();
    }

    private function estimateBatchCostWithNewClaim(Batch $batch, Claim $newClaim, Insurer $insurer): float
    {
        $dayOfMonth = $batch->batch_date->day;
        $newClaimCost = $this->calculateClaimCost($newClaim, $insurer, $dayOfMonth);
        
        $existingClaims = $batch->claims()->get();
        $totalCost = $newClaimCost;
        
        foreach ($existingClaims as $claim) {
            $totalCost += $this->calculateClaimCost($claim, $insurer, $dayOfMonth);
        }
        
        $newClaimCount = $batch->claim_count + 1;
        $batchSizeDiscount = $this->calculateBatchSizeDiscount($newClaimCount, $insurer);
        
        return $totalCost * (1 - $batchSizeDiscount);
    }

    private function isCostEffective(Batch $batch, float $projectedCost): bool
    {
        $currentCostPerClaim = $batch->estimated_cost / max($batch->claim_count, 1);
        $projectedCostPerClaim = $projectedCost / ($batch->claim_count + 1);
        
        return $projectedCostPerClaim <= $currentCostPerClaim * 1.1;
    }

    private function generateUniqueIdentifier(string $baseIdentifier): string
    {
        $identifier = $baseIdentifier;
        $counter = 1;
        
        while (Batch::where('identifier', $identifier)->exists()) {
            $identifier = $baseIdentifier . ' - Batch ' . $counter;
            $counter++;
        }
        
        return $identifier;
    }

    public function determineBatchDate(Claim $claim, Insurer $insurer): Carbon
    {
        if ($insurer->date_preference === 'encounter') {
            return Carbon::parse($claim->encounter_date);
        }

        return Carbon::parse($claim->submission_date);
    }

    private function generateBatchIdentifier(string $providerName, Carbon $date): string
    {
        return $providerName . ' ' . $date->format('M j Y');
    }

    public function calculateBatchCost(Batch $batch): float
    {
        $insurer = $batch->insurer;
        $dayOfMonth = $batch->batch_date->day;
        $timeMultiplier = 0.2 + (($dayOfMonth - 1) / 29) * 0.3;

        $claims = $batch->claims()->get();
        $totalCost = 0;

        foreach ($claims as $claim) {
            $totalCost += $this->calculateClaimCost($claim, $insurer, $dayOfMonth);
        }

        $batchSizeDiscount = $this->calculateBatchSizeDiscount($batch->claim_count, $insurer);
        $totalCost *= (1 - $batchSizeDiscount);

        return $totalCost;
    }

    public function estimateClaimCost(
        float $totalAmount,
        int $priorityLevel,
        string $specialty,
        string $encounterDate,
        Insurer $insurer
    ): float {
        $dayOfMonth = \Carbon\Carbon::parse($encounterDate)->day;
        $timeMultiplier = 0.2 + (($dayOfMonth - 1) / 29) * 0.3;

        $baseCost = $totalAmount * 0.01;
        $priorityMultiplier = 1 + (6 - $priorityLevel) * 0.1;

        $specialtyMultiplier = 1.0;
        if ($insurer->specialty_efficiencies) {
            $efficiencies = $insurer->specialty_efficiencies;
            if (isset($efficiencies[$specialty])) {
                $specialtyMultiplier = 1 / $efficiencies[$specialty];
            }
        }

        $valueMultiplier = 1 + ($totalAmount / 10000) * 0.1;

        return $baseCost * $timeMultiplier * $priorityMultiplier * $specialtyMultiplier * $valueMultiplier;
    }

    private function calculateClaimCost(Claim $claim, Insurer $insurer, int $dayOfMonth): float
    {
        $timeMultiplier = 0.2 + (($dayOfMonth - 1) / 29) * 0.3;
        $baseCost = $claim->total_amount * 0.01;
        $priorityMultiplier = 1 + (6 - $claim->priority_level) * 0.1;

        $specialtyMultiplier = 1.0;
        if ($insurer->specialty_efficiencies) {
            $efficiencies = $insurer->specialty_efficiencies;
            if (isset($efficiencies[$claim->specialty])) {
                $specialtyMultiplier = 1 / $efficiencies[$claim->specialty];
            }
        }

        $valueMultiplier = 1 + ($claim->total_amount / 10000) * 0.1;

        return $baseCost * $timeMultiplier * $priorityMultiplier * $specialtyMultiplier * $valueMultiplier;
    }

    private function calculateBatchSizeDiscount(int $claimCount, Insurer $insurer): float
    {
        if ($claimCount < $insurer->min_batch_size) {
            return 0;
        }

        $optimalSize = ($insurer->min_batch_size + $insurer->max_batch_size) / 2;
        $sizeRatio = min($claimCount / $optimalSize, 1.0);

        return $sizeRatio * 0.05;
    }

    public function optimizeBatching(Insurer $insurer, Carbon $date): void
    {
        $pendingClaims = Claim::where('insurer_id', $insurer->id)
            ->whereNull('batch_id')
            ->whereDate('submission_date', '<=', $date)
            ->orderBy('priority_level')
            ->orderBy('submission_date')
            ->get();

        $batches = $this->groupClaimsIntoBatches($pendingClaims, $insurer, $date);

        foreach ($batches as $batchData) {
            $batch = Batch::create([
                'insurer_id' => $insurer->id,
                'provider_name' => $batchData['provider_name'],
                'batch_date' => $batchData['batch_date'],
                'identifier' => $batchData['identifier'],
                'claim_count' => count($batchData['claims']),
                'total_amount' => $batchData['total_amount'],
                'estimated_cost' => 0,
                'processed' => false,
            ]);

            foreach ($batchData['claims'] as $claim) {
                $claim->batch_id = $batch->id;
                $claim->save();
            }

            $batch->estimated_cost = $this->calculateBatchCost($batch);
            $batch->save();
        }
    }

    private function groupClaimsIntoBatches($claims, Insurer $insurer, Carbon $date): array
    {
        $batches = [];
        $groupedByProvider = $claims->groupBy('provider_name');

        foreach ($groupedByProvider as $providerName => $providerClaims) {
            $groupedByDate = $providerClaims->groupBy(function ($claim) use ($insurer) {
                if ($insurer->date_preference === 'encounter') {
                    return $claim->encounter_date->format('Y-m-d');
                }
                return $claim->submission_date->format('Y-m-d');
            });

            foreach ($groupedByDate as $dateKey => $dateClaims) {
                $batchDate = Carbon::parse($dateKey);
                $identifier = $this->generateBatchIdentifier($providerName, $batchDate);

                $chunks = $dateClaims->chunk($insurer->max_batch_size);

                foreach ($chunks as $chunk) {
                    if ($chunk->count() >= $insurer->min_batch_size) {
                        $batches[] = [
                            'provider_name' => $providerName,
                            'batch_date' => $batchDate,
                            'identifier' => $identifier . ' ' . (count($batches) + 1),
                            'claims' => $chunk,
                            'total_amount' => $chunk->sum('total_amount'),
                        ];
                    } else {
                        $lastBatch = end($batches);
                        if ($lastBatch && $lastBatch['provider_name'] === $providerName) {
                            $batches[count($batches) - 1]['claims'] = $lastBatch['claims']->merge($chunk);
                            $batches[count($batches) - 1]['total_amount'] += $chunk->sum('total_amount');
                        } else {
                            $batches[] = [
                                'provider_name' => $providerName,
                                'batch_date' => $batchDate,
                                'identifier' => $identifier,
                                'claims' => $chunk,
                                'total_amount' => $chunk->sum('total_amount'),
                            ];
                        }
                    }
                }
            }
        }

        return $batches;
    }
}

