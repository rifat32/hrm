<?php

namespace App\Http\Utils;

use App\Models\ActivityLog;
use App\Models\EmployeeAddressHistory;

use App\Models\EmployeePassportDetailHistory;

use App\Models\EmployeePensionHistory;
use App\Models\EmployeeProjectHistory;

use App\Models\EmployeeRightToWorkHistory;

use App\Models\EmployeeSponsorshipHistory;
use App\Models\EmployeeUserWorkShiftHistory;
use App\Models\EmployeeVisaDetailHistory;

use App\Models\Project;
use App\Models\UserWorkShift;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

trait UserDetailsUtil
{
    use BasicUtil;





    public function store_work_shift($request_data,$user) {
        if (!empty($request_data["work_shift_id"])) {
            $work_shift =  WorkShift::where([
                "id" => $request_data["work_shift_id"],

            ])
                ->first();
            if (!$work_shift) {
                throw new Exception("Work shift validation failed");
            }
            if (!$work_shift->is_active) {

                throw new Exception(("Please activate the work shift named '" . $work_shift->name . "'"), 400);
                // return response()->json(["message" => ("Please activate the work shift named '" . $work_shift->name . "'")], 400);
            }
            $work_shift->users()->attach($user->id);


            $employee_work_shift_history_data["work_shift_id"] = $work_shift->id;

            $work_shift_history =  WorkShiftHistory::where([
                "to_date" => NULL,
                "work_shift_id" => $work_shift->id
            ])
                ->first();
            if (!$work_shift_history) {
                throw new Exception("Now work shift history found");
            }

            $work_shift_history->users()->attach($user->id, ['from_date' => auth()->user()->business->start_date, 'to_date' => NULL]);
        } else {
            $default_work_shift = WorkShift::where([
                "business_id" => auth()->user()->business_id,
                "is_business_default" => 1
            ])
                ->first();
            if (!$default_work_shift) {
                throw new Exception("There is no default workshift for this business");
            }

            if (!$default_work_shift->is_active) {
                $this->storeError(
                    ("Please activate the work shift named '" . $default_work_shift->name . "'"),
                    400,
                    "front end error",
                    "front end error"
                );
                $error =  [
                    "message" => ("Please activate the work shift named '" . $default_work_shift->name . "'"),

                ];
                throw new Exception(json_encode($error), 400);
                // return response()->json(["message" => ("Please activate the work shift named '" . $default_work_shift->name . "'")], 400);
            }

            $default_work_shift->users()->attach($user->id);
            $employee_work_shift_history_data["work_shift_id"] = $default_work_shift->id;

            $work_shift_history =  WorkShiftHistory::where([
                "to_date" => NULL,
                "work_shift_id" => $default_work_shift->id
            ])
                ->first();
            if (!$work_shift_history) {
                throw new Exception("Now work shift history found");
            }

            $work_shift_history->users()->attach($user->id, ['from_date' => auth()->user()->business->start_date, 'to_date' => NULL]);
        }
    }


    public function delete_old_histories() {
        $ten_years_ago = Carbon::now()->subYears(10);
           EmployeePensionHistory::where('pension_re_enrollment_due_date', '<=', $ten_years_ago)->delete();
           $ten_years_ago = Carbon::now()->subYears(10);
            EmployeeRightToWorkHistory::where('right_to_work_expiry_date', '<=', $ten_years_ago)->delete();
            $ten_years_ago = Carbon::now()->subYears(10);
            EmployeeVisaDetailHistory::where('visa_expiry_date', '<=', $ten_years_ago)->delete();
            $ten_years_ago = Carbon::now()->subYears(10);
            EmployeePassportDetailHistory::where('passport_expiry_date', '<=', $ten_years_ago)->delete();
            $ten_years_ago = Carbon::now()->subYears(10);
            EmployeeSponsorshipHistory::where('expiry_date', '<=', $ten_years_ago)->delete();

    }

    public function store_right_to_works($request_data,$user) {
        if (!empty($request_data["right_to_works"]) && $request_data["is_active_right_to_works"]) {
            $request_data["right_to_works"]["user_id"] = $user->id;
            $request_data["right_to_works"]["business_id"] = $user->business_id;

            $request_data["right_to_works"]["from_date"] = now();

            EmployeeRightToWorkHistory::create($request_data["right_to_works"]);



        }
    }


