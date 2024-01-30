<?php

namespace App\Jobs;

use App\Models\Payrun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PayrunJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $payruns = Payrun::where('is_active', true)->get();

        foreach ($payruns as $payrun) {

            if (!$payrun->business_id) {
                continue;
            }
            // Set end_date based on period_type
            switch ($payrun->period_type) {
                case 'weekly':

                    $payrun->end_date = Carbon::now()->startOfWeek();
                    break;
                case 'monthly':
                    $payrun->end_date = Carbon::now()->startOfMonth();;
                    break;
                    // Add additional cases for other period types if needed
            }
            if (!$payrun->end_date) {
                continue;
            }
            if (!$payrun->end_date->isToday()) {
                continue;
            }

            $employees = User::where([
                "business_id" => $payrun->business_id,
                "is_active" => 1
            ])
                ->get();

            foreach ($employees as $employee) {
                $salary_per_annum = $employee->salary_per_annum; // in euros
                $weekly_contractual_hours = $employee->weekly_contractual_hours;
                $weeksPerYear = 52;
                $hourly_salary = $salary_per_annum / ($weeksPerYear * $weekly_contractual_hours);
            }







            // Save the updated payrun
            $payrun->save();
        }
    }
}
