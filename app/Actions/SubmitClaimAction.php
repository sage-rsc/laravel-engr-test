<?php

namespace App\Actions;

use App\Models\Batch;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\Insurer;
use App\Services\BatchingService;
use App\Mail\BatchNotificationMail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Lorisleiva\Actions\Concerns\AsAction;

class SubmitClaimAction
{
    use AsAction;

    public function handle(array $data)
    {
        return DB::transaction(function () use ($data) {
            $insurer = Insurer::where('code', $data['insurer_code'])->firstOrFail();

            $claim = Claim::create([
                'insurer_id' => $insurer->id,
                'provider_name' => $data['provider_name'],
                'encounter_date' => $data['encounter_date'],
                'submission_date' => now()->toDateString(),
                'priority_level' => $data['priority_level'],
                'specialty' => $data['specialty'],
                'total_amount' => $data['total_amount'],
            ]);

            foreach ($data['items'] as $itemData) {
                ClaimItem::create([
                    'claim_id' => $claim->id,
                    'item_name' => $itemData['item_name'],
                    'unit_price' => $itemData['unit_price'],
                    'quantity' => $itemData['quantity'],
                    'subtotal' => $itemData['subtotal'],
                ]);
            }

            $batchingService = new BatchingService();
            
            $batchDate = $batchingService->determineBatchDate($claim, $insurer);
            $existingBatch = Batch::where('insurer_id', $insurer->id)
                ->where('provider_name', $data['provider_name'])
                ->whereDate('batch_date', $batchDate->format('Y-m-d'))
                ->where('processed', false)
                ->first();
            
            $previousClaimCount = $existingBatch ? $existingBatch->claim_count : 0;
            
            $batch = $batchingService->assignClaimToBatch($claim);
            $batch->refresh();

            $isNewBatch = $existingBatch === null;
            $reachedMinSize = $this->shouldNotifyInsurer($batch);
            $justReachedMinSize = !$isNewBatch && $previousClaimCount < $insurer->min_batch_size && $reachedMinSize;
            
            if (($isNewBatch && $reachedMinSize) || $justReachedMinSize) {
                Mail::to($insurer->email)->send(new BatchNotificationMail($batch));
            }

            return $claim->load(['items', 'batch', 'insurer']);
        });
    }

    private function shouldNotifyInsurer(Batch $batch): bool
    {
        return $batch->claim_count >= $batch->insurer->min_batch_size;
    }
}
