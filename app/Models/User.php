<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles,SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guard_name = "api";
    protected $fillable = [
        'first_Name',
        'last_Name',
        'middle_Name',
        "color_theme_name",
        'emergency_contact_details',
        'gender',
        'is_in_employee',
        'designation_id',
        'employment_status_id',
        'joining_date',
        'salary_per_annum',
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






        'business_id',
        'employee_id',
        "created_by",
         'is_active'
    ];

    public function business() {
        return $this->belongsTo(Business::class, 'business_id', 'id');
    }

    public function all_users() {
        return $this->hasMany(User::class, 'business_id', 'business_id');
    }


    public function departments() {
        return $this->belongsToMany(Department::class, 'department_users', 'user_id', 'department_id');
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
        return $this->hasMany(Leave::class, 'employee_id', 'id');
    }
    public function attendances() {
        return $this->hasMany(Attendance::class, 'employee_id', 'id');
    }

    public function sponsorship_details() {
        return $this->hasOne(EmployeeSponsorship::class, 'employee_id', 'id');
    }

    public function passport_details() {
        return $this->hasOne(EmployeePassportDetail::class, 'employee_id', 'id');
    }
    public function visa_details() {
        return $this->hasOne(EmployeeVisaDetail::class, 'employee_id', 'id');
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


















    public function getCreatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }
    public function getUpdatedAtAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }




    public function getJoiningDateAttribute($value)
    {
        return (new Carbon($value))->format('d-m-Y');
    }



}
