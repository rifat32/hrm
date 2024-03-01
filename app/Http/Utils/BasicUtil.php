<?php

namespace App\Http\Utils;

use App\Models\EmployeePensionHistory;
use App\Models\User;
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

    public function getCurrentPensionHistory(string $modelClass,$session_name ,$current_user_id, $issue_date_column, $expiry_date_column)
    {

        $model = new $modelClass;

        $user = User::where([
            "id" => $current_user_id
        ])
        ->first();

          $current_data = NULL;

        if(!$user->pension_eligible) {
            $current_data = $model::where('user_id', $current_user_id)
            ->where("pension_eligible",0)
            ->latest()->first();
        } else {
            $latest_expired_record = $model::where('user_id', $current_user_id)
            ->where("pension_eligible", 1)
            ->where($issue_date_column, '<', now())
            ->orderByRaw("ISNULL($expiry_date_column), $expiry_date_column DESC")
            ->orderBy('id', 'DESC')
            ->first();

            // $latest_expired_record = $model::where('user_id', $current_user_id)
            // ->where("pension_eligible",1)
            // ->where($issue_date_column, '<', now())
            // ->whereNull($expiry_date_column)
            // ->latest()
            // ->first();
            // if(!$latest_expired_record) {
            //     $latest_expired_record = $model::where('user_id', $current_user_id)
            //     ->where("pension_eligible",1)
            //     ->where($issue_date_column, '<', now())
            //     ->orderByDesc($expiry_date_column)
            //     ->first();
            // }

            if($latest_expired_record) {
                $current_data = $model::where('user_id', $current_user_id)
                ->where($expiry_date_column, $latest_expired_record->expiry_date_column)
                ->orderByDesc($issue_date_column)
                ->first();
            }
        }

        Session::put($session_name, $current_data?$current_data->id:NULL);
        return $current_data;


    }








}
