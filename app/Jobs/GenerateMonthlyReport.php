<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\ReportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateMonthlyReport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        protected string $month
    ) {}

    public function handle(ReportService $reportService): void
    {
        $users = User::where('status', 'active')->get();

        foreach ($users as $user) {
            $reportService->generateMonthlyReport($user, $this->month);
        }
    }
}
