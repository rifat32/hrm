<?php

namespace App\Http\Requests;

use App\Models\EmploymentStatus;
use Illuminate\Foundation\Http\FormRequest;

class EmploymentStatusCreateRequest extends BaseFormRequest
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
            'name' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = EmploymentStatus::where("employment_statuses.name",$value)


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
        //     $rules['name'] .= '|unique:employment_statuses,name,NULL,id,business_id,' . auth()->user()->business_id;
        // } else {
        //     $rules['name'] .= '|unique:employment_statuses,name,NULL,id,is_default,' . (auth()->user()->hasRole('superadmin') ? 1 : 0);
        // }


return $rules;








    }
}
