<?php

namespace App\Http\Requests;

use App\Models\Bank;
use Illuminate\Foundation\Http\FormRequest;

class BankCreateRequest extends BaseFormRequest
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

                        $exists = Bank::where("banks.name",$value)

                        ->when(empty(auth()->user()->business_id), function ($query) use ( $created_by, $value) {
                            if (auth()->user()->hasRole('superadmin')) {
                                return $query->where('banks.business_id', NULL)
                                    ->where('banks.is_default', 1)
                                    ->where('banks.is_active', 1);

                            } else {
                                return $query->where('banks.business_id', NULL)
                                    ->where('banks.is_default', 1)
                                    ->where('banks.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) {
                                        $q->whereIn("disabled_banks.created_by", [auth()->user()->id]);
                                    })

                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("banks.id",$value)->where('banks.business_id', NULL)
                                            ->where('banks.is_default', 0)
                                            ->where('banks.created_by', auth()->user()->id)
                                            ->where('banks.is_active', 1);


                                    });
                            }
                        })
                            ->when(!empty(auth()->user()->business_id), function ($query) use ($created_by, $value) {
                                return $query->where('banks.business_id', NULL)
                                    ->where('banks.is_default', 1)
                                    ->where('banks.is_active', 1)
                                    ->whereDoesntHave("disabled", function($q) use($created_by) {
                                        $q->whereIn("disabled_banks.created_by", [$created_by]);
                                    })
                                    ->whereDoesntHave("disabled", function($q)  {
                                        $q->whereIn("disabled_banks.business_id",[auth()->user()->business_id]);
                                    })

                                    ->orWhere(function ($query) use( $created_by, $value){
                                        $query->where("banks.id",$value)->where('banks.business_id', NULL)
                                            ->where('banks.is_default', 0)
                                            ->where('banks.created_by', $created_by)
                                            ->where('banks.is_active', 1)
                                            ->whereDoesntHave("disabled", function($q) {
                                                $q->whereIn("disabled_banks.business_id",[auth()->user()->business_id]);
                                            });
                                    })
                                    ->orWhere(function ($query) use($value)  {
                                        $query->where("banks.id",$value)->where('banks.business_id', auth()->user()->business_id)
                                            ->where('banks.is_default', 0)
                                            ->where('banks.is_active', 1);

                                    });
                            })
                        ->exists();

                    if ($exists) {
                        $fail($attribute . " is already exist.");
                    }


                },
            ],
        ];



return $rules;

    }
}
