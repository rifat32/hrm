<?php

namespace App\Models;

use App\Http\Utils\DefaultQueryScopesTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TerminationReason extends Model
{
    use HasFactory, DefaultQueryScopesTrait;
    protected $fillable = [
        'name',
        'description',
        "is_active",
        "is_default",
        "business_id",
        "created_by",
        "parent_id",
    ];

    public function terminations()
    {
        return $this->hasMany(Termination::class);
    }

    public function disabled()
    {
        return $this->hasMany(DisabledTerminationReason::class, 'termination_reason_id', 'id');
    }


    public function getIsActiveAttribute($value)
    {

        $is_active = $value;
        $user = auth()->user();

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


}