    public function store_visa_details($request_data,$user) {
        if (!empty($request_data["visa_details"]) && $request_data["is_active_visa_details"]) {
            $request_data["visa_details"]["user_id"] = $user->id;
            $request_data["visa_details"]["business_id"] = $user->business_id;


            $request_data["visa_details"]["from_date"] = now();
            $employee_visa_details_history  =  EmployeeVisaDetailHistory::create($request_data["visa_details"]);


        }
    }




    public function store_passport_details($request_data,$user) {
        if (!empty($request_data["passport_details"])) {
            $request_data["passport_details"]["user_id"] = $user->id;
            $request_data["passport_details"]["business_id"] = $user->business_id;

            $request_data["passport_details"]["from_date"] = now();

            $employee_passport_details_history  =  EmployeePassportDetailHistory::create($request_data["passport_details"]);


        }
    }


    public function store_sponsorship_details($request_data,$user) {
        if (!empty($request_data["sponsorship_details"])) {
            $request_data["sponsorship_details"]["user_id"] = $user->id;
            $request_data["sponsorship_details"]["business_id"] = $user->business_id;


            $request_data["sponsorship_details"]["from_date"] = now();

            $employee_sponsorship_history  =  EmployeeSponsorshipHistory::create($request_data["sponsorship_details"]);


        }
    }


     public function store_recruitment_processes($request_data,$user) {
        if (!empty($request_data["recruitment_processes"]) && !empty($request_data["recruitment_processes"]["description"])) {
            $user->recruitment_processes()->createMany($request_data["recruitment_processes"]);
        }
    }

    public function store_pension($request_data,$user) {


        EmployeePensionHistory::create([
            'user_id' => $user->id,
            'pension_eligible' => false,
            'pension_enrollment_issue_date' => NULL,
            'pension_letters' => [],
            'pension_scheme_status' => NULL,
            'pension_scheme_opt_out_date'=> NULL,
            'pension_re_enrollment_due_date' => NULL,
            "is_manual" => 0,
            "from_date" => now(),
            "to_date" => NULL,
            "business_id"=> auth()->user()->business_id,
            'created_by' => auth()->user()->id

        ]);

    }

    public function store_project($request_data,$user) {
        $project = Project::where([
            "business_id" => $user->business_id,
            "is_default" => 1
          ])
          ->first();
          $employee_project_history_data = $project->toArray();
          $employee_project_history_data["project_id"] = $employee_project_history_data["id"];
          $employee_project_history_data["user_id"] = $user->id;
          $employee_project_history_data["from_date"] = now();
          $employee_project_history_data["to_date"] = NULL;
          EmployeeProjectHistory::create($employee_project_history_data);
          $user->projects()->attach([$project->id]);
    }





    public function update_address_history($request_data, $user)
    {

        $three_years_ago = Carbon::now()->subYears(3);
        EmployeeSponsorshipHistory::where('to_date', '<=', $three_years_ago)->delete();
        $address_history_data = [
            'user_id' => $user->id,
            'from_date' => now(),
            'created_by' => auth()->user()->id,
            'address_line_1' => $request_data["address_line_1"],
            'address_line_2' => $request_data["address_line_2"],
            'country' => $request_data["country"],
            'city' => $request_data["city"],
            'postcode' => $request_data["postcode"],
            'lat' => $request_data["lat"],
            'long' => $request_data["long"]
        ];

        $employee_address_history  =  EmployeeAddressHistory::where([
            "user_id" =>   $user->id,
            "to_date" => NULL
        ])
            ->latest('created_at')
            ->first();

        if ($employee_address_history) {


                   $fields_to_check = [
                    "address_line_1", "address_line_2", "country", "city", "postcode"

                    ];
                    $date_fields = [

                    ];
                    $fields_changed = $this->fieldsHaveChanged($fields_to_check, $employee_address_history, $request_data, $date_fields);
                    if (
                        $fields_changed
                    ) {
                        $employee_address_history->to_date = now();
                        $employee_address_history->save();
                        EmployeeAddressHistory::create($address_history_data);

                    }


        } else {
            EmployeeAddressHistory::create($address_history_data);
        }
    }

