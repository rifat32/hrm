<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeRightToWork extends Model
{
    use HasFactory;
    <?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RightToWork extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_id',
        'right_to_work_code',
        'right_to_work_check_date',
        'right_to_work_expiry_date',
        'right_to_work_docs',
        'created_by',
    ];

    protected $casts = [

        'right_to_work_docs' => 'json',
    ];

    public function employee()
    {
        return $this->belongsTo(User::class);
    }

    public function business()
    {
        return $this->belongsTo(Business::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


}
