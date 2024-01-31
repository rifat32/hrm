<?php

namespace App\Jobs;

use App\Models\Attendance;
use App\Models\AttendanceArrear;
use App\Models\Payrun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
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




        DB::transaction(function () {
            $payruns = Payrun::where('is_active', true)->get();

            foreach ($payruns as $payrun) {

                $start_date = $payrun->start_date;
                $end_date = $payrun->end_date;

                if (!$payrun->business_id) {
                    continue;
                }
                // Set end_date based on period_type
                switch ($payrun->period_type) {
                    case 'weekly':
                        $start_date = Carbon::now()->startOfWeek()->subWeek(1);
                        $end_date = Carbon::now()->startOfWeek();
                        break;
                    case 'monthly':
                        $start_date = Carbon::now()->startOfMonth()->addMonth(1);
                        $end_date = Carbon::now()->startOfMonth();
                        break;
                }
                if (!$start_date || !$end_date) {
                    continue; // Skip to the next iteration
                }

                // Convert end_date to Carbon instance
                $end_date = Carbon::parse($end_date);

                // Check if end_date is today
                if (!$end_date->isToday()) {
                    continue; // Skip to the next iteration
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



                    $attendance_arrears = Attendance::whereDoesntHave("payroll")
                        ->where('attendances.user_id', $employee->id)

                        ->where(function ($query) use ($start_date) {
                            $query->where(function ($query) use ($start_date) {
                                $query->whereNotIn("attendances.status", ["approved"])
                                    ->where('attendances.in_date', '<=', today()->endOfDay())
                                    ->where('attendances.in_date', '>=', $start_date);
                            })
                                ->orWhere(function ($query) use ($start_date) {
                                    $query->whereDoesntHave("arrear")
                                        ->where('attendances.in_date', '<=', $start_date);
                                });
                        })
                        ->get();

                    foreach ($attendance_arrears as $attendance_arrear) {
                        AttendanceArrear::create([
                            "status" => "pending_approval",
                            "attendance_id" => $attendance_arrear->id
                        ]);
                    }

                    $approved_attendances = Attendance::whereDoesntHave("payroll")
                        ->where('attendances.user_id', $employee->id)
                        ->where(function ($query) use ($start_date) {
                            $query->where(function ($query) use ($start_date) {
                                $query
                                ->where("attendances.status", "approved")
                                ->where('attendances.in_date', '<=', today()->endOfDay())
                                ->where('attendances.in_date', '>=', $start_date);
                            })
                                ->orWhere(function ($query) {
                                    $query->whereHas("arrear", function ($query) {
                                        $query->where("attendance_arrears.status", "approved");
                                    });
                                });
                        })
                        ->get();




                }







                // Save the updated payrun
                $payrun->save();
            }
        });
    }
}
