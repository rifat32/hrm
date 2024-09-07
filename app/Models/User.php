<?php

namespace App\Models;

use App\Http\Utils\BasicUtil;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use   Billable, HasApiTokens, HasFactory, Notifiable, HasRoles, HasPermissions, BasicUtil;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $connection = 'mysql';


    protected $appends = ['has_this_project', "manages_department","will_exit"];

    protected $guard_name = "api";
    protected $fillable = [
        'first_Name',
        'last_Name',
        'middle_Name',

        "NI_number",
        "pension_eligible",
        "color_theme_name",
        'emergency_contact_details',
        'gender',
        'is_in_employee',
        'designation_id',
        'employment_status_id',
        'joining_date',



        'salary_per_annum',
        'weekly_contractual_hours',
        'minimum_working_days_per_week',
        'overtime_rate',



        'phone',
        'image',
        'address_line_1',
        'address_line_2',
        'country',
        'city',
        'postcode',
        "lat",
        "long",
        'email',
        'password',
        'is_sponsorship_offered',
        "date_of_birth",

        "immigration_status",


        "is_active_visa_details",
        "is_active_right_to_works",



        'bank_id',
        'sort_code',
        'account_number',
        'account_name',


        'business_id',
        'user_id',
        "created_by",
        'is_active'
    ];

    public function getWillExitAttribute($value)
    {

        $last_termination =  Termination::where([
            "user_id" => $this->id
        ])
            ->latest()
            ->first();
        if(!empty($last_termination)) {
            $date_of_termination = Carbon::parse($last_termination->date_of_termination);

            if($date_of_termination->isFuture() || $date_of_termination->isToday()){
                return 1;
            }
        }

        return 0;


    }

    // Relationships
    public function accessRevocation()
    {
        return $this->hasOne(AccessRevocation::class);
    }

    public function terminations()
    {
        return $this->hasMany(Termination::class);
    }

    public function lastTermination()
    {
        return $this->hasOne(Termination::class)->orderByDesc("id");
    }

    // Relationships
    public function exitInterviews()
    {
        return $this->hasMany(ExitInterview::class);
    }



    public function getHasThisProjectAttribute($value)
    {
        $request = request();
        // You can now use $currentRequest as the request object
        $has_this_project = $request->input('has_this_project');


        if (empty($has_this_project)) {
            return NULL;
        }
        $project = Project::whereHas("users", function ($query) {
                $query->where("users.id", $this->id);
            })
            ->where([
                "id" => $has_this_project
            ])
            ->first();

        return $project ? 1 : 0;
    }


    public function getManagesDepartmentAttribute($value)
    {

        $all_departments = $this->get_all_departments_of_manager();

        return count($all_departments) > 0;
    }


    public function bank()
    {
        return $this->hasOne(Bank::class, "id", 'bank_id');
    }



    public function payrun_user()
    {
        return $this->hasOne(PayrunUser::class, "user_id", 'id');
    }


    public function payrolls()
    {
        return $this->hasMany(Payroll::class, "user_id", 'id');
    }



    public function projects()
    {
        return $this->belongsToMany(Project::class, 'user_projects', 'user_id', 'project_id');
    }


    public function work_locations()
    {
        return $this->belongsToMany(WorkLocation::class, 'user_work_locations', 'user_id', 'work_location_id');
    }







    public function holidays()
    {
        return $this->belongsToMany(Holiday::class, 'user_holidays', 'user_id', 'holiday_id');
    }





    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }


    public function resold_businesses()
    {
        return $this->hasMany(Business::class, 'created_by', 'id');
    }






    public function all_users()
    {
        return $this->hasMany(User::class, 'business_id', 'business_id');
    }


    public function departments()
    {
        return $this->belongsToMany(Department::class, 'department_users', 'user_id', 'department_id');
    }


    public function manager_departments()
    {
        return $this->hasMany(Department::class, 'manager_id', 'id');
    }

    public function recursive_department_users()
    {
        return $this->hasMany(Department::class, 'manager_id', 'id')
        ->whereNotNull("manager_id")
        ->with([
            "manager",
            "recursive_manager_users",
        ]);
    }


    public function department_user()
    {
        return $this->belongsTo(DepartmentUser::class,  'id', 'user_id');
    }


    public function recruitment_processes()
    {
        return $this->hasMany(UserRecruitmentProcess::class, 'user_id', 'id');
    }

    public function employee_recruitment_processes()
    {
        return $this->belongsToMany(RecruitmentProcess::class, "user_recruitment_processes", 'user_id', 'recruitment_process_id');
    }




    public function designation()
    {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    public function employment_status()
    {
        return $this->belongsTo(EmploymentStatus::class, 'employment_status_id', 'id');
    }

    public function work_shifts()
    {
        return $this->belongsToMany(WorkShift::class, 'user_work_shifts', 'user_id', 'work_shift_id');
    }


    public function employee_rota()
    {
        return $this->hasOne(EmployeeRota::class, 'user_id', 'id');
    }

    public function leaves()
    {
        return $this->hasMany(Leave::class, 'user_id', 'id');
    }
    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id', 'id');
    }

    public function attendance_histories()
    {
        return $this->hasMany(AttendanceHistory::class, 'user_id', 'id');
    }

    public function sponsorship_detail()
    {
        $issue_date_column = 'date_assigned';
        $expiry_date_column = 'expiry_date';

        return $this->hasOne(EmployeeSponsorshipHistory::class, 'user_id', 'id')
            ->where($issue_date_column, '<', now())
            ->whereExists(function ($query) use ($issue_date_column, $expiry_date_column) {
                $query->select('user_id')
                    ->from('employee_sponsorship_histories')
                    ->whereColumn('user_id', 'employee_sponsorship_histories.user_id')
                    ->where($issue_date_column, '<', now())
                    ->orderBy($expiry_date_column, 'DESC')
                    ->limit(1);
            })
            ->orderByDesc('employee_sponsorship_histories.id');
    }


    public function sponsorship_details()
    {
        $issue_date_column = 'date_assigned';
        $expiry_date_column = 'expiry_date';

        return $this->hasOne(EmployeeSponsorshipHistory::class, 'user_id', 'id')
            ->where($issue_date_column, '<', now())
            ->whereExists(function ($query) use ($issue_date_column, $expiry_date_column) {
                $query->select('user_id')
                    ->from('employee_sponsorship_histories')
                    ->whereColumn('user_id', 'employee_sponsorship_histories.user_id')
                    ->where($issue_date_column, '<', now())
                    ->orderBy($expiry_date_column, 'DESC')
                    ->limit(1);
            })
            ->orderByDesc('employee_sponsorship_histories.id');
    }









    public function pension_detail()
    {
        return $this->hasOne(EmployeePensionHistory::class, 'user_id', 'id')

        ->where(function ($query) {
            // Include conditions based on the user's pension eligibility status
            $query->where(function ($query) {
                $query->
                whereHas('employee', function ($query) {
                    $query->where('users.pension_eligible', 1);
                  })

                ->where('employee_pension_histories.pension_enrollment_issue_date', '<', now());
            })
            ->orWhere(function ($query) {
                $query->whereHas('employee', function ($query) {
                    $query->where('users.pension_eligible', 0);
                  });
            });
        })
        ->orderByDesc('employee_pension_histories.id');
    }

    public function pension_details()
    {
        return $this->hasOne(EmployeePensionHistory::class, 'user_id', 'id')

        ->where(function ($query) {
            // Include conditions based on the user's pension eligibility status
            $query->where(function ($query) {
                $query->
                whereHas('employee', function ($query) {
                    $query->where('users.pension_eligible', 1);
                  })

                ->where('employee_pension_histories.pension_enrollment_issue_date', '<', now());
            })
            ->orWhere(function ($query) {
                $query->whereHas('employee', function ($query) {
                    $query->where('users.pension_eligible', 0);
                  });
            });
        })
        ->orderByDesc('employee_pension_histories.id');
    }



    public function passport_detail()
    {
        $issue_date_column = 'passport_issue_date';
        $expiry_date_column = 'passport_expiry_date';

        return $this->hasOne(EmployeePassportDetailHistory::class, 'user_id', 'id')
        ->where($issue_date_column, '<', now())
        ->whereExists(function ($query) use ($issue_date_column, $expiry_date_column) {
            $query->select('user_id')
                ->from('employee_passport_detail_histories')
                ->whereColumn('user_id', 'employee_passport_detail_histories.user_id')
                ->where($issue_date_column, '<', now())
                ->orderBy($expiry_date_column, 'DESC')
                ->limit(1);
        })
        ->orderByDesc('employee_passport_detail_histories.id');
    }

    public function passport_details()
    {
        $issue_date_column = 'passport_issue_date';
        $expiry_date_column = 'passport_expiry_date';

        return $this->hasOne(EmployeePassportDetailHistory::class, 'user_id', 'id')
        ->where($issue_date_column, '<', now())
        ->whereExists(function ($query) use ($issue_date_column, $expiry_date_column) {
            $query->select('user_id')
                ->from('employee_passport_detail_histories')
                ->whereColumn('user_id', 'employee_passport_detail_histories.user_id')
                ->where($issue_date_column, '<', now())
                ->orderBy($expiry_date_column, 'DESC')
                ->limit(1);
        })
        ->orderByDesc('employee_passport_detail_histories.id');

    }

    public function visa_detail()
    {
        $issue_date_column = 'visa_issue_date';
        $expiry_date_column = 'visa_expiry_date';

        return $this->hasOne(EmployeeVisaDetailHistory::class, 'user_id', 'id')
        ->where($issue_date_column, '<', now())
        ->whereExists(function ($query) use ($issue_date_column, $expiry_date_column) {
            $query->select('user_id')
                ->from('employee_visa_detail_histories')
                ->whereColumn('user_id', 'employee_visa_detail_histories.user_id')
                ->where($issue_date_column, '<', now())
                ->orderBy($expiry_date_column, 'DESC')
                ->limit(1);
        })
        ->orderByDesc('employee_visa_detail_histories.id');


    }


    public function visa_details()
    {

        $issue_date_column = 'visa_issue_date';
        $expiry_date_column = 'visa_expiry_date';

         return $this->hasOne(EmployeeVisaDetailHistory::class, 'user_id', 'id')
        ->where($issue_date_column, '<', now())
        ->whereExists(function ($query) use ($issue_date_column, $expiry_date_column) {
            $query->select('user_id')
                ->from('employee_visa_detail_histories')
                ->whereColumn('user_id', 'employee_visa_detail_histories.user_id')
                ->where($issue_date_column, '<', now())
                ->orderBy($expiry_date_column, 'DESC')
                ->limit(1);
        })
        ->orderByDesc('employee_visa_detail_histories.id');
    }


    public function right_to_work()
    {
        $issue_date_column = 'right_to_work_check_date';
        $expiry_date_column = 'right_to_work_expiry_date';

        return $this->hasOne(EmployeeRightToWorkHistory::class, 'user_id', 'id')
        ->where($issue_date_column, '<', now())
        ->whereExists(function ($query) use ($issue_date_column, $expiry_date_column) {
            $query->select('user_id')
                ->from('employee_right_to_work_histories')
                ->whereColumn('user_id', 'employee_right_to_work_histories.user_id')
                ->where($issue_date_column, '<', now())
                ->orderBy($expiry_date_column, 'DESC')
                ->limit(1);
        })
        ->orderByDesc('employee_right_to_work_histories.id');


    }

    public function right_to_works()
    {

        $issue_date_column = 'right_to_work_check_date';
        $expiry_date_column = 'right_to_work_expiry_date';

        return $this->hasOne(EmployeeRightToWorkHistory::class, 'user_id', 'id')
        ->where($issue_date_column, '<', now())
        ->whereExists(function ($query) use ($issue_date_column, $expiry_date_column) {
            $query->select('user_id')
                ->from('employee_right_to_work_histories')
                ->whereColumn('user_id', 'employee_right_to_work_histories.user_id')
                ->where($issue_date_column, '<', now())
                ->orderBy($expiry_date_column, 'DESC')
                ->limit(1);
        })
        ->orderByDesc('employee_right_to_work_histories.id');
    }


    public function assets()
    {
        return $this->hasMany(UserAsset::class, 'user_id', 'id');
    }
    public function documents()
    {
        return $this->hasMany(UserDocument::class, 'user_id', 'id');
    }
    public function education_histories()
    {
        return $this->hasMany(UserEducationHistory::class, 'user_id', 'id');
    }
    public function job_histories()
    {
        return $this->hasMany(UserJobHistory::class, 'user_id', 'id');
    }

    public function notes()
    {
        return $this->hasMany(UserNote::class, 'user_id', 'id');
    }

    public function social_links()
    {
        return $this->hasMany(UserSocialSite::class, 'user_id', 'id');
    }


    public function scopeWhereHasRecursiveHolidays($query, $today, $depth = 5)
    {
        $query->whereHas('departments', function ($subQuery) use ($today, $depth) {
            $subQuery->whereHasRecursiveHolidays($today, $depth);
        });
    }


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        "site_redirect_token",

        "email_verify_token",
        "email_verify_token_expires",
        "resetPasswordToken",
        "resetPasswordExpires"
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'emergency_contact_details' => 'array',
    ];
}
