<?php

namespace App\Models;


use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasPermissions;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    use   Billable, HasApiTokens, HasFactory, Notifiable, HasRoles,HasPermissions,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $connection = 'mysql';


    protected $appends = ['has_this_project'];
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
        "immigration_status",

        'work_location_id',
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

  public function getHasThisProjectAttribute($value) {
    $request = request();
    // You can now use $currentRequest as the request object
    $has_this_project = $request->input('has_this_project');


    if(empty($has_this_project)) {
        return NULL;
    }
    $project = Project::
    whereHas("users",function($query) {
      $query->where("users.id",$this->id);
   })
     ->where([
      "id" => $has_this_project
     ])
      ->first();

      return $project?1:0;

    }

    public function payrun_user()
    {
        return $this->hasOne(PayrunUser::class, "user_id" ,'id');
    }


    public function payrolls()
    {
        return $this->hasMany(Payroll::class, "user_id" ,'id');
    }



    public function projects() {
        return $this->belongsToMany(Project::class, 'user_projects', 'user_id', 'project_id');
    }


    public function holidays() {
        return $this->belongsToMany(Holiday::class, 'user_holidays', 'user_id', 'holiday_id');
    }


    public function work_location()
    {
        return $this->belongsTo(WorkLocation::class, "work_location_id" ,'id');
    }


    public function business() {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }


    public function all_users() {
        return $this->hasMany(User::class, 'business_id', 'business_id');
    }


    public function departments() {
        return $this->belongsToMany(Department::class, 'department_users', 'user_id', 'department_id');
    }


    public function recruitment_processes() {
        return $this->hasMany(UserRecruitmentProcess::class, 'user_id', 'id');
    }


    public function designation() {
        return $this->belongsTo(Designation::class, 'designation_id', 'id');
    }

    public function employment_status() {
        return $this->belongsTo(EmploymentStatus::class, 'employment_status_id', 'id');
    }

    public function work_shifts() {
        return $this->belongsToMany(WorkShift::class, 'user_work_shifts', 'user_id', 'work_shift_id');
    }

    public function leaves() {
        return $this->hasMany(Leave::class, 'user_id', 'id');
    }
    public function attendances() {
        return $this->hasMany(Attendance::class, 'user_id', 'id');
    }

    public function attendance_histories() {
        return $this->hasMany(AttendanceHistory::class, 'user_id', 'id');
    }

    public function sponsorship_detail() {
        return $this->hasOne(EmployeeSponsorship::class, 'user_id', 'id');
    }



    public function passport_detail() {
        return $this->hasOne(EmployeePassportDetail::class, 'user_id', 'id');
    }
    public function visa_detail() {
        return $this->hasOne(EmployeeVisaDetail::class, 'user_id', 'id');
    }
    public function right_to_work() {
        return $this->hasOne(EmployeeRightToWork::class, 'user_id', 'id');
    }
    public function sponsorship_details() {
        return $this->hasOne(EmployeeSponsorship::class, 'user_id', 'id');
    }




    private function manipulateHistory($query, $issue_date, $expiry_date) {
        return $query
        ;
    }

    public function pension_details() {
        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';
        $current_user_id = request()->id;

        $user = User::where([
            "id" => $current_user_id
        ])
        ->first();

          $current_data = NULL;
        if(!$user->pension_eligible) {
            $current_data = EmployeePensionHistory::where('user_id', $current_user_id)
            ->where("pension_eligible",0)
            ->latest()->first();
        } else {
            $latest_expired_record = EmployeePensionHistory::where('user_id', $current_user_id)
            ->where($issue_date_column, '<', now())
            ->orderByDesc($expiry_date_column)
            ->first();
            if($latest_expired_record) {
                $current_data = EmployeePensionHistory::where('user_id', $current_user_id)
                ->where($expiry_date_column, $latest_expired_record->expiry_date_column)
                ->orderByDesc($issue_date_column)
                ->first();
            }
        }



        return     $this->hasOne(EmployeePensionHistory::class, 'user_id', 'id')
        ->where("id",$current_data?$current_data->id:NULL);
    }

    public function pension_detail() {
        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';
        $current_user_id = request()->id;




        $latest_expired_record = EmployeePensionHistory::where('user_id', $current_user_id)
        ->where("pension_eligible", 1)
        ->where($issue_date_column, '<', now())
        ->orderByRaw("ISNULL($expiry_date_column), $expiry_date_column DESC")
        ->orderBy('id', 'DESC')
        // ->orderByRaw("ISNULL($expiry_date_column), $expiry_date_column DESC")
        ->first();

        // $latest_expired_record = EmployeePensionHistory::where('user_id', $current_user_id)
        // ->where($issue_date_column, '<', now())
        // ->orderByDesc($expiry_date_column)
        // ->first();


        if($latest_expired_record) {
            if($latest_expired_record->expiry_date_column) {
                $current_data = EmployeePensionHistory::where('user_id', $current_user_id)
                ->where($expiry_date_column, $latest_expired_record->expiry_date_column)
                ->orderByDesc($issue_date_column)
                ->first();
            } else {
                $current_data = $latest_expired_record;
            }

        }


        if(empty($current_data)) {
            $current_data = EmployeePensionHistory::where('user_id', $current_user_id)
            ->latest()
            ->first();
        }




        return    $this->hasOne(EmployeePensionHistory::class, 'user_id', 'id')
        ->where("id",$current_data->id);
    }

    public function passport_details() {
        return $this->hasOne(EmployeePassportDetail::class, 'user_id', 'id');
    }
    public function visa_details() {
        return $this->hasOne(EmployeeVisaDetail::class, 'user_id', 'id');
    }
    public function right_to_works() {
        return $this->hasOne(EmployeeRightToWork::class, 'user_id', 'id');
    }


    public function assets() {
        return $this->hasMany(UserAsset::class, 'user_id', 'id');
    }
    public function documents() {
        return $this->hasMany(UserDocument::class, 'user_id', 'id');
    }
    public function education_histories() {
        return $this->hasMany(UserEducationHistory::class, 'user_id', 'id');
    }
    public function job_histories() {
        return $this->hasMany(UserJobHistory::class, 'user_id', 'id');
    }

    public function notes() {
        return $this->hasMany(UserNote::class, 'user_id', 'id');
    }

    public function social_links() {
        return $this->hasMany(UserSocialSite::class, 'user_id', 'id');
    }


    public function scopeWhereHasRecursiveHolidays($query, $today,$depth = 5)
    {
        $query->whereHas('departments', function ($subQuery) use ($today,$depth) {
            $subQuery->whereHasRecursiveHolidays($today,$depth);
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







    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($user) {
            // Cascade soft delete to related children
            $user->leaves()->delete();
            $user->attendances()->delete();

        });

        static::restoring(function ($user) {
            // Cascade restore to related children
            $user->leaves()->withTrashed()->restore();
            $user->attendances()->withTrashed()->restore();
        });

    }


















}
