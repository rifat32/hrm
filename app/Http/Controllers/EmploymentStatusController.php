<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmploymentStatusCreateRequest;
use App\Http\Requests\EmploymentStatusUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\EmploymentStatus;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmploymentStatusController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/employment-statuses",
     *      operationId="createEmploymentStatus",
     *      tags={"administrator.employment_statuses"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store employment status",
     *      description="This method is to store employment status",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 * @OA\Property(property="name", type="string", format="string", example="tttttt"),
 *  * @OA\Property(property="color", type="string", format="string", example="red"),
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

    public function createEmploymentStatus(EmploymentStatusCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employment_status_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();




                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                // $request_data["created_by"] = $request->user()->id;

                $employment_status =  EmploymentStatus::create($request_data);




                return response($employment_status, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/employment-statuses",
     *      operationId="updateEmploymentStatus",
     *      tags={"administrator.employment_statuses"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update employment status ",
     *      description="This method is to update employment status",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="name", type="string", format="string", example="tttttt"),
 *  *  * @OA\Property(property="color", type="string", format="string", example="red"),
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

    public function updateEmploymentStatus(EmploymentStatusUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employment_status_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();



                $employment_status_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $employment_status_prev = EmploymentStatus::where($employment_status_query_params)
                    ->first();
                if (!$employment_status_prev) {
                    return response()->json([
                        "message" => "no employment status found"
                    ], 404);
                }

                $employment_status  =  tap(EmploymentStatus::where($employment_status_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'color',
                        'description',
                        // "is_active",
                        // "business_id",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$employment_status) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($employment_status, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/employment-statuses",
     *      operationId="getEmploymentStatuses",
     *      tags={"administrator.employment_statuses"},
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

     *      summary="This method is to get employment statuses  ",
     *      description="This method is to get employment statuses ",
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

    public function getEmploymentStatuses(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('employment_status_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $employment_statuses = EmploymentStatus::where(
                [
                    "employment_statuses.business_id" => $business_id
                ]
            )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employment_statuses.name", "like", "%" . $term . "%")
                            ->orWhere("employment_statuses.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employment_statuses.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employment_statuses.created_at', "<=", $request->end_date);
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("employment_statuses.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("employment_statuses.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($employment_statuses, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/employment-statuses/{id}",
     *      operationId="getEmploymentStatusById",
     *      tags={"administrator.employment_statuses"},
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
     *      summary="This method is to get employment status by id",
     *      description="This method is to get employment status by id",
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


    public function getEmploymentStatusById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('employment_status_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $employment_status =  EmploymentStatus::where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->first();
            if (!$employment_status) {
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($employment_status, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/employment-statuses/{ids}",
     *      operationId="deleteEmploymentStatusesByIds",
     *      tags={"administrator.employment_statuses"},
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
     *      summary="This method is to delete employment statuses by ids",
     *      description="This method is to delete employment statuses by ids",
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

    public function deleteEmploymentStatusesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('employment_status_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = EmploymentStatus::where([
                "business_id" => $business_id
            ])
                ->whereIn('id', $idsArray)
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
            EmploymentStatus::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully"], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
