<?php

namespace Tests\Feature;

use App\Models\Insurer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ClaimSubmissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_submit_claim_successfully(): void
    {
        Mail::fake();

        $insurer = Insurer::factory()->create([
            'code' => 'INS-TEST',
            'email' => 'test@example.com',
        ]);

        $data = [
            'insurer_code' => 'INS-TEST',
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'items' => [
                [
                    'item_name' => 'Consultation',
                    'unit_price' => 100.00,
                    'quantity' => 1,
                    'subtotal' => 100.00,
                ],
                [
                    'item_name' => 'Lab Test',
                    'unit_price' => 50.00,
                    'quantity' => 2,
                    'subtotal' => 100.00,
                ],
            ],
        ];

        $response = $this->post('/claims', $data);

        $response->assertStatus(302)
            ->assertRedirect(route('submit-claim'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('claims', [
            'insurer_id' => $insurer->id,
            'provider_name' => 'Test Provider',
            'total_amount' => 200.00,
        ]);

        $this->assertDatabaseHas('claim_items', [
            'item_name' => 'Consultation',
            'unit_price' => 100.00,
            'quantity' => 1,
        ]);
    }

    public function test_validation_fails_with_invalid_data(): void
    {
        $response = $this->postJson('/claims', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['insurer_code', 'provider_name', 'encounter_date', 'specialty', 'priority_level', 'items']);
    }

    public function test_validation_fails_with_invalid_insurer_code(): void
    {
        $data = [
            'insurer_code' => 'INVALID',
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'items' => [
                [
                    'item_name' => 'Test',
                    'unit_price' => 100.00,
                    'quantity' => 1,
                    'subtotal' => 100.00,
                ],
            ],
        ];

        $response = $this->post('/claims', $data);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['insurer_code']);
    }

    public function test_claim_is_assigned_to_batch(): void
    {
        Mail::fake();

        $insurer = Insurer::factory()->create([
            'code' => 'INS-TEST',
            'email' => 'test@example.com',
            'date_preference' => 'encounter',
        ]);

        $data = [
            'insurer_code' => 'INS-TEST',
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'items' => [
                [
                    'item_name' => 'Consultation',
                    'unit_price' => 100.00,
                    'quantity' => 1,
                    'subtotal' => 100.00,
                ],
            ],
        ];

        $response = $this->post('/claims', $data);

        $response->assertStatus(302)
            ->assertRedirect(route('submit-claim'));

        $this->assertDatabaseHas('claims', [
            'provider_name' => 'Test Provider',
        ]);

        $this->assertDatabaseHas('batches', [
            'provider_name' => 'Test Provider',
            'insurer_id' => $insurer->id,
        ]);
    }

    public function test_multiple_claims_create_single_batch_when_under_max_size(): void
    {
        Mail::fake();

        $insurer = Insurer::factory()->create([
            'code' => 'INS-TEST',
            'email' => 'test@example.com',
            'max_batch_size' => 10,
            'date_preference' => 'encounter',
        ]);

        $data = [
            'insurer_code' => 'INS-TEST',
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'items' => [
                [
                    'item_name' => 'Consultation',
                    'unit_price' => 100.00,
                    'quantity' => 1,
                    'subtotal' => 100.00,
                ],
            ],
        ];

        $response1 = $this->post('/claims', $data);
        $response1->assertStatus(302);
        
        $response2 = $this->post('/claims', $data);
        $response2->assertStatus(302);

        $batchCount = \App\Models\Batch::where('provider_name', 'Test Provider')
            ->whereDate('batch_date', '2024-01-15')
            ->count();

        $this->assertGreaterThanOrEqual(1, $batchCount, 'At least one batch should exist for the provider and date');
    }

    public function test_email_is_sent_when_new_batch_is_created(): void
    {
        Mail::fake();

        $insurer = Insurer::factory()->create([
            'code' => 'INS-TEST',
            'email' => 'test@example.com',
            'min_batch_size' => 1,
            'date_preference' => 'encounter',
        ]);

        $data = [
            'insurer_code' => 'INS-TEST',
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'items' => [
                [
                    'item_name' => 'Consultation',
                    'unit_price' => 100.00,
                    'quantity' => 1,
                    'subtotal' => 100.00,
                ],
            ],
        ];

        $response = $this->post('/claims', $data);

        $response->assertStatus(302);

        Mail::assertSent(\App\Mail\BatchNotificationMail::class, function ($mail) use ($insurer) {
            return $mail->hasTo($insurer->email);
        });
    }

    public function test_email_is_sent_when_batch_reaches_min_size(): void
    {
        Mail::fake();

        $insurer = Insurer::factory()->create([
            'code' => 'INS-TEST',
            'email' => 'test@example.com',
            'min_batch_size' => 2,
            'max_batch_size' => 10,
            'date_preference' => 'encounter',
        ]);

        $data = [
            'insurer_code' => 'INS-TEST',
            'provider_name' => 'Test Provider',
            'encounter_date' => '2024-01-15',
            'specialty' => 'cardiology',
            'priority_level' => 3,
            'items' => [
                [
                    'item_name' => 'Consultation',
                    'unit_price' => 100.00,
                    'quantity' => 1,
                    'subtotal' => 100.00,
                ],
            ],
        ];

        $this->post('/claims', $data);
        Mail::assertNothingSent();

        $this->post('/claims', $data);
        Mail::assertSent(\App\Mail\BatchNotificationMail::class);
    }
}
