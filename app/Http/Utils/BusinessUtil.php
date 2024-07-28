<?php

namespace App\Http\Utils;

use App\Mail\BusinessWelcomeMail;
use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\BusinessTime;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\Designation;
use App\Models\EmailTemplate;
use App\Models\EmploymentStatus;
use App\Models\JobPlatform;
use App\Models\Project;
use App\Models\Role;
use App\Models\SettingAttendance;
use App\Models\SettingLeave;
use App\Models\SettingLeaveType;
use App\Models\SettingPaymentDate;
use App\Models\SettingPayrun;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use PharIo\Manifest\Email;

trait BusinessUtil
{
    use BasicUtil, DiscountUtil;
    // this function do all the task and returns transaction id or -1



    public function businessOwnerCheck($business_id)
    {

        $businessQuery  = Business::where(["id" => $business_id]);
        if (!auth()->user()->hasRole('superadmin')) {
            $businessQuery = $businessQuery->where(function ($query) {

                $query->where(function ($query) {
                    return   $query
                       ->when(!auth()->user()->hasPermissionTo("handle_self_registered_businesses"),function($query) {
                        $query->where('id', auth()->user()->business_id)
                        ->orWhere('created_by', auth()->user()->id)
                        ->orWhere('owner_id', auth()->user()->id);
                       },
                       function($query) {
                        $query->where('is_self_registered_businesses', 1)
                        ->orWhere('created_by', auth()->user()->id);
                       }

                    );

                });



            });
        }

        $business =  $businessQuery->first();
        if (empty($business)) {
           throw new Exception("you are not the owner of the business or the requested business does not exist.",401);
        }
        return $business;
    }











    public function storeDefaultEmailTemplates($business_id) {
       // Fetch active, default email templates without a business_id
$email_templates = EmailTemplate::where([
    "is_active" => 1,
    "is_default" => 1,
    "business_id" => NULL
])->get();

// Transform the collection to include only the necessary fields for insertion
$transformed_templates = $email_templates->map(function ($template) {
    return [
        "name" => $template->name,
        "type" => $template->type,
        "is_active" => $template->is_active,
        "wrapper_id" => $template->wrapper_id,
        "is_default" => $template->is_default,
        "business_id" => $template->business_id,
        "template" => $template->template,
        "template_variables" => $template->template_variables,
        "created_at" => $template->created_at,
        "updated_at" => $template->updated_at,
    ];
});

// Insert the transformed templates
EmailTemplate::insert($transformed_templates->toArray());


    }



    public function loadDefaultSettingLeaveType($business_id = NULL)
    {
        $defaultSettingLeaveTypes = SettingLeaveType::where(function($query)  {
            $query->where(function($query)  {
                $query->where('setting_leave_types.business_id', NULL)
                ->where('setting_leave_types.is_default', 1)
                ->where('setting_leave_types.is_active', 1)
                ->whereDoesntHave("disabled", function($q)  {
                    $q->whereIn("disabled_setting_leave_types.created_by", [auth()->user()->id]);
                });
            })
            ->orWhere(function ($query) {
                $query->where('setting_leave_types.business_id', NULL)
                    ->where('setting_leave_types.is_default', 0)
                    ->where('setting_leave_types.created_by', auth()->user()->id)
                    ->where('setting_leave_types.is_active', 1);
            });
        })
        ->get();




        foreach ($defaultSettingLeaveTypes as $defaultSettingLeave) {
            error_log($defaultSettingLeave);
            $insertableData = [
        'name'=> $defaultSettingLeave->name,
        'type'=> $defaultSettingLeave->type,
        'amount'=> $defaultSettingLeave->amount,
        'is_earning_enabled'=> $defaultSettingLeave->is_earning_enabled,
        "is_active"=> 1,
        "is_default"=> 0,
        "business_id" => $business_id,
            ];

            $setting_leave_type  = SettingLeaveType::create($insertableData);
        }
    }



    public function loadDefaultSettingLeave($business_id = NULL)
    {
        // load setting leave

        $default_setting_leave_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" =>  1,
        ];



        $defaultSettingLeaves = SettingLeave::where($default_setting_leave_query)->get();

