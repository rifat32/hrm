<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use Illuminate\Support\Facades\File;

class Business extends Model
{
    use HasFactory;
    protected $appends = ['is_subscribed'];

    protected $fillable = [
        "name",
        "start_date",
        "trail_end_date",
        "about",
        "web_page",
        "phone",
        "email",
        "additional_information",
        "address_line_1",
        "address_line_2",
        "lat",
        "long",
        "country",
        "city",
        "currency",
        "postcode",
        "logo",
        "image",
        "background_image",
        "status",
        "is_active",
        "is_self_registered_businesses",

        "service_plan_id",
        "service_plan_discount_code",
        "service_plan_discount_amount",


        "pension_scheme_registered",
        "pension_scheme_name",
        "pension_scheme_letters",
        "number_of_employees_allowed",


        "owner_id",
        'created_by'

    ];

    protected $casts = [
        'pension_scheme_letters' => 'array',
    ];


    public function emailSettings()
    {
        return $this->hasOne(BusinessEmailSetting::class);
    }

    public function getIsSubscribedAttribute($value)
    {
        $user = auth()->user();
        if(empty($user)) {
return 0;
        }

        $business = $user->business;

        if ($user && $user->business) {
            $business = $user->business;
            if (!$business->is_active) {
                return 0;
            }
            if ($business->is_self_registered_businesses) {

                if(!empty($business->trail_end_date)) {
                    if(Carbon::parse($business->trail_end_date)->isPast() && !Carbon::parse($business->trail_end_date)->isToday()){
                        $latest_subscription = BusinessSubscription::where('business_id', $business->id)
                        ->where('service_plan_id', $business->service_plan_id)
                        ->latest() // Get the latest subscription
                        ->first();


// Check if there's no subscription
if (!$latest_subscription) {
return 0;
}

// Check if the subscription has not yet started
if (Carbon::parse($latest_subscription->start_date)->isFuture()) {
return 0;
}


// Check if the subscription has expired
if(!empty($latest_subscription->end_date)) {
if (Carbon::parse($latest_subscription->end_date)->isPast() && !Carbon::parse($business->trail_end_date)->isToday()) {
    return 0;
}
}

                    }

                }
            }
        }
        return 1;

    }



    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }
    public function users()
    {
        return $this->hasMany(User::class, 'business_id', 'id');
    }

    public function service_plan()
    {
        return $this->belongsTo(ServicePlan::class, 'service_plan_id', 'id');
    }

    public function subscription()
    {
        return $this->hasOne(BusinessSubscription::class, 'business_id', 'id')
        ->latest();
    }


    public function default_work_shift()
    {
        return $this->hasOne(WorkShift::class, 'business_id', 'id')->where('is_business_default', 1);
    }


    public function creator() {
        return $this->belongsTo(User::class, "created_by","id");
    }


    public function times()
    {
        return $this->hasMany(BusinessTime::class, 'business_id', 'id');
    }


    public function active_modules()
    {
        return $this->hasMany(BusinessModule::class, 'business_id', 'id');
    }





        // Define your model properties and relationships here

protected static function boot()
{
    parent::boot();

    // Listen for the "deleting" event on the Candidate model
    static::deleting(function($item) {
        // Call the deleteFiles method to delete associated files
        $item->deleteFiles();
    });
}

/**
 * Delete associated files.
 *
 * @return void
 */



public function deleteFiles()
{
    // Get the file paths associated with the candidate
    $filePaths = $this->pension_scheme_letters;

    // Iterate over each file and delete it
    foreach ($filePaths as $filePath) {
        if (File::exists(public_path($filePath->file))) {
            File::delete(public_path($filePath->file));
        }
    }
}



}