    public function update_recruitment_processes($request_data,$user) {
        if (!empty($request_data["recruitment_processes"]) && !empty($request_data["recruitment_processes"]["description"])) {
            $user->recruitment_processes()->delete();
            $user->recruitment_processes()->createMany($request_data["recruitment_processes"]);
        }
    }

    public function update_work_shift($request_data,$user) {
          if (!empty($request_data["work_shift_id"])) {
                    $work_shift =  WorkShift::where([
                        "id" => $request_data["work_shift_id"],
                    ])
                    ->where(function ($query) {
                        $query->where([

                            "business_id" => auth()->user()->business_id
                        ]) ->orWhere(function($query)  {
                            $query->where([
                                "is_active" => 1,
                                "business_id" => NULL,
                                "is_default" => 1
                            ]);
                        });
                    })
                    ->orderByDesc("id")
                        ->first();
                    if (!$work_shift) {
                        $this->storeError(
                            "no work shift found",
                            403,
                            "front end error",
                            "front end error"
                        );

                        throw new Exception("no work shift found", 403);

                    }

                    if (!$work_shift->is_active) {
                        $this->storeError(
                            ("Please activate the work shift named '" . $work_shift->name . "'"),
                            400,
                            "front end error",
                            "front end error"
                        );
                        throw new Exception("Please activate the work shift named '" . $work_shift->name . "'", 400);
                    }


                    $current_workshift = $user->work_shifts->last();

                    $current_workshift_id = NULL;
                    if ($current_workshift) {
                        $current_workshift_id = $current_workshift->id;
                    }

                    if ($work_shift->id != $current_workshift_id) {
                        UserWorkShift::where([
                            "user_id" => $user->id
                        ])
                            ->delete();

                        $work_shift->users()->attach($user->id);

                        EmployeeUserWorkShiftHistory::where([
                            "to_date" => NULL,
                            "user_id" => $user->id
                        ])
                            ->whereHas("work_shift_history", function ($query) use ($current_workshift_id) {
                                $query->where("work_shift_histories.work_shift_id", $current_workshift_id);
                            })
                            // ->where("work_shift_id",$current_workshift->id)
                            ->update([
                                "to_date" => now()
                            ]);

                        $work_shift_history =  WorkShiftHistory::where([
                            "to_date" => NULL,
                            "work_shift_id" => $work_shift->id
                        ])
                            ->first();

                        $work_shift_history->users()->attach($user->id, ['from_date' => now(), 'to_date' => NULL]);
                    }
                }
    }




    public function update_sponsorship($request_data,$user) {
        if (!empty($request_data["sponsorship_details"])) {

            $request_data["sponsorship_details"]["business_id"] = auth()->user()->business_id;
            $request_data["sponsorship_details"]["user_id"] = $user->id;
            $request_data["sponsorship_details"]["from_date"] = now();
            EmployeeSponsorshipHistory::create($request_data["sponsorship_details"]);




        }
    }
    public function update_passport_details($request_data,$user) {
        if (!empty($request_data["passport_details"])) {
            $request_data["passport_details"]["business_id"] = auth()->user()->business_id;
            $request_data["passport_details"]["user_id"] = $user->id;
            $request_data["passport_details"]["from_date"] = now();
            EmployeePassportDetailHistory::create($request_data["passport_details"]);



        }

    }

    public function update_visa_details($request_data,$user) {
        if (!empty($request_data["visa_details"]) && $request_data["is_active_visa_details"]) {
            $request_data["visa_details"]["business_id"] = auth()->user()->business_id;
            $request_data["visa_details"]["user_id"] = $user->id;
            $request_data["visa_details"]["from_date"] = now();
            EmployeeVisaDetailHistory::create($request_data["visa_details"]);

        }




    }

    public function update_right_to_works($request_data,$user) {
        if (!empty($request_data["right_to_works"]) && $request_data["is_active_right_to_works"]) {

            $request_data["right_to_works"]["business_id"] = auth()->user()->business_id;
            $request_data["right_to_works"]["user_id"] = $user->id;
            $request_data["right_to_works"]["from_date"] = now();
            EmployeeRightToWorkHistory::create($request_data["right_to_works"]);




        }

    }








}
