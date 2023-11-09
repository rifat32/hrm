<?php

namespace App\Http\Utils;


use App\Models\Business;
use App\Models\Department;
use App\Models\User;
use Exception;

trait BusinessUtil
{
    // this function do all the task and returns transaction id or -1



    public function businessOwnerCheck($business_id) {

        $businessQuery  = Business::where(["id" => $business_id]);
        if(!auth()->user()->hasRole('superadmin')) {
            $businessQuery = $businessQuery->where(function ($query) {
                $query->where('created_by', auth()->user()->id)
                      ->orWhere('owner_id', auth()->user()->id);
            });
        }

        $business =  $businessQuery->first();
        if (!$business) {
            return false;
        }
        return $business;
    }



    public function checkManager($id) {
        $manager  = User::where(["id" => $id])->first();
        if (!$manager){
            return [
                "ok" => false,
                "status" => 400,
                "message" => "Manager does not exists."
            ];
        }

        if ($manager->business_id != auth()->user()->business_id) {
            return [
                "ok" => false,
                "status" => 403,
                "message" => "Manager belongs to another business."
            ];
        }
        return [
            "ok" => true,
        ];
    }

    public function checkDepartment($id) {
        $department  = Department::where(["id" => $id])->first();
        if (!$department){
            return [
                "ok" => false,
                "status" => 400,
                "message" => "Department does not exists."
            ];
        }

        if ($department->business_id != auth()->user()->business_id) {
            return [
                "ok" => false,
                "status" => 403,
                "message" => "Department belongs to another business."
            ];
        }
        return [
            "ok" => true,
        ];
    }
}
