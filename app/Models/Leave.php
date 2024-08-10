<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Leave extends Model
{
    use HasFactory;
    protected $appends = ['is_in_arrears', 'i_approved'];
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

public function getIApprovedAttribute($value) {
$leave_approval = LeaveApproval::where([
    "created_by" => auth()->user()->id
])
->first();


if(!empty($leave_approval)){
         return $leave_approval->is_approved;

} else {
    return -1;
}


}


    public function getIsInArrearsAttribute($value)
    {
$is_in_arrears = false;

        // Retrieve IDs of related leave records
     // Retrieve IDs of related leave records
     $leave_records =LeaveRecord::where([
        "leave_id" => $this->id
    ])->get();
        $leave_record_ids = $leave_records->pluck("leave_records.id");

        // Check if leave status is approved or it's a paid leave type
        if ($this->status == "approved" || (!empty($this->leave_type) && $this->leave_type->type == "paid")) {

            // Loop through each leave record ID
            foreach ($leave_records as $leave_record) {
                // Check if there's any arrear for the leave record
                $leave_record_arrear =   LeaveRecordArrear::where(["leave_record_id" => $leave_record->id])->first();
                // Check if there's any payroll associated with the leave record
                $payroll = Payroll::whereHas("payroll_leave_records", function ($query) use ($leave_record) {
                    $query->where("payroll_leave_records.leave_record_id", $leave_record->id);
                })->first();
                // If no payroll associated with leave record

                if (empty($payroll)) {

                    // If no arrear exists for the leave record
                    if (empty($leave_record_arrear)) {

                        // Check if there's a previous payroll for the user
                        $last_payroll_exists = Payroll::where([
                            "user_id" => $this->user_id,
                        ])
                            ->where("end_date", ">=", $leave_record->date)
                            ->exists();
                        // If previous payroll exists, create a pending approval arrear
                        if (!empty($last_payroll_exists)) {
                            LeaveRecordArrear::create([
                                "leave_record_id" => $leave_record->id,
                                "status" => "pending_approval",
                            ]);
                            $is_in_arrears = true;
                            break;
                        }
                    } else if ($leave_record_arrear->status == "pending_approval") {
                        // If arrear status is pending approval, return true
                        $is_in_arrears = true;
                        break;
                    }

                }

            }


        }
        // If leave status is not approved or it's not a paid leave type, delete arrears if any

        LeaveRecordArrear::whereIn("leave_record_id", $leave_record_ids)

            ->delete();

        return  $is_in_arrears;
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
