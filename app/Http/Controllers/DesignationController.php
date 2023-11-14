<?php

namespace App\Http\Controllers;

use App\Http\Requests\DesignationCreateRequest;
use App\Http\Requests\DesignationUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Designation;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesignationController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/designations",
     *      operationId="createDesignation",
     *      tags={"administrator.designations"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store designation",
     *      description="This method is to store designation",
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

    public function createDesignation(DesignationCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('designation_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();







                if ($request->user()->hasRole('superadmin')) {
                    $request_data["business_id"] = NULL;
                $request_data["is_active"] = true;
                $request_data["is_default"] = true;
                // $request_data["created_by"] = $request->user()->id;
                } else {
                    $request_data["business_id"] = $request->user()->business_id;
                    $request_data["is_active"] = true;
                    $request_data["is_default"] = false;
                    // $request_data["created_by"] = $request->user()->id;
                }




                $designation =  Designation::create($request_data);




                return response($designation, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/designations",
     *      operationId="updateDesignation",
     *      tags={"administrator.designations"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update designation ",
     *      description="This method is to update designation",
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

    public function updateDesignation(DesignationUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('designation_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();



                $designation_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $designation_prev = Designation::where($designation_query_params)
                    ->first();
                if (!$designation_prev) {
                    return response()->json([
                        "message" => "no designation found"
                    ], 404);
                }
                if ($request->user()->hasRole('superadmin')) {
                    if(!($designation_prev->business_id == NULL && $designation_prev->is_default == true)) {
                        return response()->json([
                            "message" => "You do not have permission to update this designation due to role restrictions."
                        ], 403);
                    }

                } else {
                    if(!($designation_prev->business_id == $request->user()->business_id)) {
                        return response()->json([
                            "message" => "You do not have permission to update this designation due to role restrictions."
                        ], 403);
                    }
                }
                $designation  =  tap(Designation::where($designation_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                         // "is_default",
                        // "is_active",
                        // "business_id",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$designation) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($designation, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/designations",
     *      operationId="getDesignations",
     *      tags={"administrator.designations"},
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

     *      summary="This method is to get designations  ",
     *      description="This method is to get designations ",
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

    public function getDesignations(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('designation_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $designations = Designation::when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
                return $query->where('designations.business_id', NULL)
                             ->where('designations.is_default', true);
            })
            ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                return $query->where('designations.business_id', $request->user()->business_id);
            })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("designations.name", "like", "%" . $term . "%")
                            ->orWhere("designations.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('designations.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('designations.created_at', "<=", $request->end_date);
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("designations.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("designations.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($designations, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/designations/{id}",
     *      operationId="getDesignationById",
     *      tags={"administrator.designations"},
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
     *      summary="This method is to get designation by id",
     *      description="This method is to get designation by id",
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


    public function getDesignationById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('designation_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $designation =  Designation::where([
                "id" => $id,
            ])
            ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
                return $query->where('designations.business_id', NULL)
                             ->where('designations.is_default', true);
            })
            ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                return $query->where('designations.business_id', $request->user()->business_id);
            })
                ->first();
            if (!$designation) {
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($designation, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/designations/{ids}",
     *      operationId="deleteDesignationsByIds",
     *      tags={"administrator.designations"},
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
     *      summary="This method is to delete designation by id",
     *      description="This method is to delete designation by id",
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

    public function deleteDesignationsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('designation_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = Designation::whereIn('id', $idsArray)
            ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
                return $query->where('designations.business_id', NULL)
                             ->where('designations.is_default', true);
            })
            ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                return $query->where('designations.business_id', $request->user()->business_id)
                ->where('designations.is_default', false);
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

          $user_exists =  User::whereIn("designation_id",$existingIds)->exists();
            if($user_exists) {
                $conflictingUsers = User::whereIn("designation_id", $existingIds)->get(['id', 'first_Name',
                'last_Name',]);

                return response()->json([
                    "message" => "Some users are associated with the specified designations",
                    "conflicting_users" => $conflictingUsers
                ], 409);

            }

            Designation::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
