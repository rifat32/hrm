<?php

namespace App\Http\Requests;

use App\Models\RecruitmentProcessType;
use Illuminate\Foundation\Http\FormRequest;

class RecruitmentProcessTypeCreateRequest extends FormRequest
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
            'description' => 'nullable|string',
            'name' => [
                "required",
                'string',
                function ($attribute, $value, $fail) {

                        $created_by  = NULL;
                        if(auth()->user()->business) {
                            $created_by = auth()->user()->business->created_by;
                        }

                        $exists = RecruitmentProcessType::where("recruitment_process_types.name",$value)

                        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                            if (auth()->user()->hasRole('superadmin')) {
                                return $query->where('recruitment_process_types.business_id', NULL)
                                    ->where('recruitment_process_types.is_default', 1)
                                    ->where('recruitment_process_types.is_active', 1);

                            } else {
                                return $query->where('recruitment_process_types.business_id', NULL)
                                    ->where('recruitment_process_types.is_default', 1)
                                    ->where('recruitment_process_types.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_recruitment_process_types.created_by", [auth()->user()->id]);
                                    })

                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("recruitment_process_types.id",$value)->where('recruitment_process_types.business_id', NULL)
                                            ->where('recruitment_process_types.is_default', 0)
                                            ->where('recruitment_process_types.created_by', auth()->user()->id)
                                            ->where('recruitment_process_types.is_active', 1);


                                    });
                            }
                        })
                            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                                return $query->where('recruitment_process_types.business_id', NULL)
                                    ->where('recruitment_process_types.is_default', 1)
                                    ->where('recruitment_process_types.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                                        $q->whereIn("disabled_recruitment_process_types.created_by", [$created_by]);
                                    })
                                    ->whereDoesntHave("disabled", function($q)  {
                                        $q->whereIn("disabled_recruitment_process_types.business_id",[auth()->user()->business_id]);
                                    })

                                    ->orWhere(function ($query) use( $created_by, $value){
                                        $query->where("recruitment_process_types.id",$value)->where('recruitment_process_types.business_id', NULL)
                                            ->where('recruitment_process_types.is_default', 0)
                                            ->where('recruitment_process_types.created_by', $created_by)
                                            ->where('recruitment_process_types.is_active', 1)
                                            ->whereDoesntHave("disabled", function($q) {
                                                $q->whereIn("disabled_recruitment_process_types.business_id",[auth()->user()->business_id]);
                                            });
                                    })
                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("recruitment_process_types.id",$value)->where('recruitment_process_types.business_id', auth()->user()->business_id)
                                            ->where('recruitment_process_types.is_default', 0)
                                            ->where('recruitment_process_types.is_active', 1);

                                    });
                            })
                        ->exists();

                    if ($exists) {
                        $fail("$attribute is already exist.");
                    }


                },
            ],
        ];


return $rules;
    }
}
