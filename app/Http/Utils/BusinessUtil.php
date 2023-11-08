<?php

namespace App\Http\Utils;


use App\Models\Business;

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





}
