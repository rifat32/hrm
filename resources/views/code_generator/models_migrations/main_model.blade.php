<div class="code-snippet">
    <h3>App/Models/{{$names["singular_model_name"]}}.php</h3>
    <pre id="model"><code>

      namespace App\Models;


      use Carbon\Carbon;
      use Illuminate\Database\Eloquent\Factories\HasFactory;
      use Illuminate\Database\Eloquent\Model;

      class {{$names["singular_model_name"]}} extends Model
      {
          use HasFactory;
          protected $fillable = [
            @foreach ($fields->toArray() as $field)
              '{{$field['name']}}',
            @endforeach

            @if ($is_active)
            "is_active",
            @if ($is_default)

            "is_default",
            @endif
            @endif



              "business_id",
              "created_by"
          ];


          @foreach ($fields->toArray() as $field)
          @if ($field['is_foreign_key'])

          @php
        $relation["table_name"] = $field['relationship_table_name'];
        $relation["singular_table_name"] = Str::singular($relation["table_name"]);

        $relation["singular_model_name"] = Str::studly($relation["singular_table_name"]);

        $relation["plural_model_name"] = Str::plural($relation["singular_model_name"]);

        $relation["api_name"] = str_replace('_', '-', $relation["table_name"]);
        $relation["controller_name"] = $relation["singular_model_name"] . 'Controller';

        $relation["singular_comment_name"] = Str::singular(str_replace('_', ' ', $relation["table_name"]));
        $relation["plural_comment_name"] = str_replace('_', ' ', $relation["table_name"]);

          @endphp

          public function {{$relation['singular_table_name']}}()
          {
              return $this->belongsTo({{$relation['singular_model_name']}}::class, '{{$field['name']}}','id');
          }

          @endif

            @endforeach





            @if ($is_active && $is_default)
            public function disabled()
            {
                return $this->hasMany(Disabled{{$names["singular_model_name"]}}::class, '{{$names["singular_table_name"]}}_id', 'id');
            }
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

                if (!empty($user->business_id)) {
                    if (empty($this->business_id) || $user->business_id !=  $this->business_id) {
                        $is_default = 1;
                    }
                }



                return $is_default;
            }
            @endif









      }

</code></pre>
    <button class="copy-button" onclick="copyToClipboard('model')">Copy</button>
</div>
