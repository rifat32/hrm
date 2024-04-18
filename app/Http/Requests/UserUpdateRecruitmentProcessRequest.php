<?php

namespace App\Http\Requests;

use App\Http\Utils\BasicUtil;
use App\Models\RecruitmentProcess;
use App\Rules\ValidUserId;
use Illuminate\Foundation\Http\FormRequest;

class UserUpdateRecruitmentProcessRequest extends FormRequest
{
    use BasicUtil;
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
        $all_manager_department_ids = $this->get_all_departments_of_manager();
        return [
            'id' => [
                "required",
                "numeric",
                new ValidUserId($all_manager_department_ids),
            ],
            'recruitment_processes' => "present|array",
            'recruitment_processes.*.recruitment_process_id' => [
                "required",
                'numeric',
                function ($attribute, $value, $fail) {

                    $created_by  = NULL;
                    if(auth()->user()->business) {
                        $created_by = auth()->user()->business->created_by;
                    }

                    $exists = RecruitmentProcess::where("recruitment_processes.id",$value)
                    ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                        if (auth()->user()->hasRole('superadmin')) {
                            return $query->where('recruitment_processes.business_id', NULL)
                                ->where('recruitment_processes.is_default', 1)
                                ->where('recruitment_processes.is_active', 1);

                        } else {
                            return $query->where('recruitment_processes.business_id', NULL)
                                ->where('recruitment_processes.is_default', 1)
                                ->where('recruitment_processes.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) {
                                    $q->whereIn("disabled_recruitment_processes.created_by", [auth()->user()->id]);
                                })

                                ->orWhere(function ($query) use($value)  {
                                    $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', NULL)
                                        ->where('recruitment_processes.is_default', 0)
                                        ->where('recruitment_processes.created_by', auth()->user()->id)
                                        ->where('recruitment_processes.is_active', 1);


                                });
                        }
                    })
                        ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                            return $query->where('recruitment_processes.business_id', NULL)
                                ->where('recruitment_processes.is_default', 1)
                                ->where('recruitment_processes.is_active', 1)
                                ->whereDoesntHave("disabled", function($q) use($created_by) {
                                    $q->whereIn("disabled_recruitment_processes.created_by", [$created_by]);
                                })
                                ->whereDoesntHave("disabled", function($q)  {
                                    $q->whereIn("disabled_recruitment_processes.business_id",[auth()->user()->business_id]);
                                })

                                ->orWhere(function ($query) use( $created_by, $value){
                                    $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', NULL)
                                        ->where('recruitment_processes.is_default', 0)
                                        ->where('recruitment_processes.created_by', $created_by)
                                        ->where('recruitment_processes.is_active', 1)
                                        ->whereDoesntHave("disabled", function($q) {
                                            $q->whereIn("disabled_recruitment_processes.business_id",[auth()->user()->business_id]);
                                        });
                                })
                                ->orWhere(function ($query) use($value)  {
                                    $query->where("recruitment_processes.id",$value)->where('recruitment_processes.business_id', auth()->user()->business_id)
                                        ->where('recruitment_processes.is_default', 0)
                                        ->where('recruitment_processes.is_active', 1);

                                });
                        })
                    ->exists();

                if (!$exists) {
                    $fail($attribute . " is invalid.");
                }


                },
            ],
            'recruitment_processes.*.description' => "nullable|string",
            'recruitment_processes.*.attachments' => "present|array",
        ];
    }
}
