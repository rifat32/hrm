<?php

namespace App\Http\Utils;

use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessTierModule;
use App\Models\Module;

trait ModuleUtil
{
    // this function do all the task and returns transaction id or -1
    public function isModuleEnabled($module_name)
    {
        $user = auth()->user();
        if (empty($user->business_id)) {
                 return true;
        }



        $query_params = [
            'name' => $module_name,
        ];
        $module = Module::where($query_params)->first();
        if(!$module) {
            return false;
        }
        if(!$module->is_enabled) {
            return false;
        }
        
            $business = Business::find($user->business_id);
            if(!$business) {
                return false;
            }

                $business_tier_id = $business->service_plan?$business->service_plan->business_tier->id:1;







           $is_enabled = false;



           $businessTierModule =    BusinessTierModule::where([
            "business_tier_id" => $business_tier_id,
            "module_id" => $module->id
           ])
           ->first();

           if($businessTierModule){
            $is_enabled = $businessTierModule->is_enabled;
           }



           $businessModule =    BusinessModule::where([
            "business_id" => $business->id,
            "module_id" => $module->id
           ])
           ->first();


           if($businessModule){
            $is_enabled = $businessModule->is_enabled;
           }






        return $is_enabled;

    }





}
