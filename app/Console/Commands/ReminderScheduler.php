<?php

namespace App\Console\Commands;

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

     // Define a helper function to resolve class name dynamically
function resolveClassName($className) {
    return "App\\Models\\" . $className; // Assuming your models are stored in the "App\Models" namespace
}

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











        Log::info(($notification_description));
        Log::info(($notification_link));

        $dapartments = Department::whereHas("users", function ($query) use ($user) {
            $query->where("users.id", $user->id);
        })
            ->get();
        $all_parent_department_manager_ids = [];
        foreach ($dapartments as $dapartment) {
            $all_parent_department_manager_ids = array_merge($all_parent_department_manager_ids, $dapartment->getAllParentManagerIds());
        }
        $unique_all_parent_department_manager_ids = array_unique($all_parent_department_manager_ids);

        foreach ($unique_all_parent_department_manager_ids as $manager_id) {
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
        // File::put(storage_path('logs/laravel.log'), '');

        Log::info('reminder is sending...');
        $businesses =  Reminder::groupBy("business_id")->select("business_id")->get();



        foreach ($businesses as $business) {
            $business = Business::where([
                "id" => $business->business_id,
                "is_active" => 1
            ])
                ->first();
            if (!$business) {
                continue;
            }

            $reminders = Reminder::where([
                "business_id" => $business->id
            ])
                ->get();



            foreach ($reminders as $reminder) {


                if ($reminder->duration_unit == "weeks") {
                    $reminder->duration =  $reminder->duration * 7;
                } else if ($reminder->duration_unit == "months") {
                    $reminder->duration =  $reminder->duration * 30;
                }


                $now = Carbon::now();
                $model_name = $reminder->model_name;
                $user_relationship = $reminder->user_relationship;
                $user_eligible_field = $reminder->user_eligible_field;
                $issue_date_column = $reminder->issue_date_column;

                $expiry_date_column = $reminder->expiry_date_column;





                $all_current_data_ids = $this->resolveClassName($model_name)::select('user_id')
                ->where([
                    "business_id" => $business->id
                ])
                ->whereHas($user_relationship, function ($query) use ($user_eligible_field)  {
                    $query->where(("users." . $user_eligible_field),">",0)
                    ->where("is_active",1);
                })
                ->where($issue_date_column, '<', now())
                ->whereNotNull($expiry_date_column)
                ->groupBy('user_id')
                ->get()
                ->map(function ($record) use ($model_name,$issue_date_column, $expiry_date_column)  {

                    $latest_expired_record = $this->resolveClassName($model_name)::where('user_id', $record->user_id)
                    ->where($issue_date_column, '<', now())
                    ->where(function($query) use($expiry_date_column) {
                        $query->whereNotNull($expiry_date_column)
                        ->orWhereNull($expiry_date_column);
                     })
                     ->orderByRaw("ISNULL($expiry_date_column), $expiry_date_column DESC")
                     ->orderBy('id', 'DESC')
                    ->first();


            if($latest_expired_record->expiry_date_column) {
                $current_data = $this->resolveClassName($model_name)::where('user_id', $record->user_id)
                ->where($expiry_date_column, $latest_expired_record->expiry_date_column)
                ->orderByDesc($issue_date_column)
                ->first();
           } else {
              return NULL;
           }

                        return $current_data->id;
                }) ->filter()->values();



                $all_reminder_data = $this->resolveClassName($model_name)::whereIn("id",$all_current_data_ids)
                ->when(($reminder->send_time == "before_expiry"), function ($query) use ($reminder, $expiry_date_column, $now) {
                        return $query->where([
                            ($expiry_date_column) => $now->copy()->addDays($reminder->duration)
                        ]);
                    })
                    ->when(($reminder->send_time == "after_expiry"), function ($query) use ($reminder, $expiry_date_column, $now) {

                        return $query->where(
                            ($expiry_date_column),
                            "<=",
                            $now->copy()->subDays($reminder->duration)
                        );
                    })
                    ->get();








                foreach ($all_reminder_data as $data) {




                    if ($reminder->send_time == "after_expiry") {

                        $reminder_date =   $now->copy()->subDays($reminder->duration);


                        if ($reminder_date->eq($data->$expiry_date_column)) {

                            // send notification or email based on setting
                            // send notification or email based on setting
                            $this->sendNotification($reminder, $data, $business);
                        } else if ($reminder_date->gt($data->$expiry_date_column)) {
                            if ($reminder->keep_sending_until_update == 1 && !empty($reminder->frequency_after_first_reminder)) {

                                $days_difference = $reminder_date->diffInDays($data->$expiry_date_column);
                                if ((($days_difference % $reminder->frequency_after_first_reminder) == 0)) {
                                    // send notification or email based on setting
                                    // send notification or email based on setting
                                    $this->sendNotification($reminder, $data, $business);
                                }
                            }
                        }
                    } else if ($reminder->send_time == "before_expiry") {

                        // send notification or email based on setting
                        $this->sendNotification($reminder, $data, $business);
                    }
                }
            }
        }
        return 0;
    }
}
