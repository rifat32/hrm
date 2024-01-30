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

    public function sendNotification($reminder, $data, $business)
    {

        $user = User::where([
            "id" => $data->user_id
        ])
            ->first();

        $dapartments = Department::whereHas("users", function ($query) use ($user) {
            $query->where("users.id", $user->id);
        })
            ->get();
        $manager_ids = $dapartments->pluck("manager_id");



        $field_name = $reminder->db_field_name;
        $now = now();
        $days_difference = $now->diffInDays($data->$field_name);

        if ($reminder->send_time == "after_expiry") {

            $notification_description =   (explode('_', $reminder->entity_name)[0]) . " expired " . (abs($days_difference)) . " days ago. Please renew it now.";
            $notification_link = ($reminder->entity_name) . "/" . ($data->id);
        } else {
            $notification_description =    (explode('_', $reminder->entity_name)[0]) .  " expires in " . (abs($days_difference)) . " days. Renew now.";
            $notification_link = ($reminder->entity_name) . "/" . ($data->id);
        }










        Log::info("33");

        Log::info(($notification_description));
        Log::info(($notification_link));

        foreach ($manager_ids as $manager_id) {
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
        Notification::create([
            "entity_id" => $data->id,
            "entity_name" => $reminder->entity_name,
            'notification_title' => $reminder->title,
            'notification_description' => $notification_description,
            'notification_link' => $notification_link,
            "sender_id" => 1,
            "receiver_id" => $business->owner_id,
            "business_id" => $business->id,
            "is_system_generated" => 1,

            "status" => "unread",
        ]);
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



                $table_name = $reminder->db_table_name;
                $field_name = $reminder->db_field_name;

                $now = Carbon::now();



                $all_reminder_data = DB::table($table_name)
                    ->where([
                        "business_id" => $business->id
                    ])
                    ->when(($reminder->send_time == "before_expiry"), function ($query) use ($reminder, $field_name, $now) {


                        return $query->where([
                            ($field_name) => $now->copy()->addDays($reminder->duration)
                        ]);
                    })
                    ->when(($reminder->send_time == "after_expiry"), function ($query) use ($reminder, $field_name, $now) {

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
                            // send notification or email based on setting
                            $this->sendNotification($reminder, $data, $business);
                        } else if ($reminder_date->gt($data->$field_name)) {
                            if ($reminder->keep_sending_until_update == 1 && !empty($reminder->frequency_after_first_reminder)) {

                                $days_difference = $reminder_date->diffInDays($data->$field_name);
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