        // If no records are found and the user is not a superadmin, retry without the 'created_by' condition
        if ($defaultSettingLeaves->isEmpty() && !auth()->user()->hasRole("superadmin")) {
            unset($default_setting_leave_query['created_by']);
            $defaultSettingLeaves = SettingLeave::where($default_setting_leave_query)->get();
        }





        foreach ($defaultSettingLeaves as $defaultSettingLeave) {
            error_log($defaultSettingLeave);
            $insertableData = [
                'start_month' => $defaultSettingLeave->start_month,
                'approval_level' => $defaultSettingLeave->approval_level,
                'allow_bypass' => $defaultSettingLeave->allow_bypass,
                "created_by" => auth()->user()->id,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business_id,
            ];

            $setting_leave  = SettingLeave::create($insertableData);

            $business_owner_role_id = Role::where([
                "name" => ("business_owner#" . $business_id)
            ])
                ->pluck("id");

            $setting_leave->special_roles()->sync($business_owner_role_id);


            $default_paid_leave_employment_statuses = $defaultSettingLeave->paid_leave_employment_statuses()->pluck("employment_status_id");
            $setting_leave->paid_leave_employment_statuses()->sync($default_paid_leave_employment_statuses);

            $default_unpaid_leave_employment_statuses = $defaultSettingLeave->unpaid_leave_employment_statuses()->pluck("employment_status_id");
            $setting_leave->unpaid_leave_employment_statuses()->sync($default_unpaid_leave_employment_statuses);
        }

        // end load setting leave
    }


    public function loadDefaultAttendanceSetting($business_id = NULL)
    {
        // load setting attendance

        $default_setting_attendance_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" =>  1,
        ];



        $defaultSettingAttendances = SettingAttendance::where($default_setting_attendance_query)->get();

        // If no records are found and the user is not a superadmin, retry without the 'created_by' condition
        if ($defaultSettingAttendances->isEmpty() && !auth()->user()->hasRole("superadmin")) {
            unset($default_setting_attendance_query['created_by']);
            $default_setting_attendance_query["is_default"] = 1;
            $defaultSettingAttendances = SettingAttendance::where($default_setting_attendance_query)->get();
        }







        foreach ($defaultSettingAttendances as $defaultSettingAttendance) {
            Log::info(json_encode($defaultSettingAttendance));
            $insertableData = [
                'punch_in_time_tolerance' => $defaultSettingAttendance->punch_in_time_tolerance,
                'work_availability_definition' => $defaultSettingAttendance->work_availability_definition,
                'punch_in_out_alert' => $defaultSettingAttendance->punch_in_out_alert,
                'punch_in_out_interval' => $defaultSettingAttendance->punch_in_out_interval,
                'alert_area' => $defaultSettingAttendance->alert_area,
                'auto_approval' => $defaultSettingAttendance->auto_approval,
                'is_geolocation_enabled' => $defaultSettingAttendance->is_geolocation_enabled,


                'service_name' => $defaultSettingAttendance->service_name,
                'api_key'=> $defaultSettingAttendance->api_key,

                "created_by" => auth()->user()->id,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business_id,







            ];

            $setting_attendance  = SettingAttendance::create($insertableData);




            $business_owner_role_id = Role::where([
                "name" => ("business_owner#" . $business_id)
            ])
                ->pluck("id");
            $setting_attendance->special_roles()->sync($business_owner_role_id);
        }

        // end load setting attendance

    }
    public function loadDefaultPayrunSetting($business_id = NULL)
    {
        // load setting attendance

        $default_setting_payrun_query = [
            "business_id" => NULL,
            "is_active" => 1,
            "is_default" => 1,
        ];


        $defaultSettingPayruns = SettingPayrun::where($default_setting_payrun_query)->get();

        // If no records are found and the user is not a superadmin, retry without the 'created_by' condition
        if ($defaultSettingPayruns->isEmpty() && !auth()->user()->hasRole("superadmin")) {
            unset($default_setting_payrun_query['created_by']);
            $defaultSettingPayruns = SettingPayrun::where($default_setting_payrun_query)->get();
        }


        foreach ($defaultSettingPayruns as $defaultSettingPayrun) {
            $insertableData = [
                'payrun_period' => $defaultSettingPayrun->payrun_period,
                'consider_type' => $defaultSettingPayrun->consider_type,
                'consider_overtime' => $defaultSettingPayrun->consider_overtime,

                "created_by" => auth()->user()->id,
                "is_active" => 1,
                "is_default" => 0,
                "business_id" => $business_id,
            ];

            $setting_payrun  = SettingPayrun::create($insertableData);




            //   $business_owner_role_id = Role::where([
            //       "name" => ("business_owner#" . $business_id)
            //   ])
            //   ->pluck("id");
            //   $setting_attendance->special_roles()->sync($business_owner_role_id, []);
        }
    }

    public function loadDefaultPaymentDateSetting($business_id = null)
{
    // Load default payment date settings

    $default_setting_payment_date_query = [
        'business_id' => null,
        'is_active' => 1,
        'is_default' =>  1,
    ];



    $defaultSettingPaymentDates = SettingPaymentDate::where($default_setting_payment_date_query)->get();

    // If no records are found and the user is not a superadmin, retry without the 'created_by' condition
    if ($defaultSettingPaymentDates->isEmpty() && !auth()->user()->hasRole('superadmin')) {
        unset($default_setting_payment_date_query['created_by']);
        $defaultSettingPaymentDates = SettingPaymentDate::where($default_setting_payment_date_query)->get();
    }

    foreach ($defaultSettingPaymentDates as $defaultSettingPaymentDate) {
        $insertableData = [
            'payment_type' => $defaultSettingPaymentDate->payment_type,
            'day_of_week' => $defaultSettingPaymentDate->day_of_week,
            'day_of_month' => $defaultSettingPaymentDate->day_of_month,
            'custom_frequency_interval' => $defaultSettingPaymentDate->custom_frequency_interval,
            'custom_frequency_unit' => $defaultSettingPaymentDate->custom_frequency_unit,
            'notification_delivery_status' => $defaultSettingPaymentDate->notification_delivery_status,
            'is_active' => 1,
            'is_default' => 0,
            'business_id' => $business_id,
            'created_by' => auth()->user()->id,
            'role_specific_settings' => $defaultSettingPaymentDate->role_specific_settings,
        ];

        $settingPaymentDate = SettingPaymentDate::create($insertableData);

        // Additional logic can be added here if needed
    }
}


