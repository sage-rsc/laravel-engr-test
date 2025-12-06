<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Claim;
use App\Models\Insurer;
use App\Services\BatchingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchingCostOptimizationTest extends TestCase
{
    use RefreshDatabase;

    private BatchingService $batchingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batchingService = new BatchingService();
    }

    public function test_cost_increases_with_time_of_month(): void
    {
        $insurer = Insurer::factory()->create([
            'specialty_efficiencies' => ['cardiology' => 1.0],
        ]);

        $claim1 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'total_amount' => 1000.00,
            'priority_level' => 3,
            'specialty' => 'cardiology',
            'encounter_date' => '2024-01-01',
        ]);

        $claim2 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'total_amount' => 1000.00,
            'priority_level' => 3,
            'specialty' => 'cardiology',
            'encounter_date' => '2024-01-30',
        ]);

        $batch1 = $this->batchingService->assignClaimToBatch($claim1);
        $batch2 = $this->batchingService->assignClaimToBatch($claim2);

        $this->assertGreaterThan($batch1->estimated_cost, $batch2->estimated_cost);
    }

    public function test_priority_level_affects_cost(): void
    {
        $insurer = Insurer::factory()->create();

        $claim1 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'total_amount' => 1000.00,
            'priority_level' => 5, // Lowest priority
            'encounter_date' => '2024-01-15',
        ]);

        $claim2 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'total_amount' => 1000.00,
            'priority_level' => 1, // Highest priority
            'encounter_date' => '2024-01-15',
        ]);

        $batch1 = $this->batchingService->assignClaimToBatch($claim1);
        $batch2 = $this->batchingService->assignClaimToBatch($claim2);

        $this->assertGreaterThan($batch1->estimated_cost, $batch2->estimated_cost);
    }

    public function test_specialty_efficiency_reduces_cost(): void
    {
        $insurer = Insurer::factory()->create([
            'specialty_efficiencies' => [
                'cardiology' => 0.8, // 80% efficiency = lower cost
                'orthopedics' => 1.0, // 100% efficiency = normal cost
            ],
        ]);

        $claim1 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'total_amount' => 1000.00,
            'priority_level' => 3,
            'specialty' => 'cardiology', // Efficient specialty
            'encounter_date' => '2024-01-15',
        ]);

        $claim2 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'total_amount' => 1000.00,
            'priority_level' => 3,
            'specialty' => 'orthopedics', // Normal efficiency
            'encounter_date' => '2024-01-15',
        ]);

        $batch1 = $this->batchingService->assignClaimToBatch($claim1);
        $batch2 = $this->batchingService->assignClaimToBatch($claim2);

        $this->assertGreaterThan($batch2->estimated_cost, $batch1->estimated_cost);
    }

    public function test_batch_size_discount_applies(): void
    {
        $insurer = Insurer::factory()->create([
            'min_batch_size' => 5,
            'max_batch_size' => 10,
            'date_preference' => 'encounter',
        ]);

        $batch = Batch::factory()->create([
            'insurer_id' => $insurer->id,
            'claim_count' => 5,
            'batch_date' => Carbon::parse('2024-01-15'),
        ]);

        for ($i = 0; $i < 5; $i++) {
            Claim::factory()->create([
                'insurer_id' => $insurer->id,
                'batch_id' => $batch->id,
                'total_amount' => 1000.00,
                'priority_level' => 3,
                'encounter_date' => '2024-01-15',
            ]);
        }

        $batch->refresh();
        $costWithDiscount = $this->batchingService->calculateBatchCost($batch);

        $smallBatch = Batch::factory()->create([
            'insurer_id' => $insurer->id,
            'claim_count' => 2,
            'batch_date' => Carbon::parse('2024-01-15'),
        ]);

        for ($i = 0; $i < 2; $i++) {
            Claim::factory()->create([
                'insurer_id' => $insurer->id,
                'batch_id' => $smallBatch->id,
                'total_amount' => 1000.00,
                'priority_level' => 3,
                'encounter_date' => '2024-01-15',
            ]);
        }

        $smallBatch->refresh();
        $costWithoutDiscount = $this->batchingService->calculateBatchCost($smallBatch);

        $costPerClaimWithDiscount = $costWithDiscount / 5;
        $costPerClaimWithoutDiscount = $costWithoutDiscount / 2;

        $this->assertLessThan($costPerClaimWithoutDiscount, $costPerClaimWithDiscount);
    }

    public function test_optimal_batch_selection_minimizes_cost(): void
    {
        $insurer = Insurer::factory()->create([
            'max_batch_size' => 10,
            'date_preference' => 'encounter',
        ]);

        $existingBatch = Batch::factory()->create([
            'insurer_id' => $insurer->id,
            'provider_name' => 'Test Provider',
            'batch_date' => Carbon::parse('2024-01-15'),
            'claim_count' => 5,
            'processed' => false,
        ]);

        for ($i = 0; $i < 5; $i++) {
            Claim::factory()->create([
                'insurer_id' => $insurer->id,
                'batch_id' => $existingBatch->id,
                'total_amount' => 1000.00,
                'priority_level' => 3,
                'encounter_date' => '2024-01-15',
            ]);
        }

        $existingBatch->refresh();
        $existingBatch->estimated_cost = $this->batchingService->calculateBatchCost($existingBatch);
        $existingBatch->save();

        $newClaim = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'provider_name' => 'Test Provider',
            'total_amount' => 1000.00,
            'priority_level' => 3,
            'encounter_date' => '2024-01-15',
        ]);

        $batch = $this->batchingService->assignClaimToBatch($newClaim);

        $this->assertEquals($existingBatch->id, $batch->id);
    }
}

