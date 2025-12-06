<?php

namespace Tests\Feature;

use App\Models\Batch;
use App\Models\Claim;
use App\Models\Insurer;
use App\Services\BatchingService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private BatchingService $batchingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->batchingService = new BatchingService();
    }

    public function test_assigns_claim_to_new_batch(): void
    {
        $insurer = Insurer::factory()->create([
            'date_preference' => 'encounter',
        ]);

        $claim = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
            'submission_date' => '2024-01-16',
            'total_amount' => 100.00,
        ]);

        $batch = $this->batchingService->assignClaimToBatch($claim);

        $this->assertInstanceOf(Batch::class, $batch);
        $this->assertEquals($insurer->id, $batch->insurer_id);
        $this->assertEquals('Test Provider', $batch->provider_name);
        $this->assertEquals(1, $batch->claim_count);
        $this->assertEquals(100.00, $batch->total_amount);
    }

    public function test_uses_encounter_date_when_preference_is_encounter(): void
    {
        $insurer = Insurer::factory()->create([
            'date_preference' => 'encounter',
        ]);

        $claim = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'encounter_date' => '2024-01-15',
            'submission_date' => '2024-01-20',
        ]);

        $batch = $this->batchingService->assignClaimToBatch($claim);

        $this->assertEquals('2024-01-15', $batch->batch_date->format('Y-m-d'));
    }

    public function test_uses_submission_date_when_preference_is_submission(): void
    {
        $insurer = Insurer::factory()->create([
            'date_preference' => 'submission',
        ]);

        $claim = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'encounter_date' => '2024-01-15',
            'submission_date' => '2024-01-20',
        ]);

        $batch = $this->batchingService->assignClaimToBatch($claim);

        $this->assertEquals('2024-01-20', $batch->batch_date->format('Y-m-d'));
    }

    public function test_creates_new_batch_when_existing_is_at_max_capacity(): void
    {
        $insurer = Insurer::factory()->create([
            'max_batch_size' => 2,
            'date_preference' => 'encounter',
        ]);

        $claim1 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
        ]);

        $claim2 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
        ]);

        $claim3 = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
        ]);

        $batch1 = $this->batchingService->assignClaimToBatch($claim1);
        $batch2 = $this->batchingService->assignClaimToBatch($claim2);
        $batch3 = $this->batchingService->assignClaimToBatch($claim3);

        $this->assertEquals($batch1->id, $batch2->id);
        $this->assertNotEquals($batch1->id, $batch3->id);
    }

    public function test_calculates_batch_cost_correctly(): void
    {
        $insurer = Insurer::factory()->create([
            'specialty_efficiencies' => ['cardiology' => 0.8],
        ]);

        $batch = Batch::factory()->create([
            'insurer_id' => $insurer->id,
            'batch_date' => Carbon::parse('2024-01-15'),
        ]);

        $claim = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'batch_id' => $batch->id,
            'total_amount' => 1000.00,
            'priority_level' => 3,
            'specialty' => 'cardiology',
        ]);

        $batch->refresh();
        $cost = $this->batchingService->calculateBatchCost($batch);

        $this->assertGreaterThan(0, $cost);
    }

    public function test_generates_correct_batch_identifier(): void
    {
        $insurer = Insurer::factory()->create();

        $claim = Claim::factory()->create([
            'insurer_id' => $insurer->id,
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
        ]);

        $batch = $this->batchingService->assignClaimToBatch($claim);

        $this->assertStringContainsString('Test Provider', $batch->identifier);
        $this->assertNotEmpty($batch->identifier);
        $this->assertIsString($batch->identifier);
    }
}
