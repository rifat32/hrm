<?php

namespace App\Http\Requests;

use App\Models\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;

class EmploymentStatusUpdateRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        $rules = [

            'id' => [
                'required',
                'numeric',
                function ($attribute, $value, $fail) {

                    $employment_status_query_params = [
                        "id" => $this->id,
                    ];
                    $employment_status = EmploymentStatus::where($employment_status_query_params)
                        ->first();
                    if (!$employment_status) {
                            // $fail($attribute . " is invalid.");
                            $fail("no employment statuses found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($employment_status->business_id != NULL || $employment_status->is_default != 1)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this employment statuses due to role restrictions.");

                          }

                        } else {
                            if(($employment_status->business_id != NULL || $employment_status->is_default != 0 || $employment_status->created_by != auth()->user()->id)) {
                                // $fail($attribute . " is invalid.");
                                $fail("You do not have permission to update this employment statuses due to role restrictions.");

                          }
                        }

                    } else {
                        if(($employment_status->business_id != auth()->user()->business_id || $employment_status->is_default != 0)) {
                               // $fail($attribute . " is invalid.");
                            $fail("You do not have permission to update this employment status due to role restrictions.");
                        }
                    }




                },
            ],

            'name' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = EmploymentStatus::where("employment_statuses.name",$value)
                        ->whereNotIn("id",[$this->id])

                        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                            if (auth()->user()->hasRole('superadmin')) {
                                return $query->where('employment_statuses.business_id', NULL)
                                    ->where('employment_statuses.is_default', 1)
                                    ->where('employment_statuses.is_active', 1);

                            } else {
                                return $query->where('employment_statuses.business_id', NULL)
                                    ->where('employment_statuses.is_default', 1)
                                    ->where('employment_statuses.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_employment_statuses.created_by", [auth()->user()->id]);
                                    })

                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', NULL)
                                            ->where('employment_statuses.is_default', 0)
                                            ->where('employment_statuses.created_by', auth()->user()->id)
                                            ->where('employment_statuses.is_active', 1);


                                    });
                            }
                        })
                            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                                return $query->where('employment_statuses.business_id', NULL)
                                    ->where('employment_statuses.is_default', 1)
                                    ->where('employment_statuses.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                                        $q->whereIn("disabled_employment_statuses.created_by", [$created_by]);
                                    })
                                    ->whereDoesntHave("disabled", function($q)  {
                                        $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                                    })

                                    ->orWhere(function ($query) use( $created_by, $value){
                                        $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', NULL)
                                            ->where('employment_statuses.is_default', 0)
                                            ->where('employment_statuses.created_by', $created_by)
                                            ->where('employment_statuses.is_active', 1)
                                            ->whereDoesntHave("disabled", function($q) {
                                                $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
                                            });
                                    })
                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("employment_statuses.id",$value)->where('employment_statuses.business_id', auth()->user()->business_id)
                                            ->where('employment_statuses.is_default', 0)
                                            ->where('employment_statuses.is_active', 1);

                                    });
                            })
                        ->exists();

                    if ($exists) {
                        $fail($attribute . " is already exist.");
                    }


                },
            ],
            'description' => 'nullable|string',
            'color' => 'required|string',
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:employment_statuses,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        // } else {
        //     if(auth()->user()->hasRole('superadmin')){
        //         $rules['name'] .= '|unique:employment_statuses,name,'.$this->id.',id,is_default,' . 1 . ',business_id,' . NULL;
        //     }
        //     else {
        //         $rules['name'] .= '|unique:employment_statuses,name,'.$this->id.',id,is_default,' . 0 . ',business_id,' . NULL;
        //     }

        // }

return $rules;
    }








}