public function loadDefaultEmailTemplate($business_id = null)
{
    // Load default payment date settings

    $default_email_template_query = [
        'business_id' => null,
        'is_active' => 1,
        'is_default' =>  1,
    ];



    $defaultEmailTemplates = EmailTemplate::where($default_email_template_query)->get();


    foreach ($defaultEmailTemplates as $defaultEmailTemplate) {
        $insertableData = [
            "name" => $defaultEmailTemplate->name,
            "type" => $defaultEmailTemplate->name,
            "template" => $defaultEmailTemplate->name,
            "is_active" => 1,
            "is_default" => 0,
            "business_id" => $business_id,
            'wrapper_id' => $defaultEmailTemplate->wrapper_id,
        ];

        $emailTemplate = EmailTemplate::create($insertableData);

        // Additional logic can be added here if needed
    }
}

    // end load setting attendance

    public function storeDefaultsToBusiness($business_id, $business_name, $owner_id, $address_line_1, $business)
    {

        $work_location =  WorkLocation::create([
            'name' => ($business_name . " " . "Office"),
            "is_active" => 1,
            "is_default" => 1,
            "business_id" => $business_id,
            "created_by" => $owner_id
        ]);




        $department =  Department::create([
            "name" => $business_name,
            "location" => $address_line_1,
            "is_active" => 1,
            "manager_id" => $owner_id,
            "business_id" => $business_id,
            "work_location_id" => $work_location->id,
            "created_by" => $owner_id
        ]);
        // DepartmentUser::create([
        //     "user_id" => $owner_id,
        //     "department_id" => $department->id
        // ]);

        $project =  Project::create([
            'name' => $business_name,
            'description',
            'start_date' => $business->start_date,
            'end_date' => NULL,
            'status' => "in_progress",
            "is_active" => 1,
            "is_default" => 1,
            "business_id" => $business_id,
            "created_by" => $owner_id
        ]);

        $default_work_shift_data = [
            'name' => 'Default work shift',
            'type' => 'regular',
            'description' => '',
            'is_personal' => false,
            'break_type' => 'unpaid',
            'break_hours' => 1,

            'details' => $business->times->toArray(),
            "is_business_default" => 1,
            "is_active",
            "is_default" => 1,
            "business_id" => $business_id,
        ];

        $default_work_shift = WorkShift::create($default_work_shift_data);
        $default_work_shift->details()->createMany($default_work_shift_data['details']);
        $default_work_shift->departments()->sync([$department->id]);



        $employee_work_shift_history_data = $default_work_shift->toArray();
        $employee_work_shift_history_data["work_shift_id"] = $default_work_shift->id;
        $employee_work_shift_history_data["from_date"] = $business->start_date;
        $employee_work_shift_history_data["to_date"] = NULL;
         $employee_work_shift_history =  WorkShiftHistory::create($employee_work_shift_history_data);
         $employee_work_shift_history->details()->createMany($default_work_shift_data['details']);
         $employee_work_shift_history->departments()->sync([$department->id]);








        $attached_defaults = [];
        $defaultRoles = Role::where([
            "business_id" => NULL,
            "is_default" => 1,
            "is_default_for_business" => 1,
            "guard_name" => "api",
        ])->get();

        foreach ($defaultRoles as $defaultRole) {
            $insertableData = [
                'name'  => ($defaultRole->name . "#" . $business_id),
                "is_default" => 1,
                "business_id" => $business_id,
                "is_default_for_business" => 0,
                "guard_name" => "api",
            ];
            $role  = Role::create($insertableData);
            $attached_defaults["roles"][$defaultRole->id] = $role->id;

            $permissions = $defaultRole->permissions;
            foreach ($permissions as $permission) {
                if (!$role->hasPermissionTo($permission)) {
                    $role->givePermissionTo($permission);
                }
            }
        }


        $this->storeDefaultEmailTemplates($business_id);

        $this->loadDefaultSettingLeaveType($business_id);


        $this->loadDefaultSettingLeave($business_id);

        $this->loadDefaultAttendanceSetting($business_id);

        $this->loadDefaultPayrunSetting($business_id);

        $this->loadDefaultPaymentDateSetting($business_id);

        $this->loadDefaultEmailTemplate($business_id);







    }





    public function checkWorkShiftDetails($details)
    {


        foreach ($details as $index => $detail) {
            $business_time =   BusinessTime::where([
                "business_id" => auth()->user()->business_id,
                "day" => $detail["day"]
            ])
                ->first();
            if (!$business_time) {
                $error = [
                    "message" => "The given data was invalid.",
                    "errors" => [("details." . $index . ".day") => ["no business time found on this day"]]
                ];
                return [
                    "ok" => false,
                    "status" => 422,
                    "error" => $error
                ];
            }

            if ($business_time->is_weekend == 1 && $detail["is_weekend"] != 1) {
                $error = [
                    "message" => "The given data was invalid.",
                    "errors" => [("details." . $index . ".is_weekend") => ["This is weekend day"]]
                ];
                return [
                    "ok" => false,
                    "status" => 422,
                    "error" => $error
                ];
            }


            if (!empty($detail["start_at"]) && !empty($detail["end_at"] && !empty($business_time->start_at) && !empty($business_time->end_at))) {

                $request_start_at = Carbon::createFromFormat('H:i:s', $detail["start_at"]);
                $request_end_at = Carbon::createFromFormat('H:i:s', $detail["end_at"]);
                $business_start_at = Carbon::createFromFormat('H:i:s', $business_time->start_at);
                $business_end_at = Carbon::createFromFormat('H:i:s', $business_time->end_at);


                $difference_in_both_request  = $request_start_at->diffInHours($request_end_at);
                $difference_in_both_start_at  = $business_start_at->diffInHours($request_start_at);
                $difference_in_end_at_start_at  = $business_end_at->diffInHours($request_start_at);
                $difference_in_both_end_at  = $business_end_at->diffInHours($business_end_at);
                $difference_in_start_at_end_at  = $business_start_at->diffInHours($request_end_at);






                if ($difference_in_both_request < 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [
                            ("details." . $index . ".end_at") => ["end at should be greater than start at"]

                        ]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }


                if ($difference_in_both_start_at < 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("details." . $index . ".start_at") => ["start at should be in business working time $difference_in_both_start_at"]]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }



                if ($difference_in_end_at_start_at < 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("details." . $index . ".start_at") => ["start at should be in business working time"]]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }


                if ($difference_in_both_end_at > 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("details." . $index . ".end_at") => ["end at should be in business working time"]]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }

                if ($difference_in_start_at_end_at < 0) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("details." . $index . ".end_at") => ["end at should be in business working time"]]
                    ];
                    return [
                        "ok" => false,
                        "status" => 422,
                        "error" => $error
                    ];
                }


            }
        }

        // foreach($request_data['details'] as $index => $detail) {
        //     $business_time =   BusinessTime::where([
        //            "business_id" => auth()->user()->business_id,
        //            "day" => $detail["day"]
        //        ])
        //        ->first();
        //        if(!$business_time) {
        //        $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [("details.".$index.".day")=>["no business time found on this day"]]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }


        //        if($business_time->is_weekend == 1 && $detail["is_weekend"] != 1) {
        //            $error = [
        //                    "message" => "The given data was invalid.",
        //                    "errors" => [("details.".$index.".is_weekend")=>["This is weekend day"]]
        //             ];
        //                throw new Exception(json_encode($error),422);
        //         }

        //         if(!empty($detail["start_at"]) && !empty($detail["end_at"] && !empty($business_time->start_at) && !empty($business_time->end_at)) ) {

        //        $request_start_at = Carbon::createFromFormat('H:i:s', $detail["start_at"]);
        //        $request_end_at = Carbon::createFromFormat('H:i:s', $detail["end_at"]);

        //        $business_start_at = Carbon::createFromFormat('H:i:s', $business_time->start_at);
        //        $business_end_at = Carbon::createFromFormat('H:i:s', $business_time->end_at);

        //        $difference_in_both_request  = $request_start_at->diffInHours($request_end_at);
        //        $difference_in_both_start_at  = $business_start_at->diffInHours($request_start_at);
        //        $difference_in_end_at_start_at  = $business_end_at->diffInHours($request_start_at);

        //        $difference_in_both_end_at  = $business_end_at->diffInHours($business_end_at);
        //        $difference_in_start_at_end_at  = $business_start_at->diffInHours($request_end_at);


        //        if($difference_in_both_request < 0) {
        //            $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [
        //                    ("details.".$index.".end_at")=>["end at should be greater than start at"]

        //                    ]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }


        //        if($difference_in_both_start_at < 0) {
        //            $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [ ("details.".$index.".start_at")=>["start at should be in business working time"]]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }



        //        if($difference_in_end_at_start_at < 0) {
        //         $error = [
        //             "message" => "The given data was invalid.",
        //             "errors" => [ ("details.".$index.".start_at")=>["start at should be in business working time"]]
        //      ];
        //         throw new Exception(json_encode($error),422);
        //     }


        //        if($difference_in_both_end_at > 0) {
        //            $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [ ("details.".$index.".end_at")=>["end at should be in business working time"]]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }

        //        if($difference_in_start_at_end_at < 0) {
        //            $error = [
        //                "message" => "The given data was invalid.",
        //                "errors" => [ ("details.".$index.".end_at")=>["end at should be in business working time"]]
        //         ];
        //            throw new Exception(json_encode($error),422);
        //        }
        //     }



        //    }

        return [
            "ok" => true,
        ];
    }


