<?php

namespace App\Http\Utils;

use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessTierModule;
use App\Models\Module;
use App\Models\ServicePlan;
use App\Models\ServicePlanModule;
use Exception;

trait ModuleUtil
{
    // this function do all the task and returns transaction id or -1
    public function isModuleEnabled($module_name,$throwErr = true)
    {
        $user = auth()->user();
        if (empty($user->business_id)) {
            return true;
        }



        $query_params = [
            'name' => $module_name,
        ];
        $module = Module::where($query_params)->first();
        if (empty($module)) {
            throw new Exception('No Module Found', 401);
        }
        if (empty($module->is_enabled)) {

            throw new Exception('Module is not enabled', 401);
        }


        $business = Business::find($user->business_id);
        if (empty($business)) {

            throw new Exception('No Business Found', 401);
        }


        $business_tier_id = $business->service_plan ? $business->service_plan->business_tier->id : 1;


        $is_enabled = false;


        $businessTierModule =    BusinessTierModule::where([
            "business_tier_id" => $business_tier_id,
            "module_id" => $module->id
        ])
            ->first();

        if (!empty($businessTierModule)) {
            $is_enabled = $businessTierModule->is_enabled;
        }



        $servicePlanModule =    ServicePlanModule::where([
            "service_plan_id" => $business->service_plan ? $business->service_plan->id : 0,
            "module_id" => $module->id
        ])
            ->first();


        if (!empty($servicePlanModule)) {
            $is_enabled = $servicePlanModule->is_enabled;
        }



        $businessModule =    BusinessModule::where([
            "business_id" => $business->id,
            "module_id" => $module->id
        ])
            ->first();


        if (!empty($businessModule)) {
            $is_enabled = $businessModule->is_enabled;
        }



        if (!$is_enabled && $throwErr) {
            throw new Exception('Module is not enabled', 401);
        }


        return $is_enabled;
    }


}
