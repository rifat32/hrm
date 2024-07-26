<div class="container">
    <h1 class="text-center mt-5">Form Requests</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">



            <div class="code-snippet">
                <h3>Generate create form request</h3>
                <pre id="generate_create_form_request"><code>
php artisan make:request {{$names["singular_model_name"]}}CreateRequest
      </code></pre>
                <button class="copy-button" onclick="copyToClipboard('generate_create_form_request')">Copy</button>
            </div>

            <div class="code-snippet">
              <h3>Generate update form request</h3>
              <pre id="generate_update_form_request"><code>
php artisan make:request {{$names["singular_model_name"]}}UpdateRequest
    </code></pre>
              <button class="copy-button" onclick="copyToClipboard('generate_update_form_request')">Copy</button>
          </div>



          <div class="code-snippet">
            <h3>App/http/requests/{{$names["singular_model_name"]}}CreateRequest</h3>
  <pre id="create_form_request"><code>

    namespace App\Http\Requests;


    use App\Rules\Validate{{$names["singular_model_name"]}}Name;
    use Illuminate\Foundation\Http\FormRequest;

    class {{$names["singular_model_name"]}}CreateRequest extends BaseFormRequest
    {
        /**
         * Determine if the user is authorized to make this request.
         *
         * @return bool
         */
        public function authorize()
        {
            return true;
        }

        /**
         * Get the validation rules that apply to the request.
         *
         * @return array
         */
        public function rules()
        {

            $rules = [
                'name' => [
                    "required",
                    'string',
                    new Validate{{$names["singular_model_name"]}}Name(NULL)
                ],
                'description' => 'nullable|string',
                'color' => 'required|string',
            ];



            return $rules;
        }
    }

  </code>
</pre>
            <button class="copy-button" onclick="copyToClipboard('create_form_request')">Copy</button>
        </div>

        <div class="code-snippet">
          <h3>App/http/requests/{{$names["singular_model_name"]}}UpdateRequest</h3>
<pre id="update_form_request"><code>


namespace App\Http\Requests;

use App\Models\{{$names["singular_model_name"]}};
use App\Rules\Validate{{$names["singular_model_name"]}}Name;
use Illuminate\Foundation\Http\FormRequest;

class {{$names["singular_model_name"]}}UpdateRequest extends BaseFormRequest
{
  /**
   * Determine if the user is authorized to make this request.
   *
   * @return bool
   */
  public function authorize()
  {
      return true;
  }

  /**
   * Get the validation rules that apply to the request.
   *
   * @return array
   */
  public function rules()
  {

      $rules = [

          'id' => [
              'required',
              'numeric',
              function ($attribute, $value, $fail) {

                  ${{$names["singular_table_name"]}}_query_params = [
                      "id" => $this->id,
                  ];
                  ${{$names["singular_table_name"]}} = {{$names["singular_model_name"]}}::where(${{$names["singular_table_name"]}}_query_params)
                      ->first();
                  if (!${{$names["singular_table_name"]}}) {
                      // $fail($attribute . " is invalid.");
                      $fail("no {{$names["singular_comment_name"]}} found");
                      return 0;
                  }
                  if (empty(auth()->user()->business_id)) {

                      if (auth()->user()->hasRole('superadmin')) {
                          if ((${{$names["singular_table_name"]}}->business_id != NULL || ${{$names["singular_table_name"]}}->is_default != 1)) {
                              // $fail($attribute . " is invalid.");
                              $fail("You do not have permission to update this {{$names["singular_comment_name"]}} due to role restrictions.");
                          }
                      } else {
                          if ((${{$names["singular_table_name"]}}->business_id != NULL || ${{$names["singular_table_name"]}}->is_default != 0 || ${{$names["singular_table_name"]}}->created_by != auth()->user()->id)) {
                              // $fail($attribute . " is invalid.");
                              $fail("You do not have permission to update this {{$names["singular_comment_name"]}} due to role restrictions.");
                          }
                      }
                  } else {
                      if ((${{$names["singular_table_name"]}}->business_id != auth()->user()->business_id || ${{$names["singular_table_name"]}}->is_default != 0)) {
                          // $fail($attribute . " is invalid.");
                          $fail("You do not have permission to update this {{$names["singular_comment_name"]}} due to role restrictions.");
                      }
                  }
              },
          ],

          'name' => [
              "required",
              'string',
              new Validate{{$names["singular_model_name"]}}Name($this->id)

          ],
          'description' => 'nullable|string',
          'color' => 'required|string',
      ];



      return $rules;
  }
}


</code>
</pre>
          <button class="copy-button" onclick="copyToClipboard('update_form_request')">Copy</button>
      </div>


        </div>
    </div>
</div>
