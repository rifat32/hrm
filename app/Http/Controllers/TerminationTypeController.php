<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\TerminationTypeCreateRequest;
use App\Http\Requests\TerminationTypeUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\DisabledTerminationType;
use App\Models\TerminationType;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TerminationTypeController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/termination-types",
     *      operationId="createTerminationType",
     *      tags={"termination_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store termination type",
     *      description="This method is to store termination type",
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

    public function createTerminationType(TerminationTypeCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('termination_type_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["is_active"] = 1;
                $request_data["is_default"] = 0;
                $request_data["created_by"] = $request->user()->id;
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $termination_type =  TerminationType::create($request_data);




                return response($termination_type, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/termination-types",
     *      operationId="updateTerminationType",
     *      tags={"termination_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update termination type ",
     *      description="This method is to update termination type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
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

    public function updateTerminationType(TerminationTypeUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('termination_type_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $termination_type_query_params = [
                    "id" => $request_data["id"],
                ];

                $termination_type  =  tap(TerminationType::where($termination_type_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$termination_type) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($termination_type, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/termination-types/toggle-active",
     *      operationId="toggleActiveTerminationType",
     *      tags={"termination_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle designatation",
     *      description="This method is to toggle designatation",
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

    public function toggleActiveTerminationType(GetIdRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('termination_type_activate')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $termination_type =  TerminationType::where([
                "id" => $request_data["id"],
            ])
                ->first();
            if (!$termination_type) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }
            $should_update = 0;
            $should_disable = 0;
            if (empty(auth()->user()->business_id)) {

                if (auth()->user()->hasRole('superadmin')) {
                    if (($termination_type->business_id != NULL)) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination type due to role restrictions."
                        ], 403);
                    } else {
                        $should_update = 1;
                    }
                } else {
                    if ($termination_type->business_id != NULL) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination type due to role restrictions."
                        ], 403);
                    } else if ($termination_type->is_default == 0) {

                        if ($termination_type->created_by != auth()->user()->id) {

                            return response()->json([
                                "message" => "You do not have permission to update this termination type due to role restrictions."
                            ], 403);
                        } else {
                            $should_update = 1;
                        }
                    } else {
                        $should_disable = 1;
                    }
                }
            } else {
                if ($termination_type->business_id != NULL) {
                    if (($termination_type->business_id != auth()->user()->business_id)) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination type due to role restrictions."
                        ], 403);
                    } else {
                        $should_update = 1;
                    }
                } else {
                    if ($termination_type->is_default == 0) {
                        if ($termination_type->created_by != auth()->user()->created_by) {

                            return response()->json([
                                "message" => "You do not have permission to update this termination type due to role restrictions."
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
                $termination_type->update([
                    'is_active' => !$termination_type->is_active
                ]);
            }

            if ($should_disable) {

                $disabled_termination_type =    DisabledTerminationType::where([
                    'termination_type_id' => $termination_type->id,
                    'business_id' => auth()->user()->business_id,
                    'created_by' => auth()->user()->id,
                ])->first();
                if (!$disabled_termination_type) {
                    DisabledTerminationType::create([
                        'termination_type_id' => $termination_type->id,
                        'business_id' => auth()->user()->business_id,
                        'created_by' => auth()->user()->id,
                    ]);
                } else {
                    $disabled_termination_type->delete();
                }
            }


            return response()->json(['message' => 'Termination Type status updated successfully'], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/termination-types",
     *      operationId="getTerminationTypes",
     *      tags={"termination_types"},
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

     *      summary="This method is to get termination types  ",
     *      description="This method is to get termination types ",
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

    public function getTerminationTypes(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('termination_type_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $termination_types = TerminationType::when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                if (auth()->user()->hasRole('superadmin')) {
                    return $query->where('termination_types.business_id', NULL)
                        ->where('termination_types.is_default', 1)
                        ->when(isset($request->is_active), function ($query) use ($request) {
                            return $query->where('termination_types.is_active', request()->boolean("is_active"));
                        });
                } else {
                    return $query

                        ->where(function ($query) use ($request) {
                            $query->where('termination_types.business_id', NULL)
                                ->where('termination_types.is_default', 1)
                                ->where('termination_types.is_active', 1)
                                ->when(isset($request->is_active), function ($query) use ($request) {
                                    if (request()->boolean("is_active")) {
                                        return $query->whereDoesntHave("disabled", function ($q) {
                                            $q->whereIn("disabled_termination_types.created_by", [auth()->user()->id]);
                                        });
                                    }
                                })
                                ->orWhere(function ($query) use ($request) {
                                    $query->where('termination_types.business_id', NULL)
                                        ->where('termination_types.is_default', 0)
                                        ->where('termination_types.created_by', auth()->user()->id)
                                        ->when(isset($request->is_active), function ($query) use ($request) {
                                            return $query->where('termination_types.is_active', request()->boolean("is_active"));
                                        });
                                });
                        });
                }
            })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                    return $query
                        ->where(function ($query) use ($request, $created_by) {


                            $query->where('termination_types.business_id', NULL)
                                ->where('termination_types.is_default', 1)
                                ->where('termination_types.is_active', 1)
                                ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                                    $q->whereIn("disabled_termination_types.created_by", [$created_by]);
                                })
                                ->when(isset($request->is_active), function ($query) use ($request, $created_by) {
                                    if (request()->boolean("is_active")) {
                                        return $query->whereDoesntHave("disabled", function ($q) use ($created_by) {
                                            $q->whereIn("disabled_termination_types.business_id", [auth()->user()->business_id]);
                                        });
                                    }
                                })


                                ->orWhere(function ($query) use ($request, $created_by) {
                                    $query->where('termination_types.business_id', NULL)
                                        ->where('termination_types.is_default', 0)
                                        ->where('termination_types.created_by', $created_by)
                                        ->where('termination_types.is_active', 1)

                                        ->when(isset($request->is_active), function ($query) use ($request) {
                                            if (request()->boolean("is_active")) {
                                                return $query->whereDoesntHave("disabled", function ($q) {
                                                    $q->whereIn("disabled_termination_types.business_id", [auth()->user()->business_id]);
                                                });
                                            }
                                        });
                                })
                                ->orWhere(function ($query) use ($request) {
                                    $query->where('termination_types.business_id', auth()->user()->business_id)
                                        ->where('termination_types.is_default', 0)
                                        ->when(isset($request->is_active), function ($query) use ($request) {
                                            return $query->where('termination_types.is_active', request()->boolean("is_active"));
                                        });;
                                });
                        });
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("termination_types.name", "like", "%" . $term . "%")
                            ->orWhere("termination_types.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('termination_types.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('termination_types.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("termination_types.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("termination_types.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($termination_types, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/termination-types/{id}",
     *      operationId="getTerminationTypeById",
     *      tags={"termination_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get termination type by id",
     *      description="This method is to get termination type by id",
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


    public function getTerminationTypeById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('termination_type_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $termination_type =  TerminationType::where([
                "termination_types.id" => $id,
            ])

                ->first();

            if (!$termination_type) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            if (empty(auth()->user()->business_id)) {

                if (auth()->user()->hasRole('superadmin')) {
                    if (($termination_type->business_id != NULL )) {


                        return response()->json([
                            "message" => "You do not have permission to update this termination type due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($termination_type->business_id != NULL) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination type due to role restrictions."
                        ], 403);
                    } else if ($termination_type->is_default == 0 && $termination_type->created_by != auth()->user()->id) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination type due to role restrictions."
                        ], 403);
                    }
                }
            } else {
                if ($termination_type->business_id != NULL) {
                    if (($termination_type->business_id != auth()->user()->business_id)) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination type due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($termination_type->is_default == 0) {
                        if ($termination_type->created_by != auth()->user()->created_by) {

                            return response()->json([
                                "message" => "You do not have permission to update this termination type due to role restrictions."
                            ], 403);
                        }
                    }
                }
            }



            return response()->json($termination_type, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/termination-types/{ids}",
     *      operationId="deleteTerminationTypesByIds",
     *      tags={"termination_types"},
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
     *      summary="This method is to delete termination type by id",
     *      description="This method is to delete termination type by id",
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

    public function deleteTerminationTypesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('termination_type_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = TerminationType::whereIn('id', $idsArray)
                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if ($request->user()->hasRole("superadmin")) {
                        return $query->where('termination_types.business_id', NULL)
                            ->where('termination_types.is_default', 1);
                    } else {
                        return $query->where('termination_types.business_id', NULL)
                            ->where('termination_types.is_default', 0)
                            ->where('termination_types.created_by', $request->user()->id);
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return $query->where('termination_types.business_id', auth()->user()->business_id)
                        ->where('termination_types.is_default', 0);
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


            $conflictingUsers = User::
            whereHas("terminations",function($query) use($existingIds) {
                $query->whereIn("terminations.termination_reason_id", $existingIds);
            })

            ->get([
                'users.id', 'users.first_Name',
                'users.last_Name',
            ]);

            if ($conflictingUsers->isNotEmpty()) {
                return response()->json([
                    "message" => "Some users are associated with the specified termination types",
                    "conflicting_users" => $conflictingUsers
                ], 409);
            }



            TerminationType::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
