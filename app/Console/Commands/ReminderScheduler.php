<?php

namespace App\Console\Commands;

use App\Http\Utils\BasicUtil;
use App\Models\Business;
use App\Models\Department;
use App\Models\Notification;
use App\Models\NotificationTemplate;
use App\Models\Reminder;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class ReminderScheduler extends Command
{
    use BasicUtil;
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


    public function sendNotification($reminder, $data, $business)
    {


        $user = User::where([
            "id" => $data->user_id
        ])
            ->first();




        $expiry_date_column = $reminder->expiry_date_column;

        $now = now();
        $days_difference = $now->diffInDays($data->$expiry_date_column);

        if ($reminder->send_time == "after_expiry") {

            $notification_description =   (explode('_', $reminder->entity_name)[0]) . " expired " . (abs($days_difference)) . " days ago. Please renew it now.";
            $notification_link = ($reminder->entity_name) . "/" . ($data->id);
        } else {
            $notification_description =    (explode('_', $reminder->entity_name)[0]) .  " expires in " . (abs($days_difference)) . " days. Renew now.";
            $notification_link = ($reminder->entity_name) . "/" . ($data->id);
        }














        // Get all parent department IDs of the employee
        $all_parent_departments_manager_ids = $this->all_parent_departments_manager_of_user($user->id, $user->business_id);





        foreach ($all_parent_departments_manager_ids as $manager_id) {
            Notification::create([
                "entity_id" => $data->id,
                "entity_name" => $reminder->entity_name,
                'notification_title' => $reminder->title,
                'notification_description' => $notification_description,
                'notification_link' => $notification_link,
                "sender_id" => 1,
                "receiver_id" => $manager_id,
                "business_id" => $business->id,

                "is_system_generated" => 1,
                "status" => "unread",
            ]);
        }
    }


    public function handle()
    {
        Log::info('Reminder process started.');
        // Log that the reminder process has started


        // Retrieve distinct business IDs for reminders
        $businesses =  Reminder::groupBy("business_id")->select("business_id")->get();


        // Iterate over each business
        foreach ($businesses as $business) {

            $business = Business::where([
                "id" => $business->business_id,
                "is_active" => 1
            ])
                ->first();

            // Skip iteration if business not found or inactive
            if (empty($business)) {
                // Log that the business is not found or inactive

                continue;
            }

            // Retrieve reminders for the current business
            $reminders = Reminder::where([
                "business_id" => $business->id
            ])
                ->get();


            // Iterate over each reminder
            foreach ($reminders as $reminder) {


                // Adjust reminder duration if necessary
                if ($reminder->duration_unit == "weeks") {
                    $reminder->duration =  $reminder->duration * 7;
                } else if ($reminder->duration_unit == "months") {
                    $reminder->duration =  $reminder->duration * 30;
                }

                // Get current timestamp
                $now = Carbon::now();
                $model_name = $reminder->model_name;
                $user_relationship = $reminder->user_relationship;
                $user_eligible_field = $reminder->user_eligible_field;
                $issue_date_column = $reminder->issue_date_column;

                $expiry_date_column = $reminder->expiry_date_column;



                // Handle different model types differently
                if ($model_name == "EmployeePensionHistory") {

                    // Handle reminders for EmployeePensionHistory model
                    $all_current_data_ids = $this->resolveClassName($model_name)::select('id','user_id')
                        ->where([
                            "business_id" => $business->id
                        ])
                        ->whereHas($user_relationship, function ($query) use ($user_eligible_field) {
                            $query->where(("users." . $user_eligible_field), ">", 0)
                                ->where("is_active", 1);
                        })
                        ->where($issue_date_column, '<', now())
                        ->whereNotNull($expiry_date_column)
                        ->groupBy('user_id')
                        ->get()
                        ->map(function ($record) use ($model_name, $issue_date_column, $user_eligible_field) {

                            $current_data = $this->resolveClassName($model_name)::where('user_id', $record->user_id)
                                ->where($user_eligible_field, 1)
                                ->where($issue_date_column, '<', now())
                                ->orderByDesc("id")
                                ->first();

                            if (empty($current_data)) {
                                return NULL;
                            }


                            return $current_data->id;
                        })->filter()->values();



                    $all_reminder_data = $this->resolveClassName($model_name)::whereIn("id", $all_current_data_ids)
                        ->when(($reminder->send_time == "before_expiry"), function ($query) use ($reminder, $expiry_date_column, $now) {

                            return $query->where(
                                ($expiry_date_column),
                                "<=",
                                $now->copy()->addDays($reminder->duration)
                            );


                        })
                        ->when(($reminder->send_time == "after_expiry"), function ($query) use ($reminder, $expiry_date_column, $now) {

                            return $query->where(
                                ($expiry_date_column),
                                "<=",
                                $now->copy()->subDays($reminder->duration)
                            );
                        })
                        ->get();
                } else {
                    // Handle reminders for other models
                    $all_current_data_ids = $this->resolveClassName($model_name)::select('user_id')
                        ->where([
                            "business_id" => $business->id
                        ])
                        ->whereHas($user_relationship, function ($query) use ($user_eligible_field) {
                            $query->where(("users." . $user_eligible_field), ">", 0)
                                ->where("is_active", 1);
                        })
                        ->where($issue_date_column, '<', now())
                        ->groupBy('user_id')
                        ->get()
                        ->map(function ($record) use ($issue_date_column, $expiry_date_column, $model_name) {

                            $latest_expired_record = $this->resolveClassName($model_name)::where('user_id', $record->user_id)
                                ->where($issue_date_column, '<', now())
                                ->orderByDesc($expiry_date_column)
                                // ->latest()
                                ->first();

                            if ($latest_expired_record) {
                                $current_data = $this->resolveClassName($model_name)::where('user_id', $record->user_id)
                                    ->where($expiry_date_column, $latest_expired_record[$expiry_date_column])
                                    ->where($issue_date_column, '<', now())
                                    ->orderByDesc('id')
                                    // ->orderByDesc($issue_date_column)
                                    ->first();
                            } else {
                                return NULL;
                            }
                            return $current_data ? $current_data->id : NULL;
                        })
                        ->filter()->values();

                    $all_reminder_data = $this->resolveClassName($model_name)::whereIn("id", $all_current_data_ids)
                        ->when(($reminder->send_time == "before_expiry"), function ($query) use ($reminder, $expiry_date_column, $now) {
                            return $query->where(
                                ($expiry_date_column),
                                "<=",
                                $now->copy()->addDays($reminder->duration)
                            );
                        })
                        ->when(($reminder->send_time == "after_expiry"), function ($query) use ($reminder, $expiry_date_column, $now) {

                            return $query->where(
                                ($expiry_date_column),
                                "<=",
                                $now->copy()->subDays($reminder->duration)
                            );
                        })
                        ->get();
                }



                // Iterate over all reminder data
                foreach ($all_reminder_data as $data) {






                    // Check if reminder should be sent after expiry
                    if ($reminder->send_time == "after_expiry") {

                        // Calculate the reminder date based on the duration set
                        $reminder_date =   $now->copy()->subDays($reminder->duration);

                        // Check if the reminder date matches the expiry date
                        if ($reminder_date->eq($data->$expiry_date_column)) {
                            // send notification or email based on setting
                            $this->sendNotification($reminder, $data, $business);
                        } else if ($reminder_date->gt($data->$expiry_date_column)) {

                            // Check if the reminder should keep sending until updated and if a frequency is set
                            if (!empty($reminder->frequency_after_first_reminder)) {

                                // Calculate the difference in days between reminder date and expiry date
                                $days_difference = $reminder_date->diffInDays($data->$expiry_date_column);

                                // Calculate the modulo once
                                $is_frequency_met = ($days_difference % $reminder->frequency_after_first_reminder) == 0;

                                if ($reminder->keep_sending_until_update) {
                                    // Check if the difference in days is a multiple of the set frequency
                                    if ($is_frequency_met) {
                                        // send notification or email based on setting
                                        $this->sendNotification($reminder, $data, $business);
                                    }
                                } else {

                                    if ($is_frequency_met && (($days_difference / $reminder->frequency_after_first_reminder) <= $reminder->reminder_limit)) {
                                        // send notification or email based on setting
                                        $this->sendNotification($reminder, $data, $business);
                                    }
                                }
                            }
                        }
                    } else if ($reminder->send_time == "before_expiry") {



                            // Calculate the reminder date based on the duration set
                            // $reminder_date =   $now->copy()->addDays($reminder->duration);
                            $reminder_date =   Carbon::parse($data->$expiry_date_column)->subDays($reminder->duration);


                            // Check if the reminder date matches the expiry date
                            if ($reminder_date->eq($now)) {
                                // send notification or email based on setting
                                $this->sendNotification($reminder, $data, $business);
                            } else if ($reminder_date->lt($now)) {

                                // Check if the reminder should keep sending until updated and if a frequency is set
                                if (!empty($reminder->frequency_after_first_reminder)) {

                                    // Calculate the difference in days between reminder date and expiry date
                                    $days_difference = $reminder_date->diffInDays($now);

                                    // Calculate the modulo once
                                    $is_frequency_met = ($days_difference % $reminder->frequency_after_first_reminder) == 0;

                                    if ($reminder->keep_sending_until_update) {
                                        // Check if the difference in days is a multiple of the set frequency
                                        if ($is_frequency_met) {
                                            // send notification or email based on setting
                                            $this->sendNotification($reminder, $data, $business);
                                        }
                                    } else {

                                        if ($is_frequency_met && (($days_difference / $reminder->frequency_after_first_reminder) <= $reminder->reminder_limit)) {
                                            // send notification or email based on setting
                                            $this->sendNotification($reminder, $data, $business);
                                        }
                                    }
                                }
                            }




                    }
                }
            }
        }

        // Log that the reminder process has finished
        Log::info('Reminder process finished.');
        return 0;
    }
}
