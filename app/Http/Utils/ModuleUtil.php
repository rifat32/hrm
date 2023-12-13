<?php

namespace App\Http\Utils;

use App\Models\Business;
use App\Models\Module;

trait ModuleUtil
{
    // this function do all the task and returns transaction id or -1
    public function isModuleEnabled($moduleName)
    {
        $user = auth()->user();
        $query_params = [
            'name' => $moduleName,
            'is_default' => 1,
            'business_tier_id' => null,
        ];

        if (!empty($user->business_id)) {
            $business = Business::find($user->business_id);

            if ($business) {
                $query_params["is_default"] = 0;
                $query_params["business_tier_id"] = $business->business_tier->id;
            }
        }

        $module = Module::where($query_params)->first();
        return $module ? $module->is_enabled : false;

    }
}
