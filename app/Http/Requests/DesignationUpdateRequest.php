<?php

namespace App\Http\Requests;

use App\Models\Designation;
use Illuminate\Foundation\Http\FormRequest;

class DesignationUpdateRequest extends BaseFormRequest
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

                    $designation_query_params = [
                        "id" => $this->id,
                    ];
                    $designation = Designation::where($designation_query_params)
                        ->first();
                    if (!$designation) {
                            // $fail("$attribute is invalid.");
                            $fail("no designation found");
                            return 0;

                    }
                    if (empty(auth()->user()->business_id)) {

                        if(auth()->user()->hasRole('superadmin')) {
                            if(($designation->business_id != NULL || $designation->is_default != 1)) {
                                // $fail("$attribute is invalid.");
                                $fail("You do not have permission to update this designation due to role restrictions.");

                          }

                        } else {
                            if(($designation->business_id != NULL || $designation->is_default != 0 || $designation->created_by != auth()->user()->id)) {
                                // $fail("$attribute is invalid.");
                                $fail("You do not have permission to update this designation due to role restrictions.");

                          }
                        }

                    } else {
                        if(($designation->business_id != auth()->user()->business_id || $designation->is_default != 0)) {
                               // $fail("$attribute is invalid.");
                            $fail("You do not have permission to update this designation due to role restrictions.");
                        }
                    }




                },
            ],


            'name' => 'required|string',
            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = Designation::where("designations.name",$value)
                        ->whereNotIn("id",[$this->id])

                        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                            if (auth()->user()->hasRole('superadmin')) {
                                return $query->where('designations.business_id', NULL)
                                    ->where('designations.is_default', 1)
                                    ->where('designations.is_active', 1);

                            } else {
                                return $query->where('designations.business_id', NULL)
                                    ->where('designations.is_default', 1)
                                    ->where('designations.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_designations.created_by", [auth()->user()->id]);
                                    })

                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("designations.id",$value)->where('designations.business_id', NULL)
                                            ->where('designations.is_default', 0)
                                            ->where('designations.created_by', auth()->user()->id)
                                            ->where('designations.is_active', 1);


                                    });
                            }
                        })
                            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                                return $query->where('designations.business_id', NULL)
                                    ->where('designations.is_default', 1)
                                    ->where('designations.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                                        $q->whereIn("disabled_designations.created_by", [$created_by]);
                                    })
                                    ->whereDoesntHave("disabled", function($q)  {
                                        $q->whereIn("disabled_designations.business_id",[auth()->user()->business_id]);
                                    })

                                    ->orWhere(function ($query) use( $created_by, $value){
                                        $query->where("designations.id",$value)->where('designations.business_id', NULL)
                                            ->where('designations.is_default', 0)
                                            ->where('designations.created_by', $created_by)
                                            ->where('designations.is_active', 1)
                                            ->whereDoesntHave("disabled", function($q) {
                                                $q->whereIn("disabled_designations.business_id",[auth()->user()->business_id]);
                                            });
                                    })
                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("designations.id",$value)->where('designations.business_id', auth()->user()->business_id)
                                            ->where('designations.is_default', 0)
                                            ->where('designations.is_active', 1);

                                    });
                            })
                        ->exists();

                    if ($exists) {
                        $fail("$attribute is already exist.");
                    }


                },
            ],
        ];

        // if (!empty(auth()->user()->business_id)) {
        //     $rules['name'] .= '|unique:designations,name,'.$this->id.',id,business_id,' . auth()->user()->business_id;
        // } else {
        //     if(auth()->user()->hasRole('superadmin')){
        //         $rules['name'] .= '|unique:designations,name,'.$this->id.',id,is_default,' . 1 . ',business_id,' . NULL;
        //     }
        //     else {
        //         $rules['name'] .= '|unique:designations,name,'.$this->id.',id,is_default,' . 0 . ',business_id,' . NULL;
        //     }

        // }

return $rules;
    }
}
