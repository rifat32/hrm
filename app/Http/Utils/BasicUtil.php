<?php

namespace App\Http\Utils;

use App\Models\Business;
use App\Models\Department;
use App\Models\EmployeePensionHistory;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\File;

trait BasicUtil
{

      // Define a helper function to resolve class name dynamically
      function resolveClassName($className)
      {
          return "App\\Models\\" . $className; // Assuming your models are stored in the "App\Models" namespace
      }

    // this function do all the task and returns transaction id or -1

    public function fieldsHaveChanged($fields_to_check, $entity1, $entity2, $date_fields) {
        foreach ($fields_to_check as $field) {
            $value1 = $entity1->$field;
            $value2 = $entity2[$field];

            // Additional formatting if needed
            if (in_array($field, $date_fields)) {
                $value1 = (new Carbon($value1))->format('Y-m-d');
                $value2 = (new Carbon($value2))->format('Y-m-d');
            }

            if ($value1 !== $value2) {
                return true;
            }
        }
        return false;
    }

    public function getCurrentPensionHistory(string $modelClass,$session_name ,$current_user_id, $issue_date_column, $expiry_date_column)
    {
        $model = new $modelClass;

        $user = User::where([
            "id" => $current_user_id
        ])
        ->first();
        if(!$user) {
            return NULL;
          }
          $current_data = NULL;
        if(!$user->pension_eligible) {
            $current_data = $model::where('user_id', $current_user_id)
            ->where("pension_eligible",0)
            ->latest()->first();
        } else {
            $current_data = $model::where('user_id', $current_user_id)
            ->where("pension_eligible", 1)
            ->where($issue_date_column, '<', now())
                ->orderByDesc("id")
                ->first();
        }

        Session::put($session_name, $current_data?$current_data->id:NULL);
        return $current_data;


    }

    public function getCurrentHistory(string $modelClass,$session_name ,$current_user_id, $issue_date_column, $expiry_date_column)
    {

        $model = new $modelClass;

        $user = User::where([
            "id" => $current_user_id
        ])
        ->first();

        if(!$user) {
            return NULL;
          }

        $current_data = NULL;

           $latest_expired_record = $model::where('user_id', $current_user_id)
            ->where($issue_date_column, '<', now())
            ->orderBy($expiry_date_column, 'DESC')
            ->first();


            if($latest_expired_record) {
                $current_data = $model::where('user_id', $current_user_id)
                ->where($issue_date_column, '<', now())
                ->where($expiry_date_column, $latest_expired_record[$expiry_date_column])
                ->orderByDesc($issue_date_column)
                ->orderByDesc("id")
                ->first();
            }


        Session::put($session_name, $current_data?$current_data->id:NULL);
        return $current_data;


    }



    public function get_all_departments_of_manager() {
        $all_manager_department_ids = [];
        $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
        foreach ($manager_departments as $manager_department) {
            $all_manager_department_ids[] = $manager_department->id;
            $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
        }
        return $all_manager_department_ids;
    }


    public function all_parent_departments_of_user($user_id) {
        $all_parent_department_ids = [];
        $assigned_departments = Department::whereHas("users", function ($query) use ($user_id) {
            $query->where("users.id", $user_id);
        })->limit(1)->get();


        foreach ($assigned_departments as $assigned_department) {
            array_push($all_parent_department_ids, $assigned_department->id);
            $all_parent_department_ids = array_merge($all_parent_department_ids, $assigned_department->getAllParentIds());
        }

        return array_unique($all_parent_department_ids);
    }

    public function all_parent_departments_manager_of_user($user_id,$business_id) {
        $all_parent_department_manager_ids = [];
        $assigned_departments = Department::whereHas("users", function ($query) use ($user_id) {
            $query->where("users.id", $user_id);
        })->limit(1)->get();


        foreach ($assigned_departments as $assigned_department) {
            array_push($all_parent_department_manager_ids, $assigned_department->manager_id);
            $all_parent_department_manager_ids = array_merge($all_parent_department_manager_ids, $assigned_department->getAllParentDepartmentManagerIds($business_id));
        }

        // Remove null values and then remove duplicates
    $all_parent_department_manager_ids = array_unique(array_filter($all_parent_department_manager_ids, function($value) {
        return !is_null($value);
    }));

    return $all_parent_department_manager_ids;
    }






public function log($data) {
   Log::info(json_encode($data));
}

public function getUserByIdUtil($id,$all_manager_department_ids) {
  $user =  User::with("roles")
    ->where([
        "id" => $id
    ])

    ->when(!auth()->user()->hasRole('superadmin'), function ($query) use($all_manager_department_ids)  {
        return $query->where(function ($query) use($all_manager_department_ids){
            return  $query->where('created_by', auth()->user()->id)
                ->orWhere('id', auth()->user()->id)
                ->orWhere('business_id', auth()->user()->business_id)
                ->orWhereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                });
        });
    })
    ->first();
    if (!$user) {
        throw new Exception("no user found",404);

    }
    return $user;
}


public function retrieveData($query,$orderByField){
  $data =  $query->when(!empty(request()->order_by) && in_array(strtoupper(request()->order_by), ['ASC', 'DESC']), function ($query) use ($orderByField)  {
        return $query->orderBy($orderByField, request()->order_by);
    }, function ($query) use ($orderByField) {
        return $query->orderBy($orderByField, "DESC");
    })
    ->when(!empty(request()->per_page), function ($query)  {
        return $query->paginate(request()->per_page);
    }, function ($query) {
        return $query->get();
    });
    return $data;
}



public function generateUniqueId($relationModel,$relationModelId,$mainModel,$unique_identifier_column = ""){

    $relation = $this->resolveClassName($relationModel)::where(["id" => $relationModelId])->first();


    $prefix = "";
    if ($relation) {
        preg_match_all('/\b\w/', $relation->name, $matches);

        $prefix = implode('', array_map(function ($match) {
            return strtoupper($match[0]);
        }, $matches[0]));

        // If you want to get only the first two letters from each word:
        $prefix = substr($prefix, 0, 2 * count($matches[0]));
    }

    $current_number = 1; // Start from 0001

    do {
        $unique_identifier = $prefix . "-" . str_pad($current_number, 4, '0', STR_PAD_LEFT);
        $current_number++; // Increment the current number for the next iteration
    } while (
        $this->resolveClassName($mainModel)::where([
            ($unique_identifier_column?$unique_identifier_column:"unique_identifier") => $unique_identifier,
            "business_id" => auth()->user()->business_id
        ])->exists()
    );
return $unique_identifier;


}






public function moveUploadedFiles($files,$location) {
    $temporary_files_location =  config("setup-config.temporary_files_location");

    foreach($files as $temp_file_path) {
        if (File::exists(public_path($temp_file_path))) {

            // Move the file from the temporary location to the permanent location
            File::move(public_path($temp_file_path), public_path(str_replace($temporary_files_location, $location, $temp_file_path)));
        } else {

            // throw new Exception(("no file exists"));
            // Handle the case where the file does not exist (e.g., log an error or take appropriate action)
        }
    }

}





}
