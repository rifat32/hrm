<?php



namespace App\Models;


use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LetterTemplate extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'template',
        "is_active",
        "is_default",
        "business_id",
        "created_by"
    ];

    public function disabled()
    {
        return $this->hasMany(DisabledLetterTemplate::class, 'letter_template_id', 'id');
    }

    // public function getTemplateAttribute($value)
    // {
    //     return json_decode($value);
    // }


    public function getIsActiveAttribute($value)
    {

        $is_active = $value;
        $user = auth()->user();

        if (empty($user->business_id)) {
            if (empty($this->business_id) && $this->is_default == 1) {
                if (!$user->hasRole("superadmin")) {
                    $disabled = $this->disabled()->where([
                        "created_by" => $user->id
                    ])
                        ->first();
                    if ($disabled) {
                        $is_active = 0;
                    }
                }
            }
        } else {

            if (empty($this->business_id)) {
                $disabled = $this->disabled()->where([
                    "business_id" => $user->business_id
                ])
                    ->first();
                if ($disabled) {
                    $is_active = 0;
                }
            }
        }




        return $is_active;
    }


    public function getIsDefaultAttribute($value)
    {

        $is_default = $value;
        $user = auth()->user();

        if(!empty($user)) {

            if(!empty($user->business_id)) {
                if(empty($this->business_id) || $user->business_id !=  $this->business_id) {
                      $is_default = 1;
                   }

            } else if($user->hasRole("superadmin")) {
                $is_default = 0;
            }
        }

        return $is_default;
    }

    
}

