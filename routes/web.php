<?php

use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('SubmitClaim');
})->name('submit-claim');

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard', [
        'claims' => \App\Models\Claim::with(['insurer', 'batch', 'items'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($claim) {
                return [
                    'id' => $claim->id,
                    'insurer_code' => $claim->insurer->code ?? 'N/A',
                    'insurer_name' => $claim->insurer->name ?? 'N/A',
                    'provider_name' => $claim->provider_name,
                    'encounter_date' => $claim->encounter_date,
                    'submission_date' => $claim->submission_date,
                    'specialty' => $claim->specialty,
                    'priority_level' => $claim->priority_level,
                    'total_amount' => $claim->total_amount,
                    'batch_identifier' => $claim->batch->identifier ?? 'Pending',
                    'item_count' => $claim->items->count(),
                    'created_at' => $claim->created_at->format('M j, Y g:i A'),
                ];
            }),
        'total_claims' => \App\Models\Claim::count(),
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/claims', [ClaimController::class, 'store'])->name('claims.store');
Route::post('/claims/estimate-cost', [ClaimController::class, 'estimateCost'])->name('claims.estimate-cost');
Route::get('/insurers', function () {
    return \App\Models\Insurer::select('id', 'code', 'name')->get();
})->name('insurers.index');

require __DIR__.'/auth.php';
