<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\TerminationReasonCreateRequest;
use App\Http\Requests\TerminationReasonUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\DisabledTerminationReason;
use App\Models\TerminationReason;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TerminationReasonController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/termination-reasons",
     *      operationId="createTerminationReason",
     *      tags={"termination_reasons"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store termination reason",
     *      description="This method is to store termination reason",
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

    public function createTerminationReason(TerminationReasonCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('termination_reason_create')) {
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




                $termination_reason =  TerminationReason::create($request_data);




                return response($termination_reason, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/termination-reasons",
     *      operationId="updateTerminationReason",
     *      tags={"termination_reasons"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update termination reason ",
     *      description="This method is to update termination reason",
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

    public function updateTerminationReason(TerminationReasonUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('termination_reason_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $termination_reason_query_params = [
                    "id" => $request_data["id"],
                ];

                $termination_reason  =  tap(TerminationReason::where($termination_reason_query_params))->update(
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
                if (!$termination_reason) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($termination_reason, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/termination-reasons/toggle-active",
     *      operationId="toggleActiveTerminationReason",
     *      tags={"termination_reasons"},
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

    public function toggleActiveTerminationReason(GetIdRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('termination_reason_activate')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $termination_reason =  TerminationReason::where([
                "id" => $request_data["id"],
            ])
                ->first();
            if (!$termination_reason) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }
            $should_update = 0;
            $should_disable = 0;
            if (empty(auth()->user()->business_id)) {

                if (auth()->user()->hasRole('superadmin')) {
                    if (($termination_reason->business_id != NULL || $termination_reason->is_default != 1)) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination reason due to role restrictions."
                        ], 403);
                    } else {
                        $should_update = 1;
                    }
                } else {
                    if ($termination_reason->business_id != NULL) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination reason due to role restrictions."
                        ], 403);
                    } else if ($termination_reason->is_default == 0) {

                        if ($termination_reason->created_by != auth()->user()->id) {

                            return response()->json([
                                "message" => "You do not have permission to update this termination reason due to role restrictions."
                            ], 403);
                        } else {
                            $should_update = 1;
                        }
                    } else {
                        $should_disable = 1;
                    }
                }
            } else {
                if ($termination_reason->business_id != NULL) {
                    if (($termination_reason->business_id != auth()->user()->business_id)) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination reason due to role restrictions."
                        ], 403);
                    } else {
                        $should_update = 1;
                    }
                } else {
                    if ($termination_reason->is_default == 0) {
                        if ($termination_reason->created_by != auth()->user()->created_by) {

                            return response()->json([
                                "message" => "You do not have permission to update this termination reason due to role restrictions."
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
                $termination_reason->update([
                    'is_active' => !$termination_reason->is_active
                ]);
            }

            if ($should_disable) {

                $disabled_termination_reason =    DisabledTerminationReason::where([
                    'termination_reason_id' => $termination_reason->id,
                    'business_id' => auth()->user()->business_id,
                    'created_by' => auth()->user()->id,
                ])->first();
                if (!$disabled_termination_reason) {
                    DisabledTerminationReason::create([
                        'termination_reason_id' => $termination_reason->id,
                        'business_id' => auth()->user()->business_id,
                        'created_by' => auth()->user()->id,
                    ]);
                } else {
                    $disabled_termination_reason->delete();
                }
            }


            return response()->json(['message' => 'termination reason status updated successfully'], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/termination-reasons",
     *      operationId="getTerminationReasons",
     *      tags={"termination_reasons"},
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

     *      summary="This method is to get termination reasons  ",
     *      description="This method is to get termination reasons ",
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

    public function getTerminationReasons(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('termination_reason_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $termination_reasons = TerminationReason::when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                if (auth()->user()->hasRole('superadmin')) {
                    return $query->where('termination_reasons.business_id', NULL)
                        ->where('termination_reasons.is_default', 1)
                        ->when(isset($request->is_active), function ($query) use ($request) {
                            return $query->where('termination_reasons.is_active', intval($request->is_active));
                        });
                } else {
                    return $query

                        ->where(function ($query) use ($request) {
                            $query->where('termination_reasons.business_id', NULL)
                                ->where('termination_reasons.is_default', 1)
                                ->where('termination_reasons.is_active', 1)
                                ->when(isset($request->is_active), function ($query) use ($request) {
                                    if (intval($request->is_active)) {
                                        return $query->whereDoesntHave("disabled", function ($q) {
                                            $q->whereIn("disabled_termination_reasons.created_by", [auth()->user()->id]);
                                        });
                                    }
                                })
                                ->orWhere(function ($query) use ($request) {
                                    $query->where('termination_reasons.business_id', NULL)
                                        ->where('termination_reasons.is_default', 0)
                                        ->where('termination_reasons.created_by', auth()->user()->id)
                                        ->when(isset($request->is_active), function ($query) use ($request) {
                                            return $query->where('termination_reasons.is_active', intval($request->is_active));
                                        });
                                });
                        });
                }
            })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                    return $query
                        ->where(function ($query) use ($request, $created_by) {


                            $query->where('termination_reasons.business_id', NULL)
                                ->where('termination_reasons.is_default', 1)
                                ->where('termination_reasons.is_active', 1)
                                ->whereDoesntHave("disabled", function ($q) use ($created_by) {
                                    $q->whereIn("disabled_termination_reasons.created_by", [$created_by]);
                                })
                                ->when(isset($request->is_active), function ($query) use ($request, $created_by) {
                                    if (intval($request->is_active)) {
                                        return $query->whereDoesntHave("disabled", function ($q) use ($created_by) {
                                            $q->whereIn("disabled_termination_reasons.business_id", [auth()->user()->business_id]);
                                        });
                                    }
                                })


                                ->orWhere(function ($query) use ($request, $created_by) {
                                    $query->where('termination_reasons.business_id', NULL)
                                        ->where('termination_reasons.is_default', 0)
                                        ->where('termination_reasons.created_by', $created_by)
                                        ->where('termination_reasons.is_active', 1)

                                        ->when(isset($request->is_active), function ($query) use ($request) {
                                            if (intval($request->is_active)) {
                                                return $query->whereDoesntHave("disabled", function ($q) {
                                                    $q->whereIn("disabled_termination_reasons.business_id", [auth()->user()->business_id]);
                                                });
                                            }
                                        });
                                })
                                ->orWhere(function ($query) use ($request) {
                                    $query->where('termination_reasons.business_id', auth()->user()->business_id)
                                        ->where('termination_reasons.is_default', 0)
                                        ->when(isset($request->is_active), function ($query) use ($request) {
                                            return $query->where('termination_reasons.is_active', intval($request->is_active));
                                        });;
                                });
                        });
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("termination_reasons.name", "like", "%" . $term . "%")
                            ->orWhere("termination_reasons.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('termination_reasons.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('termination_reasons.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("termination_reasons.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("termination_reasons.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($termination_reasons, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/termination-reasons/{id}",
     *      operationId="getTerminationReasonById",
     *      tags={"termination_reasons"},
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
     *      summary="This method is to get termination reason by id",
     *      description="This method is to get termination reason by id",
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


    public function getTerminationReasonById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('termination_reason_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $termination_reason =  TerminationReason::where([
                "termination_reasons.id" => $id,
            ])

                ->first();

            if (!$termination_reason) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            if (empty(auth()->user()->business_id)) {

                if (auth()->user()->hasRole('superadmin')) {
                    if (($termination_reason->business_id != NULL || $termination_reason->is_default != 1)) {


                        return response()->json([
                            "message" => "You do not have permission to update this termination reason due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($termination_reason->business_id != NULL) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination reason due to role restrictions."
                        ], 403);
                    } else if ($termination_reason->is_default == 0 && $termination_reason->created_by != auth()->user()->id) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination reason due to role restrictions."
                        ], 403);
                    }
                }
            } else {
                if ($termination_reason->business_id != NULL) {
                    if (($termination_reason->business_id != auth()->user()->business_id)) {

                        return response()->json([
                            "message" => "You do not have permission to update this termination reason due to role restrictions."
                        ], 403);
                    }
                } else {
                    if ($termination_reason->is_default == 0) {
                        if ($termination_reason->created_by != auth()->user()->created_by) {

                            return response()->json([
                                "message" => "You do not have permission to update this termination reason due to role restrictions."
                            ], 403);
                        }
                    }
                }
            }



            return response()->json($termination_reason, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/termination-reasons/{ids}",
     *      operationId="deleteTerminationReasonsByIds",
     *      tags={"termination_reasons"},
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
     *      summary="This method is to delete termination reason by id",
     *      description="This method is to delete termination reason by id",
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

    public function deleteTerminationReasonsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('termination_reason_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = TerminationReason::whereIn('id', $idsArray)
                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if ($request->user()->hasRole("superadmin")) {
                        return $query->where('termination_reasons.business_id', NULL)
                            ->where('termination_reasons.is_default', 1);
                    } else {
                        return $query->where('termination_reasons.business_id', NULL)
                            ->where('termination_reasons.is_default', 0)
                            ->where('termination_reasons.created_by', $request->user()->id);
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return $query->where('termination_reasons.business_id', auth()->user()->business_id)
                        ->where('termination_reasons.is_default', 0);
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
                    "message" => "Some users are associated with the specified termination reasons",
                    "conflicting_users" => $conflictingUsers
                ], 409);
            }



            TerminationReason::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