public function businessImageStore($business) {
    if (!empty($business["images"])) {
        $business["images"] = $this->storeUploadedFiles($business["images"],"","business_images");
        $this->makeFilePermanent($business["images"],"");
    }
    if (!empty($business["image"])) {
        $business["image"] = $this->storeUploadedFiles([$business["image"]],"","business_images")[0];
        $this->makeFilePermanent([$business["image"]],"");
    }
    if (!empty($business["logo"])) {
        $business["logo"] = $this->storeUploadedFiles([$business["logo"]],"","business_images")[0];
        $this->makeFilePermanent([$business["logo"]],"");
    }
    if (!empty($business["background_image"])) {
        $business["background_image"] = $this->storeUploadedFiles([$business["background_image"]],"","business_images")[0];
        $this->makeFilePermanent([$business["background_image"]],"");
    }
    return $business;
}



public function businessImageRollBack($request_data) {
    if(!empty($request_data["business"]["images"])) {
        try {

                $this->moveUploadedFilesBack($request_data["business"]["images"],"","business_images");



        } catch (Exception $innerException) {
            error_log("Failed to move recruitment processes files back: " . $innerException->getMessage());
        }
    }

    if(!empty($request_data["business"]["image"])) {
        try {

                $this->moveUploadedFilesBack($request_data["business"]["image"],"","business_images");



        } catch (Exception $innerException) {
            error_log("Failed to move recruitment processes files back: " . $innerException->getMessage());
        }
    }
    if(!empty($request_data["business"]["logo"])) {
        try {

                $this->moveUploadedFilesBack($request_data["business"]["logo"],"","business_images");



        } catch (Exception $innerException) {
            error_log("Failed to move recruitment processes files back: " . $innerException->getMessage());
        }
    }

    if(!empty($request_data["business"]["background_image"])) {
        try {

                $this->moveUploadedFilesBack($request_data["business"]["background_image"],"","business_images");



        } catch (Exception $innerException) {
            error_log("Failed to move recruitment processes files back: " . $innerException->getMessage());
        }
    }
}




