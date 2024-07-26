<div class="container">
    <h1 class="text-center mt-5">Create Model and Migration</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">


            <div class="code-snippet">
                <h3>Create {{ $names["singular_model_name"] }} Model and Migration</h3>
                <pre id="create_model"><code>
            php artisan make:model -m {{ $names["singular_model_name"] }}
</code></pre>
                <button class="copy-button" onclick="copyToClipboard('create_model')">Copy</button>
            </div>

            <div class="code-snippet">
                <h3>Create Disabled{{ $names["singular_model_name"] }} Model and Migration</h3>
                <pre id="create_disabled_model"><code>
          php artisan make:model -m Disabled{{ $names["singular_model_name"] }}
</code></pre>
                <button class="copy-button" onclick="copyToClipboard('create_disabled_model')">Copy</button>
            </div>



            <div class="code-snippet">
                <h3>database/migrations/2024_07_26_182431_create_{{ $names["table_name"] }}_table_.php</h3>
                <pre id="migration"><code>

                use Illuminate\Database\Migrations\Migration;
                use Illuminate\Database\Schema\Blueprint;
                use Illuminate\Support\Facades\Schema;

                class Create{{ $names["plural_model_name"] }}Table extends Migration
                {
                    /**
                     * Run the migrations.
                     *
                     * @return void
                     */
                    public function up()
                    {
                        Schema::create('{{ $names["table_name"] }}', function (Blueprint $table) {
                            $table->id();
                            $table->string('name');
                            $table->text('description')->nullable();
                            $table->boolean('is_active')->default(false);
                            $table->boolean('is_default')->default(false);

                            $table->foreignId('business_id')
                            ->constrained('businesses')
                            ->onDelete('cascade');

                            $table->unsignedBigInteger("created_by");
                            $table->softDeletes();
                            $table->timestamps();
                        });
                    }

                    /**
                     * Reverse the migrations.
                     *
                     * @return void
                     */
                    public function down()
                    {
                        Schema::dropIfExists('letter_templates');
                    }
                }



    </code></pre>
                <button class="copy-button" onclick="copyToClipboard('migration')">Copy</button>
            </div>

            <div class="code-snippet">
                <h3>database/migrations/2024_07_26_182431_create_disabled_{{ $names["table_name"] }}_table_.php</h3>
                <pre id="disabled_migration"><code>

              use Illuminate\Database\Migrations\Migration;
              use Illuminate\Database\Schema\Blueprint;
              use Illuminate\Support\Facades\Schema;

              class CreateDisabled{{ $names["plural_model_name"] }}Table extends Migration
              {
                  /**
                   * Run the migrations.
                   *
                   * @return void
                   */
                  public function up()
                  {
                      Schema::create('disabled_{{ $names["plural_model_name"] }}', function (Blueprint $table) {
                          $table->id();

                          $table->foreignId('{{ $names["singular_table_name"] }}_id')
                          ->constrained('{{ $names["table_name"] }}')
                          ->onDelete('cascade');

                          $table->foreignId('business_id')
                          ->constrained('businesses')
                          ->onDelete('cascade');

                          $table->foreignId('created_by')
                          ->nullable()
                          ->constrained('users')
                          ->onDelete('set null');


                          $table->timestamps();
                      });
                  }

                  /**
                   * Reverse the migrations.
                   *
                   * @return void
                   */
                  public function down()
                  {
                      Schema::dropIfExists('disabled_letter_templates');
                  }
              }




  </code></pre>
                <button class="copy-button" onclick="copyToClipboard('disabled_migration')">Copy</button>
            </div>


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
                        'name',
                        'description',
                        "is_active",
                        "is_default",
                        "business_id",
                        "created_by"
                    ];

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
                }

  </code></pre>
              <button class="copy-button" onclick="copyToClipboard('model')">Copy</button>
          </div>


          <div class="code-snippet">
            <h3>App/Models/Disabled{{$names["singular_model_name"]}}.php</h3>
            <pre id="disabled_model"><code>
              namespace App\Models;

              use Carbon\Carbon;
              use Illuminate\Database\Eloquent\Factories\HasFactory;
              use Illuminate\Database\Eloquent\Model;

              class Disabled{{$names["singular_model_name"]}} extends Model
              {
                  use HasFactory;
                  protected $fillable = [
                      '{{$names["singular_table_name"]}}_id',
                      'business_id',
                      'created_by',
                      // Add other fillable columns if needed
                  ];

              }

 </code></pre>
            <button class="copy-button" onclick="copyToClipboard('disabled_model')">Copy</button>
        </div>

        </div>
    </div>
</div>
