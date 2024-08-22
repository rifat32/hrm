<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'color',
        'description',
        "is_active",
        "is_default",
        "business_id",
        "project_id",
        "order_no",
        "created_by"
    ];
    public function getOrderNoAttribute($value)
    {

      $task_category_order = TaskCategoryOrder::where([
        "task_category_id" => $this->id,
        'project_id'  => request()->project_id,
        "business_id"  => auth()->user()->business_id

        ])->first();

        if (!empty($task_category_order)) {
            return $task_category_order->order_no;
        }


        return $value;
    }

    public function disabled()
    {
        return $this->hasMany(DisabledTaskCategory::class, 'task_category_id', 'id');
    }

    public function order()
    {
        return $this->hasMany(TaskCategoryOrder::class, 'task_category_id', 'id');
    }


    public function getIsActiveAttribute($value)
    {

        $is_active = $value;
        $user = auth()->user();

        if($user){
            if(empty($user->business_id)) {
                if(empty($this->business_id) && $this->is_default == 1) {
                    if(!$user->hasRole("superadmin")) {
                        $disabled = $this->disabled()->where([
                            "created_by" => $user->id
                       ])
                       ->first();
                       if($disabled) {
                          $is_active = 0;
                       }
                    }
                   }


            } else {

                if(empty($this->business_id)) {
                 $disabled = $this->disabled()->where([
                      "business_id" => $user->business_id
                 ])
                 ->first();
                 if($disabled) {
                    $is_active = 0;
                 }

                }


            }

        }





        return $is_active;
    }

    public function getIsDefaultAttribute($value)
    {

        $is_default = $value;
        $user = auth()->user();

        if(!empty($user)) {

            if(!empty($user->business_id)) {
                if(empty($this->business_id) || $user->business_id !=  $this->business_id) {
                      $is_default = 1;
                   }

            } else if($user->hasRole("superadmin")) {
                $is_default = 0;
            }
        }

        return $is_default;
    }



    public function tasks() {
        return $this->hasMany(Task::class, 'task_category_id', 'id');
    }

    // public function getCreatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
    // public function getUpdatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
}
