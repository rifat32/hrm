<?php

namespace App\Http\Utils;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Session;

trait BasicUtil
{
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

    public function getCurrentHistory(string $modelClass,$session_name ,$current_user_id, $all_manager_department_ids, $issue_date, $expiry_date)
    {
    $model = new $modelClass;
       $current_data = $model::where('user_id', $current_user_id)
       ->where("business_id",auth()->user()->business_id)
        ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
            $query->whereIn("departments.id",$all_manager_department_ids);
         })
        ->where($expiry_date, function ($query) use ( $expiry_date) {
            $query->max($expiry_date);
        })
        ->where($issue_date, function ($query) use ($issue_date) {
            $query->where($issue_date, '<', now())
                ->max($issue_date);
        })
        ->orderByDesc($issue_date)
        ->first();

        Session::put($session_name, $current_data?$current_data->id:NULL);
        return $current_data;

    }

    public function getHistoryQuery(string $modelClass, $user_ids, $all_manager_department_ids, $issue_date, $expiry_date,$status_column='',$status_value='')
    {
    $model = new $modelClass;
       $query = $model::whereIn('user_id', $user_ids)
       ->where("business_id",auth()->user()->business_id)
       ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
           $query->whereIn("departments.id",$all_manager_department_ids);
        })
       ->where($expiry_date, function ($query) use($expiry_date) {
           $query->max($expiry_date);
       })
       ->where($issue_date, function ($query) use ($issue_date)  {
           $query->where($issue_date, '<', now())
               ->max($issue_date);
       })
       ->when(!empty($status_column), function($query) use ($status_column,$status_value) {
        $query->where($status_column, $status_value);
       })

       ->orderByDesc($issue_date)
       ->groupBy('user_id');


        return $query;

    }







}
