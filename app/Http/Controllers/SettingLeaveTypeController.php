<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\SettingLeaveTypeCreateRequest;
use App\Http\Requests\SettingLeaveTypeUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Leave;
use App\Models\SettingLeaveType;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingLeaveTypeController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-leave-types",
     *      operationId="createSettingLeaveType",
     *      tags={"settings.setting_leave_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting leave type",
     *      description="This method is to store setting leave type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *     @OA\Property(property="is_active", type="boolean", example="1"),
 *     @OA\Property(property="is_earning_enabled", type="boolean", example="1"),
 *     @OA\Property(property="name", type="string", format="string", example="yy"),
 *     @OA\Property(property="type", type="string", format="string", example="paid"),
 *     @OA\Property(property="amount", type="string", format="string", example="30")
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

    public function createSettingLeaveType(SettingLeaveTypeCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('setting_leave_type_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();







                if (empty($request->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                $request_data["is_active"] = true;
                $request_data["is_default"] = 1;
                 $request_data["created_by"] = $request->user()->id;
                } else {
                    $request_data["business_id"] = $request->user()->business_id;
                    $request_data["is_active"] = true;
                    $request_data["is_default"] = 0;
                 $request_data["created_by"] = $request->user()->id;
                }




                $setting_leave_type =  SettingLeaveType::create($request_data);




                return response($setting_leave_type, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/setting-leave-types",
     *      operationId="updateSettingLeaveType",
     *      tags={"settings.setting_leave_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update setting leave type ",
     *      description="This method is to update setting leave type",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
*     @OA\Property(property="is_active", type="boolean", example="1"),
 *     @OA\Property(property="is_earning_enabled", type="boolean", example="1"),
 *     @OA\Property(property="name", type="string", format="string", example="yy"),
 *     @OA\Property(property="type", type="string", format="string", example="paid"),
 *     @OA\Property(property="amount", type="string", format="string", example="30"),



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

    public function updateSettingLeaveType(SettingLeaveTypeUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('setting_leave_type_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();



                $setting_leave_type_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $setting_leave_type_prev = SettingLeaveType::where($setting_leave_type_query_params)
                    ->first();
                if (!$setting_leave_type_prev) {
                    return response()->json([
                        "message" => "no setting leave type found"
                    ], 404);
                }
                if (empty($request->user()->business_id)) {
                    if(!($setting_leave_type_prev->business_id == NULL && $setting_leave_type_prev->is_default == 1)) {
                        return response()->json([
                            "message" => "You do not have permission to update this setting leave type due to role restrictions."
                        ], 403);
                    }

                } else {
                    if(!($setting_leave_type_prev->business_id == $request->user()->business_id)) {
                        return response()->json([
                            "message" => "You do not have permission to update this setting leave type due to role restrictions."
                        ], 403);
                    }
                }
                $setting_leave_type  =  tap(SettingLeaveType::where($setting_leave_type_query_params))->update(
                    collect($request_data)->only([
                        'name',
        'type',
        'amount',
        'description',
        'is_earning_enabled',
        "is_active"



                         // "is_default",
                        // "is_active",
                        // "business_id",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$setting_leave_type) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($setting_leave_type, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

   /**
     *
     * @OA\Put(
     *      path="/v1.0/setting-leave-types/toggle-active",
     *      operationId="toggleActiveSettingLeaveType",
     *      tags={"settings.setting_leave_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle user leave type",
     *      description="This method is to toggle user leave type",
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

     public function toggleActiveSettingLeaveType(GetIdRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('setting_leave_type_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();

             $setting_leave_type =  SettingLeaveType::where([
                "id" => $request_data["id"],
            ])
            ->when(empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', NULL)
                             ->where('setting_leave_types.is_default', 1);
            })
            ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', $request->user()->business_id);
            })
                ->first();
            if (!$setting_leave_type) {
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

             $setting_leave_type->update([
                 'is_active' => !$setting_leave_type->is_active
             ]);

             return response()->json(['message' => 'User status updated successfully'], 200);
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }
      /**
     *
     * @OA\Put(
     *      path="/v1.0/setting-leave-types/toggle-earning-enabled",
     *      operationId="toggleEarningEnabledSettingLeaveType",
     *      tags={"settings.setting_leave_types"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle Earning Enabled ",
     *      description="This method is to toggle Earning Enabled",
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

     public function toggleEarningEnabledSettingLeaveType(GetIdRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('setting_leave_type_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();

             $setting_leave_type =  SettingLeaveType::where([
                "id" => $request_data["id"],
            ])
            ->when(empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', NULL)
                             ->where('setting_leave_types.is_default', 1);
            })
            ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', $request->user()->business_id);
            })
                ->first();
            if (!$setting_leave_type) {
                return response()->json([
                    "message" => "no data found"
                ] , 404);
            }



             $setting_leave_type->update([
                 'is_earning_enabled' => !$setting_leave_type->is_earning_enabled
             ]);

             return response()->json(['message' => 'User status updated successfully'], 200);
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-leave-types",
     *      operationId="getSettingLeaveTypes",
     *      tags={"settings.setting_leave_types"},
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

     *      summary="This method is to get setting leave types  ",
     *      description="This method is to get setting leave types ",
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

    public function getSettingLeaveTypes(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('setting_leave_type_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $setting_leave_types = SettingLeaveType::when(empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', NULL)
                             ->where('setting_leave_types.is_default', 1);
            })
            ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', $request->user()->business_id);
            })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("setting_leave_types.name", "like", "%" . $term . "%")
                            ->orWhere("setting_leave_types.type", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('setting_leave_types.created_at', ">=", Carbon::createFromFormat('d-m-Y', trim(($request->start_date)))->format('Y-m-d'));
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('setting_leave_types.created_at', "<=", Carbon::createFromFormat('d-m-Y H:i:s', trim($request->end_date . ' 23:59:59'))->format('Y-m-d'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("setting_leave_types.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("setting_leave_types.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($setting_leave_types, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-leave-types/{id}",
     *      operationId="getSettingLeaveTypeById",
     *      tags={"settings.setting_leave_types"},
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
     *      summary="This method is to get setting leave type by id",
     *      description="This method is to get setting leave type by id",
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


    public function getSettingLeaveTypeById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('setting_leave_type_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $setting_leave_type =  SettingLeaveType::where([
                "id" => $id,
            ])
            ->when(empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', NULL)
                             ->where('setting_leave_types.is_default', 1);
            })
            ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', $request->user()->business_id);
            })
                ->first();
            if (!$setting_leave_type) {
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($setting_leave_type, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/setting-leave-types/{ids}",
     *      operationId="deleteSettingLeaveTypesByIds",
     *      tags={"settings.setting_leave_types"},
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
     *      summary="This method is to delete setting leave type by id",
     *      description="This method is to delete setting leave type by id",
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

    public function deleteSettingLeaveTypesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('setting_leave_type_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = SettingLeaveType::whereIn('id', $idsArray)
            ->when(empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', NULL)
                             ->where('setting_leave_types.is_default', 1);
            })
            ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('setting_leave_types.business_id', $request->user()->business_id)
                ->where('setting_leave_types.is_default', 0);
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

          $leave_exists =  Leave::whereIn("leave_type_id",$existingIds)->exists();
            if($leave_exists) {
                $conflictingLeaves = Leave::whereIn("leave_type_id", $existingIds)->get(['id']);

                return response()->json([
                    "message" => "Some leaves are associated with the specified setting_leave_types",
                    "conflicting_leaves" => $conflictingLeaves
                ], 409);

            }




            SettingLeaveType::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
