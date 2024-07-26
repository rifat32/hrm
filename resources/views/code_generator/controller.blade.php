<div class="container">
    <h1 class="text-center mt-5">Controller</h1>
    <div class="row justify-content-center">
        <div class="col-md-8">

            <div class="code-snippet">
                <h3>Create Controller Using CLI</h3>
                <pre id="create_controller"><code>
php artisan make:controller {{ $names["controller_name"] }}
    </code></pre>
                <button class="copy-button" onclick="copyToClipboard('create_controller')">Copy</button>
            </div>



            <div class="code-snippet">
                <h3>App/Http/controllers/{{ $names["controller_name"] }}</h3>
                <pre id="controller"><code>



                namespace App\Http\Controllers;

                use App\Http\Requests\{{ $names["singular_model_name"] }}CreateRequest;
                use App\Http\Requests\{{ $names["singular_model_name"] }}UpdateRequest;
                use App\Http\Requests\GetIdRequest;
                use App\Http\Utils\BusinessUtil;
                use App\Http\Utils\ErrorUtil;
                use App\Http\Utils\UserActivityUtil;
                use App\Models\{{ $names["singular_model_name"] }};
                use App\Models\Disabled{{ $names["singular_model_name"] }};
                use App\Models\User;
                use Carbon\Carbon;
                use Exception;
                use Illuminate\Http\Request;
                use Illuminate\Support\Facades\DB;

                class {{ $names["controller_name"] }} extends Controller
                {
                    use ErrorUtil, UserActivityUtil, BusinessUtil;
                    /**
                     *
                     * @OA\Post(
                     *      path="/v1.0/{{ $names["api_name"] }}",
                     *      operationId="create{{ $names["singular_model_name"] }}",
                     *      tags={"{{ $names["table_name"] }}"},
                     *       security={
                     *           {"bearerAuth": {}}
                     *       },
                     *      summary="This method is to store {{ $names["plural_comment_name"] }}",
                     *      description="This method is to store {{ $names["plural_comment_name"] }}",
                     *
                     *  @OA\RequestBody(
                     *         required=true,
                     *         @OA\JsonContent(
                     * @OA\Property(property="name", type="string", format="string", example="tttttt"),
                     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;")
                     *
                     *
                     *
                     *         ),
                     *      ),
                     *      @OA\Response(
                     *          response=200,
                     *          description="Successful operation",
                     *       @OA\JsonContent(),
                     *       ),
                     *      @OA\Response(
                     *          response=401,
                     *          description="Unauthenticated",
                     * @OA\JsonContent(),
                     *      ),
                     *        @OA\Response(
                     *          response=422,
                     *          description="Unprocesseble Content",
                     *    @OA\JsonContent(),
                     *      ),
                     *      @OA\Response(
                     *          response=403,
                     *          description="Forbidden",
                     *   @OA\JsonContent()
                     * ),
                     *  * @OA\Response(
                     *      response=400,
                     *      description="Bad Request",
                     *   *@OA\JsonContent()
                     *   ),
                     * @OA\Response(
                     *      response=404,
                     *      description="not found",
                     *   *@OA\JsonContent()
                     *   )
                     *      )
                     *     )
                     */

                    public function create{{ $names["singular_model_name"] }}({{ $names["singular_model_name"] }}CreateRequest $request)
                    {

                        try {
                            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
                            return DB::transaction(function () use ($request) {
                                if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_create')) {
                                    return response()->json([
                                        "message" => "You can not perform this action"
                                    ], 401);
                                }

                                $request_data = $request->validated();

                                $request_data["is_active"] = 1;
                                $request_data["is_default"] = 0;
                                $request_data["created_by"] = $request->user()->id;
                                $request_data["business_id"] = $request->user()->business_id;

                                if (empty($request->user()->business_id)) {
                                    $request_data["business_id"] = NULL;
                                    if ($request->user()->hasRole('superadmin')) {
                                        $request_data["is_default"] = 1;
                                    }
                                }




                                ${{ $names["singular_table_name"] }} =  {{ $names["singular_model_name"] }}::create($request_data);




                                return response(${{ $names["singular_table_name"] }}, 201);
                            });
                        } catch (Exception $e) {

                            return $this->sendError($e, 500, $request);
                        }
                    }

                    /**
                     *
                     * @OA\Put(
                     *      path="/v1.0/{{ $names["api_name"] }}",
                     *      operationId="update{{ $names["singular_model_name"] }}",
                     *      tags={"{{ $names["table_name"] }}"},
                     *       security={
                     *           {"bearerAuth": {}}
                     *       },
                     *      summary="This method is to update {{ $names["plural_comment_name"] }} ",
                     *      description="This method is to update {{ $names["plural_comment_name"] }} ",
                     *
                     *  @OA\RequestBody(
                     *         required=true,
                     *         @OA\JsonContent(
                     *      @OA\Property(property="id", type="number", format="number", example="1"),
                     * @OA\Property(property="name", type="string", format="string", example="tttttt"),
                     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;")

                     *
                     *         ),
                     *      ),
                     *      @OA\Response(
                     *          response=200,
                     *          description="Successful operation",
                     *       @OA\JsonContent(),
                     *       ),
                     *      @OA\Response(
                     *          response=401,
                     *          description="Unauthenticated",
                     * @OA\JsonContent(),
                     *      ),
                     *        @OA\Response(
                     *          response=422,
                     *          description="Unprocesseble Content",
                     *    @OA\JsonContent(),
                     *      ),
                     *      @OA\Response(
                     *          response=403,
                     *          description="Forbidden",
                     *   @OA\JsonContent()
                     * ),
                     *  * @OA\Response(
                     *      response=400,
                     *      description="Bad Request",
                     *   *@OA\JsonContent()
                     *   ),
                     * @OA\Response(
                     *      response=404,
                     *      description="not found",
                     *   *@OA\JsonContent()
                     *   )
                     *      )
                     *     )
                     */

                    public function update{{ $names["singular_model_name"] }}({{ $names["singular_model_name"] }}UpdateRequest $request)
                    {

                        try {
                            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
                            return DB::transaction(function () use ($request) {
                                if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_update')) {
                                    return response()->json([
                                        "message" => "You can not perform this action"
                                    ], 401);
                                }
                                $request_data = $request->validated();



                                ${{ $names["singular_table_name"] }}_query_params = [
                                    "id" => $request_data["id"],
                                ];

                                ${{ $names["singular_table_name"] }} = $names["singular_model_name"]::where($names["singular_table_name"]_query_params)->first();

if (${{ $names["singular_table_name"] }}) {
${{ $names["singular_table_name"] }}->fill(collect($request_data)->only([
    'name',
    'description',
    // "is_default",
    // "is_active",
    // "business_id",
    // "created_by"
])->toArray());
${{ $names["singular_table_name"] }}->save();
} else {
                                    return response()->json([
                                        "message" => "something went wrong."
                                    ], 500);
                                }




                                return response(${{ $names["singular_table_name"] }}, 201);
                            });
                        } catch (Exception $e) {
                            error_log($e->getMessage());
                            return $this->sendError($e, 500, $request);
                        }
                    }
                    /**
                     *
                     * @OA\Put(
                     *      path="/v1.0/{{ $names["api_name"] }}/toggle-active",
                     *      operationId="toggleActive{{ $names["singular_model_name"] }}",
                     *      tags={"{{ $names["table_name"] }}"},
                     *       security={
                     *           {"bearerAuth": {}}
                     *       },
                     *      summary="This method is to toggle {{ $names["plural_comment_name"] }}",
                     *      description="This method is to toggle {{ $names["plural_comment_name"] }}",
                     *
                     *  @OA\RequestBody(
                     *         required=true,
                     *         @OA\JsonContent(

                     *           @OA\Property(property="id", type="string", format="number",example="1"),
                     *
                     *         ),
                     *      ),
                     *      @OA\Response(
                     *          response=200,
                     *          description="Successful operation",
                     *       @OA\JsonContent(),
                     *       ),
                     *      @OA\Response(
                     *          response=401,
                     *          description="Unauthenticated",
                     * @OA\JsonContent(),
                     *      ),
                     *        @OA\Response(
                     *          response=422,
                     *          description="Unprocesseble Content",
                     *    @OA\JsonContent(),
                     *      ),
                     *      @OA\Response(
                     *          response=403,
                     *          description="Forbidden",
                     *   @OA\JsonContent()
                     * ),
                     *  * @OA\Response(
                     *      response=400,
                     *      description="Bad Request",
                     *   *@OA\JsonContent()
                     *   ),
                     * @OA\Response(
                     *      response=404,
                     *      description="not found",
                     *   *@OA\JsonContent()
                     *   )
                     *      )
                     *     )
                     */

                    public function toggleActive{{ $names["singular_model_name"] }}(GetIdRequest $request)
                    {

                        try {
                            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
                            if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_activate')) {
                                return response()->json([
                                    "message" => "You can not perform this action"
                                ], 401);
                            }
                            $request_data = $request->validated();

                            ${{ $names["singular_table_name"] }} =  {{ $names["singular_model_name"] }}::where([
                                "id" => $request_data["id"],
                            ])
                                ->first();
                            if (!${{ $names["singular_table_name"] }}) {

                                return response()->json([
                                    "message" => "no data found"
                                ], 404);
                            }









                            $should_update = 0;
                            $should_disable = 0;
                            if (empty(auth()->user()->business_id)) {

                                if (auth()->user()->hasRole('superadmin')) {
                                    if ((${{ $names["singular_table_name"] }}->business_id != NULL || ${{ $names["singular_table_name"] }}->is_default != 1)) {

                                        return response()->json([
                                            "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                                        ], 403);
                                    } else {
                                        $should_update = 1;
                                    }
                                } else {
                                    if (${{ $names["singular_table_name"] }}->business_id != NULL) {

                                        return response()->json([
                                            "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                                        ], 403);
                                    } else if (${{ $names["singular_table_name"] }}->is_default == 0) {

                                        if(${{ $names["singular_table_name"] }}->created_by != auth()->user()->id) {

                                            return response()->json([
                                                "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                                            ], 403);
                                        }
                                        else {
                                            $should_update = 1;
                                        }



                                    }
                                    else {
                                     $should_disable = 1;

                                    }
                                }
                            } else {
                                if (${{ $names["singular_table_name"] }}->business_id != NULL) {
                                    if ((${{ $names["singular_table_name"] }}->business_id != auth()->user()->business_id)) {

                                        return response()->json([
                                            "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                                        ], 403);
                                    } else {
                                        $should_update = 1;
                                    }
                                } else {
                                    if (${{ $names["singular_table_name"] }}->is_default == 0) {
                                        if (${{ $names["singular_table_name"] }}->created_by != auth()->user()->created_by) {

                                            return response()->json([
                                                "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                                            ], 403);
                                        } else {
                                            $should_disable = 1;

                                        }
                                    } else {
                                        $should_disable = 1;

                                    }
                                }
                            }

                            if ($should_update) {
                                ${{ $names["singular_table_name"] }}->update([
                                    'is_active' => !${{ $names["singular_table_name"] }}->is_active
                                ]);
                            }

                            if($should_disable) {
                                $disabled_{{ $names["singular_table_name"] }} =    Disabled{{ $names["singular_model_name"] }}::where([
                                    '{{ $names["singular_table_name"] }}_id' => ${{ $names["singular_table_name"] }}->id,
                                    'business_id' => auth()->user()->business_id,
                                    'created_by' => auth()->user()->id,
                                ])->first();
                                if(!$disabled_{{ $names["singular_table_name"] }}) {
                                    Disabled{{ $names["singular_model_name"] }}::create([
                                        '{{ $names["singular_table_name"] }}_id' => ${{ $names["singular_table_name"] }}->id,
                                        'business_id' => auth()->user()->business_id,
                                        'created_by' => auth()->user()->id,
                                    ]);
                                } else {
                                    $disabled_{{ $names["singular_table_name"] }}->delete();
                                }
                            }


                            return response()->json(['message' => '{{ $names["singular_comment_name"] }} status updated successfully'], 200);
                        } catch (Exception $e) {
                            error_log($e->getMessage());
                            return $this->sendError($e, 500, $request);
                        }
                    }

                    /**
                     *
                     * @OA\Get(
                     *      path="/v1.0/{{ $names["api_name"] }}",
                     *      operationId="get{{ $names["plural_model_name"] }}",
                     *      tags={"{{ $names["table_name"] }}"},
                     *       security={
                     *           {"bearerAuth": {}}
                     *       },

                     *              @OA\Parameter(
                     *         name="per_page",
                     *         in="query",
                     *         description="per_page",
                     *         required=true,
                     *  example="6"
                     *      ),
                *      * *  @OA\Parameter(
                     * name="is_active",
                     * in="query",
                     * description="is_active",
                     * required=true,
                     * example="1"
                     * ),
                     *      * *  @OA\Parameter(
                     * name="start_date",
                     * in="query",
                     * description="start_date",
                     * required=true,
                     * example="2019-06-29"
                     * ),
                     * *  @OA\Parameter(
                     * name="end_date",
                     * in="query",
                     * description="end_date",
                     * required=true,
                     * example="2019-06-29"
                     * ),
                     * *  @OA\Parameter(
                     * name="search_key",
                     * in="query",
                     * description="search_key",
                     * required=true,
                     * example="search_key"
                     * ),
                     * *  @OA\Parameter(
                     * name="order_by",
                     * in="query",
                     * description="order_by",
                     * required=true,
                     * example="ASC"
                     * ),
                     * *  @OA\Parameter(
                      * name="id",
                      * in="query",
                      * description="id",
                      * required=true,
                      * example="ASC"
                      * ),
                      * *  @OA\Parameter(
                        * name="is_single_search",
                        * in="query",
                        * description="is_single_search",
                        * required=true,
                        * example="ASC"
                        * ),




                     *      summary="This method is to get {{ $names["plural_comment_name"] }}  ",
                     *      description="This method is to get {{ $names["plural_comment_name"] }} ",
                     *

                     *      @OA\Response(
                     *          response=200,
                     *          description="Successful operation",
                     *       @OA\JsonContent(),
                     *       ),
                     *      @OA\Response(
                     *          response=401,
                     *          description="Unauthenticated",
                     * @OA\JsonContent(),
                     *      ),
                     *        @OA\Response(
                     *          response=422,
                     *          description="Unprocesseble Content",
                     *    @OA\JsonContent(),
                     *      ),
                     *      @OA\Response(
                     *          response=403,
                     *          description="Forbidden",
                     *   @OA\JsonContent()
                     * ),
                     *  * @OA\Response(
                     *      response=400,
                     *      description="Bad Request",
                     *   *@OA\JsonContent()
                     *   ),
                     * @OA\Response(
                     *      response=404,
                     *      description="not found",
                     *   *@OA\JsonContent()
                     *   )
                     *      )
                     *     )
                     */

                    public function get{{ $names["plural_model_name"] }}(Request $request)
                    {
                        try {
                            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
                            if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_view')) {
                                return response()->json([
                                    "message" => "You can not perform this action"
                                ], 401);
                            }
                            $created_by  = NULL;
                            if(auth()->user()->business) {
                                $created_by = auth()->user()->business->created_by;
                            }



                            ${{ $names["table_name"] }} = {{ $names["singular_model_name"] }}::when(empty($request->user()->business_id), function ($query) use ($request, $created_by) {
                                if (auth()->user()->hasRole('superadmin')) {
                                    return $query->where('{{ $names["table_name"] }}.business_id', NULL)
                                        ->where('{{ $names["table_name"] }}.is_default', 1)
                                        ->when(isset($request->is_active), function ($query) use ($request) {
                                            return $query->where('{{ $names["table_name"] }}.is_active', intval($request->is_active));
                                        });
                                } else {
                                    return $query

                                    ->where(function($query) use($request) {
                                        $query->where('{{ $names["table_name"] }}.business_id', NULL)
                                        ->where('{{ $names["table_name"] }}.is_default', 1)
                                        ->where('{{ $names["table_name"] }}.is_active', 1)
                                        ->when(isset($request->is_active), function ($query) use ($request) {
                                            if(intval($request->is_active)) {
                                                return $query->whereDoesntHave("disabled", function($q) {
                                                    $q->whereIn("disabled_{{ $names["table_name"] }}.created_by", [auth()->user()->id]);
                                                });
                                            }

                                        })
                                        ->orWhere(function ($query) use ($request) {
                                            $query->where('{{ $names["table_name"] }}.business_id', NULL)
                                                ->where('{{ $names["table_name"] }}.is_default', 0)
                                                ->where('{{ $names["table_name"] }}.created_by', auth()->user()->id)
                                                ->when(isset($request->is_active), function ($query) use ($request) {
                                                    return $query->where('{{ $names["table_name"] }}.is_active', intval($request->is_active));
                                                });
                                        });

                                    });
                                }
                            })
                                ->when(!empty($request->user()->business_id), function ($query) use ($request, $created_by) {
                                    return $query
                                    ->where(function($query) use($request, $created_by) {


                                        $query->where('{{ $names["table_name"] }}.business_id', NULL)
                                        ->where('{{ $names["table_name"] }}.is_default', 1)
                                        ->where('{{ $names["table_name"] }}.is_active', 1)
                                        ->whereDoesntHave("disabled", function($q) use($created_by) {
                                            $q->whereIn("disabled_{{ $names["table_name"] }}.created_by", [$created_by]);
                                        })
                                        ->when(isset($request->is_active), function ($query) use ($request, $created_by)  {
                                            if(intval($request->is_active)) {
                                                return $query->whereDoesntHave("disabled", function($q) use($created_by) {
                                                    $q->whereIn("disabled_{{ $names["table_name"] }}.business_id",[auth()->user()->business_id]);
                                                });
                                            }

                                        })


                                        ->orWhere(function ($query) use($request, $created_by){
                                            $query->where('{{ $names["table_name"] }}.business_id', NULL)
                                                ->where('{{ $names["table_name"] }}.is_default', 0)
                                                ->where('{{ $names["table_name"] }}.created_by', $created_by)
                                                ->where('{{ $names["table_name"] }}.is_active', 1)

                                                ->when(isset($request->is_active), function ($query) use ($request) {
                                                    if(intval($request->is_active)) {
                                                        return $query->whereDoesntHave("disabled", function($q) {
                                                            $q->whereIn("disabled_{{ $names["table_name"] }}.business_id",[auth()->user()->business_id]);
                                                        });
                                                    }

                                                })


                                                ;
                                        })
                                        ->orWhere(function ($query) use($request) {
                                            $query->where('{{ $names["table_name"] }}.business_id', auth()->user()->business_id)
                                                ->where('{{ $names["table_name"] }}.is_default', 0)
                                                ->when(isset($request->is_active), function ($query) use ($request) {
                                                    return $query->where('{{ $names["table_name"] }}.is_active', intval($request->is_active));
                                                });
                                        });
                                    });

                                })
                                ->when(!empty($request->id), function ($query) use ($request) {
                                  return $query->where('{{ $names["table_name"] }}.id', $request->id);
                              })
                                ->when(!empty($request->search_key), function ($query) use ($request) {
                                    return $query->where(function ($query) use ($request) {
                                        $term = $request->search_key;
                                        $query->where("{{ $names["table_name"] }}.name", "like", "%" . $term . "%")
                                            ->orWhere("{{ $names["table_name"] }}.description", "like", "%" . $term . "%");
                                    });
                                })

                                ->when(!empty($request->start_date), function ($query) use ($request) {
                                    return $query->where('{{ $names["table_name"] }}.created_at', ">=", $request->start_date);
                                })
                                ->when(!empty($request->end_date), function ($query) use ($request) {
                                    return $query->where('{{ $names["table_name"] }}.created_at', "<=", ($request->end_date . ' 23:59:59'));
                                })
                                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                                    return $query->orderBy("{{ $names["table_name"] }}.id", $request->order_by);
                                }, function ($query) {
                                    return $query->orderBy("{{ $names["table_name"] }}.id", "DESC");
                                })
                                ->when($request->filled("is_single_search") && $request->boolean("is_single_search"), function ($query) use ($request) {
                                  return $query->first();
                          }, function($query) {
                             return $query->when(!empty(request()->per_page), function ($query) {
                                  return $query->paginate(request()->per_page);
                              }, function ($query) {
                                  return $query->get();
                              });
                          });

                          if($request->filled("is_single_search") && empty(${{ $names["table_name"] }})){
                     throw new Exception("No data found",404);
                          }


                            return response()->json(${{ $names["table_name"] }}, 200);
                        } catch (Exception $e) {

                            return $this->sendError($e, 500, $request);
                        }
                    }




                    /**
                     *
                     *     @OA\Delete(
                     *      path="/v1.0/{{ $names["api_name"] }}/{ids}",
                     *      operationId="delete{{ $names["plural_model_name"] }}ByIds",
                     *      tags={"{{ $names["table_name"] }}"},
                     *       security={
                     *           {"bearerAuth": {}}
                     *       },
                     *              @OA\Parameter(
                     *         name="ids",
                     *         in="path",
                     *         description="ids",
                     *         required=true,
                     *  example="1,2,3"
                     *      ),
                     *      summary="This method is to delete {{ $names["singular_comment_name"] }} by id",
                     *      description="This method is to delete {{ $names["singular_comment_name"] }} by id",
                     *

                     *      @OA\Response(
                     *          response=200,
                     *          description="Successful operation",
                     *       @OA\JsonContent(),
                     *       ),
                     *      @OA\Response(
                     *          response=401,
                     *          description="Unauthenticated",
                     * @OA\JsonContent(),
                     *      ),
                     *        @OA\Response(
                     *          response=422,
                     *          description="Unprocesseble Content",
                     *    @OA\JsonContent(),
                     *      ),
                     *      @OA\Response(
                     *          response=403,
                     *          description="Forbidden",
                     *   @OA\JsonContent()
                     * ),
                     *  * @OA\Response(
                     *      response=400,
                     *      description="Bad Request",
                     *   *@OA\JsonContent()
                     *   ),
                     * @OA\Response(
                     *      response=404,
                     *      description="not found",
                     *   *@OA\JsonContent()
                     *   )
                     *      )
                     *     )
                     */

                    public function delete{{ $names["plural_model_name"] }}ByIds(Request $request, $ids)
                    {

                        try {
                            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
                            if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_delete')) {
                                return response()->json([
                                    "message" => "You can not perform this action"
                                ], 401);
                            }

                            $idsArray = explode(',', $ids);
                            $existingIds = {{ $names["singular_model_name"] }}::whereIn('id', $idsArray)
                                ->when(empty($request->user()->business_id), function ($query) use ($request) {
                                    if ($request->user()->hasRole("superadmin")) {
                                        return $query->where('{{ $names["table_name"] }}.business_id', NULL)
                                            ->where('{{ $names["table_name"] }}.is_default', 1);
                                    } else {
                                        return $query->where('{{ $names["table_name"] }}.business_id', NULL)
                                            ->where('{{ $names["table_name"] }}.is_default', 0)
                                            ->where('{{ $names["table_name"] }}.created_by', $request->user()->id);
                                    }
                                })
                                ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                                    return $query->where('{{ $names["table_name"] }}.business_id', $request->user()->business_id)
                                        ->where('{{ $names["table_name"] }}.is_default', 0);
                                })
                                ->select('id')
                                ->get()
                                ->pluck('id')
                                ->toArray();
                            $nonExistingIds = array_diff($idsArray, $existingIds);

                            if (!empty($nonExistingIds)) {

                                return response()->json([
                                    "message" => "Some or all of the specified data do not exist."
                                ], 404);
                            }


                            $conflictingUsers = User::whereIn("{{ $names["singular_table_name"] }}_id", $existingIds)->get([
                                'id', 'first_Name',
                                'last_Name',
                            ]);

                            if ($conflictingUsers->isNotEmpty()) {
                                return response()->json([
                                    "message" => "Some users are associated with the specified {{ $names["plural_comment_name"] }}",
                                    "conflicting_users" => $conflictingUsers
                                ], 409);
                            }



                            {{ $names["singular_model_name"] }}::destroy($existingIds);


                            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
                        } catch (Exception $e) {

                            return $this->sendError($e, 500, $request);
                        }
                    }
                }







  </code></pre>
                <button class="copy-button" onclick="copyToClipboard('controller')">Copy</button>
            </div>
        </div>
    </div>
</div>
