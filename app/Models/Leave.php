<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;
    protected $appends = ['is_in_arrears'];
    protected $fillable = [
        'leave_duration',
        'day_type',
        'leave_type_id',
        'user_id',
        'date',
        'note',
        'start_date',
        'end_date',

        'attachments',
        "hourly_rate",
        "status",
        "is_active",
        "business_id",
        "created_by",
    ];
    public function getIsInArrearsAttribute($value)
    {
        // Retrieve IDs of related leave records
        $leave_record_ids = $this->records()->pluck("leave_records.id");

        // Check if leave status is approved or it's a paid leave type
        if ($this->status == "approved" || $this->leave_type->type == "paid") {
            // Loop through each leave record ID
            foreach ($leave_record_ids as $leave_record_id) {
                // Check if there's any arrear for the leave record
                $leave_record_arrear =   LeaveRecordArrear::where(["leave_record_id" => $leave_record_id])->first();
                // Check if there's any payroll associated with the leave record
                $payroll = Payroll::whereHas("payroll_leave_records", function ($query) use ($leave_record_id) {
                    $query->where("payroll_leave_records.leave_record_id", $leave_record_id);
                })->first();
                // If no payroll associated with leave record
                if (!$payroll) {
                    // If no arrear exists for the leave record
                    if (!$leave_record_arrear) {

                        // Check if there's a previous payroll for the user
                        $last_payroll_exists = Payroll::where([
                            "user_id" => $this->user_id,
                        ])
                            ->where("end_date", ">", $this->in_date)
                            ->exists();
                        // If previous payroll exists, create a pending approval arrear
                        if ($last_payroll_exists) {
                            LeaveRecordArrear::create([
                                "leave_record_id" => $leave_record_id,
                                "status" => "pending_approval",
                            ]);
                            return true;
                        }
                    } else if ($leave_record_arrear->status == "pending_approval") {
                        // If arrear status is pending approval, return true
                        return true;
                    }
                }
            }

            return false;
        }
        // If leave status is not approved or it's not a paid leave type, delete arrears if any

        LeaveRecordArrear::whereIn("leave_record_id", $leave_record_ids)

            ->delete();

        return false;
    }




    public function records()
    {
        return $this->hasMany(LeaveRecord::class, 'leave_id', 'id');
    }

    public function employee()
    {
        return $this->belongsTo(User::class, "user_id", "id");
    }
    public function leave_type()
    {
        return $this->belongsTo(SettingLeaveType::class, "leave_type_id", "id");
    }
    protected $casts = [
        'attachments' => 'array',

    ];




    // public function getCreatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
    // public function getUpdatedAtAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }



    // public function getDateAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }

    // public function getStartDateAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }
    // public function getEndDateAttribute($value)
    // {
    //     return (new Carbon($value))->format('d-m-Y');
    // }








}
