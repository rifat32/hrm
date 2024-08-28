<?php

namespace App\Http\Utils;

use App\Models\ActivityLog;
use App\Models\Department;
use App\Models\ErrorLog;
use App\Models\Notification;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait BasicNotificationUtil
{
    use BasicUtil;
    // this function do all the task and returns transaction id or -1
    public function send_notification($data, $user, $title, $type, $entity_name, $all_parent_department_ids = [])
    {



        if ($data instanceof \Illuminate\Support\Collection) {
            // If it's a collection, check if it's empty
            if ($data->isNotEmpty()) {
                // If not empty, take the first element as the entity
                $entity_ids = $data->pluck('id')->toArray();

                $entity = $data->first();

                if($entity_name == "attendance") {
                    $notification_link = ($entity_name) . "/" . implode('_', $entity_ids);
                } else {
                    $notification_link = ($entity_name) . "/" . implode('_', $entity_ids);
                }
            } else {
                // Handle the case where the collection is empty
                return; // or do something else, depending on your requirements
            }
        } else {
            // If it's not a collection, it's assumed to be a single entity
            $entity_ids = [];
            $entity = $data;
            if($entity_name == "attendance") {
                $notification_link = ($entity_name) . "/" . ($entity->id);
            }
            $notification_link = ($entity_name) . "/" . ($entity->id);
        }


        $departments = Department::whereHas("users", function ($query) use ($entity) {
            $query->where("users.id", $entity->user_id);
        })
            ->get();



        $notification_description = '';




        if ($type == "create") {
            $notification_description = (explode('_', $entity_name)[0]) . " taken for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "update") {
            $notification_description = (explode('_', $entity_name)[0]) . " updated for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "approve") {
            $notification_description = (explode('_', $entity_name)[0]) . " approved for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "reject") {
            $notification_description = (explode('_', $entity_name)[0]) . " rejected for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }
        if ($type == "delete") {
            $notification_description = (explode('_', $entity_name)[0]) . " deleted for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
        }





            if (!empty($all_parent_department_ids)) {
                $unique_all_parent_department_manager_ids = $this->get_all_parent_department_manager_ids($all_parent_department_ids);
            } else {
                $all_parent_department_manager_ids = collect([]);
                foreach ($departments as $department) {
                    $all_parent_department_manager_ids->push($department->manager_id);
                    $all_parent_department_manager_ids = $all_parent_department_manager_ids->merge($department->getAllParentManagerIds());
                }
                $unique_all_parent_department_manager_ids = $all_parent_department_manager_ids
                    ->filter() // Removes null values
                    ->unique()
                    ->values(); // Extracts the values from the collection
            }






            // Initialize a collection to hold all notification data
            $notifications = collect();

            foreach ($unique_all_parent_department_manager_ids->all() as $manager_id) {
                $notification = [
                    "entity_id" => $entity->id,
                    "entity_ids" => json_encode($entity_ids),
                    "entity_name" => $entity_name,
                    'notification_title' => $title,
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id,
                    "receiver_id" => $manager_id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now(),
                    "type" => $type
                ];

                // Log each notification data
                Log::info("Notification data for manager {$manager_id}", $notification);

                // Append to notifications collection
                $notifications->push($notification);
            }

            // Log the data before inserting
            Log::info("Inserting notifications", ['insert_data' => $notifications->toArray()]);

            // Perform bulk insertion of notifications
            Notification::insert($notifications->toArray());

    }



    public function send_notification_for_department($data, $user, $title, $type, $entity_name, $all_parent_department_ids = [], $for_department = 0, $department = NULL)
    {


        if ($data instanceof \Illuminate\Support\Collection) {
            // If it's a collection, check if it's empty
            if ($data->isNotEmpty()) {
                // If not empty, take the first element as the entity
                $entity_ids = $data->pluck('id')->toArray();

                $entity = $data->first();
                $notification_link = ($entity_name) . "/" . implode('_', $entity_ids);
            } else {
                // Handle the case where the collection is empty
                return; // or do something else, depending on your requirements
            }
        } else {
            // If it's not a collection, it's assumed to be a single entity
            $entity_ids = [];
            $entity = $data;
            $notification_link = "/holiday/holiday-request/?enc_id=" . base64_encode($entity->id);
        }






        $notification_description = '';



        if (!$for_department && !empty($user)) {
            if ($type == "create") {
                $notification_description = (explode('_', $entity_name)[0]) . " taken for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
           else if ($type == "update") {
                $notification_description = (explode('_', $entity_name)[0]) . " updated for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
           else if ($type == "approve") {
                $notification_description = (explode('_', $entity_name)[0]) . " approved for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
           else if ($type == "reject") {
                $notification_description = (explode('_', $entity_name)[0]) . " rejected for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
          else  if ($type == "delete") {
                $notification_description = (explode('_', $entity_name)[0]) . " deleted for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
            else {
                $notification_description = (explode('_', $entity_name)[0]) . " status for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
        } else if(!empty($department)) {
            if ($type == "create") {
                $notification_description = (explode('_', $entity_name)[0]) . " taken for the department " . ($department->name);
            }
            else if ($type == "update") {
                $notification_description = (explode('_', $entity_name)[0]) . " updated for the department " . ($department->name);
            }
            else  if ($type == "approve") {
                $notification_description = (explode('_', $entity_name)[0]) . " approved for the department " . ($department->name);
            }
            else  if ($type == "reject") {
                $notification_description = (explode('_', $entity_name)[0]) . " rejected for the department " . ($department->name);
            }
            else   if ($type == "delete") {
                $notification_description = (explode('_', $entity_name)[0]) . " deleted for the department " . ($department->name);
            }
            else {
                $notification_description = (explode('_', $entity_name)[0]) . " status for the department " . ($department->name);
            }
        }


        $receiver_id = !empty($user) ? $user->id : auth()->user()->business->owner_id;

                $notification = [
                    "entity_id" => $entity->id,
                    "entity_ids" => json_encode($entity_ids),
                    "entity_name" => $entity_name,
                    'notification_title' => $title,
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id,
                    "receiver_id" => $receiver_id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now(),
                    "type" => $type,
                ];

                // Log each notification data
                Log::info("Notification data", $notification);






        // Log the data before inserting
        Log::info("Inserting notifications", ['insert_data' => $notification]);

        // Perform bulk insertion of notifications
        Notification::insert($notification);
    }

    public function send_notification_for_department_self($data, $title, $type, $entity_name, $all_parent_department_ids = [])
    {

$user = auth()->user();

        if ($data instanceof \Illuminate\Support\Collection) {
            // If it's a collection, check if it's empty
            if ($data->isNotEmpty()) {
                // If not empty, take the first element as the entity
                $entity_ids = $data->pluck('id')->toArray();

                $entity = $data->first();
                $notification_link = ($entity_name) . "/" . implode('_', $entity_ids);
            } else {
                // Handle the case where the collection is empty
                return; // or do something else, depending on your requirements
            }
        } else {
            // If it's not a collection, it's assumed to be a single entity
            $entity_ids = [];
            $entity = $data;
            $notification_link = "/holiday/holiday-request/?enc_id=" . base64_encode($entity->id);
        }


        $departments = Department::whereHas("users", function ($query) use ($entity) {
            $query->where("users.id", auth()->user()->id);
        })
            ->get();



        $notification_description = '';




            if ($type == "create") {
                $notification_description = (explode('_', $entity_name)[0]) . " taken for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
           else if ($type == "update") {
                $notification_description = (explode('_', $entity_name)[0]) . " updated for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
           else if ($type == "approve") {
                $notification_description = (explode('_', $entity_name)[0]) . " approved for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
           else if ($type == "reject") {
                $notification_description = (explode('_', $entity_name)[0]) . " rejected for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
          else  if ($type == "delete") {
                $notification_description = (explode('_', $entity_name)[0]) . " deleted for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }
            else {
                $notification_description = (explode('_', $entity_name)[0]) . " status for the user " . ($user->first_Name . " " . $user->middle_Name . " " . $user->last_Name);
            }



        if (!empty($all_parent_department_ids)) {
            $unique_all_parent_department_manager_ids = $this->get_all_parent_department_manager_ids($all_parent_department_ids);
        } else {
            $all_parent_department_manager_ids = collect([]);
            foreach ($departments as $department) {
                $all_parent_department_manager_ids->push($department->manager_id);
                $all_parent_department_manager_ids = $all_parent_department_manager_ids->merge($department->getAllParentManagerIds());
            }
            $unique_all_parent_department_manager_ids = $all_parent_department_manager_ids
                ->filter() // Removes null values
                ->unique()
                ->values(); // Extracts the values from the collection
        }






        // Initialize a collection to hold all notification data
        $notifications = collect();

        foreach ($unique_all_parent_department_manager_ids->all() as $manager_id) {
            $notification = [
                "entity_id" => $entity->id,
                "entity_ids" => json_encode($entity_ids),
                "entity_name" => $entity_name,
                'notification_title' => $title,
                'notification_description' => $notification_description,
                'notification_link' => $notification_link,
                "sender_id" => auth()->user()->id,
                "receiver_id" => $manager_id,
                "business_id" => auth()->user()->business_id,
                "is_system_generated" => 1,
                "status" => "unread",
                "created_at" => now(),
                "updated_at" => now(),
                "type" => $type
            ];

            // Log each notification data
            Log::info("Notification data for manager {$manager_id}", $notification);

            // Append to notifications collection
            $notifications->push($notification);
        }

        // Log the data before inserting
        Log::info("Inserting notifications", ['insert_data' => $notifications->toArray()]);

        // Perform bulk insertion of notifications
        Notification::insert($notifications->toArray());


















    }




}
