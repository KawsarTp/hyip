<?php

namespace App\Console\Commands;

use App\InvestmentStatus;
use App\Models\Investment;
use App\Services\InvestmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('investments:calculate-returns')]
#[Description('Create idempotent ROI snapshots for due active investments')]
class CalculateInvestmentReturns extends Command
{
    public function handle(InvestmentService $investmentService): int
    {
        $created = 0;

        Investment::query()->where('status', InvestmentStatus::Active)->chunkById(100, function ($investments) use ($investmentService, &$created): void {
            foreach ($investments as $investment) {
                if ($investmentService->calculateDueReturn($investment) !== null) {
                    $created++;
                }
            }
        });

        $this->info("Created {$created} due ROI snapshot(s).");

        return self::SUCCESS;
    }
}
