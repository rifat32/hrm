<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Reminder;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReminderScheduler extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reminder:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'send reminder';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Log::info('Task executed.');
        $business_ids =  Reminder::groupBy("business_id")->select("business_id")->get();
        foreach ($business_ids as $business_id) {
            $business = Business::where([
                "id" => $business_id,
                "is_active" => 1
            ])
                ->first();
            if (!$business) {
                continue;
            }
            $reminders = Reminder::where([
                "business_id" => $business_id
            ])
                ->get();

            foreach ($reminders as $reminder) {



                if ($reminder->duration_unit == "weeks") {
                    $reminder->duration =  $reminder->duration * 7;
                } else if ($reminder->duration_unit == "months") {
                    $reminder->duration =  $reminder->duration * 30;
                }



                $table_name = $reminder->db_table_name;
                $field_name = $reminder->db_field_name;

                $now = Carbon::now();

                $all_reminder_data = DB::table($table_name)
                    ->where([
                        "business_id" => $business_id
                    ])
                    ->when(($reminder->send_time == "before_expiry"), function ($query) use ($reminder, $field_name,$now) {


                        return $query->where([
                            ($field_name) => $now->copy()->addDays($reminder->duration)
                        ]);
                    })
                    ->when(($reminder->send_time == "after_expiry"), function ($query) use ($reminder, $field_name,$now) {

                        return $query->where(
                            ($field_name),
                            "<=",
                            $now->copy()->subDays($reminder->duration)
                        );
                    })
                    ->get();

                foreach ($all_reminder_data as $data) {

                    if ($reminder->send_time == "after_expiry") {

                        $reminder_date =   $now->copy()->subDays($reminder->duration);


                        if ($reminder_date->eq($data->$field_name)) {

                            // send notification or email based on setting

                        } else if ($reminder_date->gt($data->$field_name)) {
                            if ($reminder->keep_sending_until_update == 1 && !empty($reminder->frequency_after_first_reminder)) {

                                $days_difference = $reminder_date->diffInDays($data->$field_name);
                                if ((($days_difference % $reminder->frequency_after_first_reminder) == 0)) {
                                    // send notification or email based on setting
                                }
                            }
                        }
                    } else if ($reminder->send_time == "before_expiry") {

                        // send notification or email based on setting

                    }
                }
            }
        }
        return 0;
    }
}
