<?php

namespace App\Http\Utils;

use App\Models\Module;

trait ModuleUtil
{
    // this function do all the task and returns transaction id or -1
    public function isModuleEnabled($moduleName)
    {

        $module = Module::where('name', $moduleName)->first();

        return $module ? $module->is_enabled : false;

    }
}