public function createUserWithBusiness($request_data) {
   // user info starts ##############

   $password = $request_data['user']['password'];


   $request_data['user']['password'] = Hash::make($request_data['user']['password']);
    //    if(!$request->user()->hasRole('superadmin') || empty($request_data['user']['password'])) {
    //     $password = Str::random(10);
    //     $request_data['user']['password'] = Hash::make($password);
    //     }
    $request_data['user']['remember_token'] = Str::random(10);
    $request_data['user']['is_active'] = true;


    $request_data['user']['address_line_1'] = $request_data['business']['address_line_1'];
    $request_data['user']['address_line_2'] = (!empty($request_data['business']['address_line_2']) ? $request_data['business']['address_line_2'] : "");
    $request_data['user']['country'] = $request_data['business']['country'];
    $request_data['user']['city'] = $request_data['business']['city'];
    $request_data['user']['postcode'] = $request_data['business']['postcode'];
    $request_data['user']['lat'] = $request_data['business']['lat'];
    $request_data['user']['long'] = $request_data['business']['long'];




   $user =  User::create($request_data['user']);

   $created_by_user = auth()->user();

   if(empty($created_by_user)){
    $created_by_user = User::permission(['handle_self_registered_businesses'])->first();
   }



   $user->assignRole('business_owner');
   // end user info ##############


   //  business info ##############

   $request_data['business']['status'] = "pending";
   $request_data['business']['owner_id'] = $user->id;
   $request_data['business']['created_by'] = $created_by_user->id;
   $request_data['business']['is_active'] = true;
   $request_data['business']["pension_scheme_letters"] = [];
 $request_data['business']['service_plan_discount_amount'] = $this->getDiscountAmount($request_data['business']);


   $business =  Business::create($request_data['business']);


//    foreach($request_data['business']["active_module_ids"] as $active_module_id){
//     BusinessModule::create([
//      "is_enabled" => 1,
//      "business_id" => $business->id,
//      "module_id" => $active_module_id,
//      'created_by' => auth()->user()->id
//     ]);
//  }


   $user->email_verified_at = now();
   $user->business_id = $business->id;
   $token = Str::random(30);
   $user->resetPasswordToken = $token;
   $user->resetPasswordExpires = Carbon::now()->subDays(-1);
   $user->created_by = auth()->user()->id;
   $user->save();

   BusinessTime::where([
       "business_id" => $business->id
   ])
       ->delete();
   $timesArray = collect($request_data["times"])->unique("day");
   foreach ($timesArray as $business_time) {
       BusinessTime::create([
           "business_id" => $business->id,
           "day" => $business_time["day"],
           "start_at" => $business_time["start_at"],
           "end_at" => $business_time["end_at"],
           "is_weekend" => $business_time["is_weekend"],
       ]);
   }

   $this->storeDefaultsToBusiness($business->id, $business->name, $business->owner_id, $business->address_line_1, $business);



   //  if($request_data['user']['send_password']) {



   if (env("SEND_EMAIL") == true) {
       $this->checkEmailSender($user->id,0);

       Mail::to($request_data['user']['email'])->send(new BusinessWelcomeMail($user, $password));

       $this->storeEmailSender($user->id,0);

   }

$business->service_plan = $business->service_plan;

   return [
    "user" => $user,
    "business" => $business
   ];


}












}
