@foreach ($fields->toArray() as $field)
@php
    $relation["field_name"] = $field['name'];
    $relation["singular_field_name"] = Str::studly($relation["field_name"]);

     // Remove '_Id' from the string
     $relation["singular_model_name"] = Str::replaceLast('_id', '', $relation["field_name"]);

    // Remove the last underscore if it exists
    $relation["singular_model_name"] = Str::rtrim($string, '_');
@endphp


    @if ($field['is_foreign_key'] == 1)

    <div class="code-snippet">
        <h3>Create Rule Validate{{$relation['singular_field_name']}}</h3>
        <pre id="create_validate_{{$relation['field_name']}}"><code>
    php artisan make:rule Validate{{$relation['singular_field_name']}}
    </code></pre>
        <button class="copy-button" onclick="copyToClipboard('create_validate_{{$relation['field_name']}}')">Copy</button>
    </div>

    <div class="code-snippet">
      <h3>App/rules/Validate{{$relation['singular_field_name']}}</h3>
      <pre id="validate_{{$names["singular_table_name"]}}_name"><code>

        namespace App\Rules;

        use Illuminate\Contracts\Validation\Rule;

        class Validate{{$relation['singular_field_name']}} implements Rule
        {
            /**
            * Create a new rule instance.
            *
            * @return void
            */

            protected $id;
           protected $errMessage;

           public function __construct($id)
           {
               $this->id = $id;
               $this->errMessage = "";
           }


        }

    </code></pre>
      <button class="copy-button" onclick="copyToClipboard('validate_{{$relation['field_name']}}')">Copy</button>
    </div>


    @endif





@endforeach


