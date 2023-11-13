<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkShiftCreateRequest;
use App\Http\Requests\WorkShiftUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\WorkShift;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkShiftController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/work-shifts",
     *      operationId="createWorkShift",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store work shift",
     *      description="This method is to store work shift",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
 *     @OA\Property(property="type", type="string", format="string", example="regular"),
 *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3}),

 *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
 * *     @OA\Property(property="details", type="string", format="array", example={
 *         {
 *             "off_day": "0",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "1",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "2",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "3",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "4",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "5",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "6",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         }
 *     }),

 *     @OA\Property(property="start_date", type="string", format="date", example="2023-11-16"),
 *     @OA\Property(property="end_date", type="string", format="date", example=""),
 *
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

    public function createWorkShift(WorkShiftCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "");

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('work_shift_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();

                $check_employee = $this->checkEmployees($request_data["users"]);
                if (!$check_employee["ok"]) {
                    return response()->json([
                        "message" => $check_employee["message"]
                    ], $check_employee["status"]);
                }

               $check_department = $this->checkDepartments($request_data["departments"]);
                    if (!$check_department["ok"]) {
                        return response()->json([
                            "message" => $check_department["message"]
                        ], $check_department["status"]);
                    }

                $request_data["business_id"] = $business_id;
                $request_data["is_active"] = true;
                $request_data["attendances_count"] = 0;


                $work_shift =  WorkShift::create($request_data);

                $work_shift->departments()->sync($request_data['departments'],[]);
                $work_shift->users()->sync($request_data['users'],[]);

                return response($work_shift, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/work-shifts",
     *      operationId="updateWorkShift",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update work shift ",
     *      description="This method is to update work_shift",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
 *     @OA\Property(property="type", type="string", format="string", example="regular"),
 *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3,4}),

 *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
 * *     @OA\Property(property="details", type="string", format="array", example={
 *         {
 *             "off_day": "0",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "1",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "2",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "3",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "4",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "5",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         },
 *         {
 *             "off_day": "6",
 *             "start_at": "",
 *             "end_at": "",
 *             "is_weekend": 0
 *         }
 *     }),

 *     @OA\Property(property="start_date", type="string", format="date", example="2023-11-16"),
 *     @OA\Property(property="end_date", type="string", format="date", example=""),
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

    public function updateWorkShift(WorkShiftUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('work_shift_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();

                $check_employee = $this->checkEmployees($request_data["users"]);
                if (!$check_employee["ok"]) {
                    return response()->json([
                        "message" => $check_employee["message"]
                    ], $check_employee["status"]);
                }
                $check_department = $this->checkDepartments($request_data["departments"]);
                    if (!$check_department["ok"]) {
                        return response()->json([
                            "message" => $check_department["message"]
                        ], $check_department["status"]);
                    }


                $work_shift_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $work_shift_prev = WorkShift::where($work_shift_query_params)
                    ->first();
                if (!$work_shift_prev) {
                    return response()->json([
                        "message" => "no work shift found"
                    ], 404);
                }

                $work_shift  =  tap(WorkShift::where($work_shift_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'type',
                        'departments',
                        'users',
                        // 'attendances_count',
                        'details',
                        'start_date',
                        'end_date',
                        // 'business_id',
                        // 'is_active',

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$work_shift) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }
                $work_shift->departments()->sync($request_data['departments'],[]);
                $work_shift->users()->sync($request_data['users'],[]);
                return response($work_shift, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shifts",
     *      operationId="getWorkShifts",
     *      tags={"administrator.work_shift"},
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

     *      summary="This method is to get work shifts  ",
     *      description="This method is to get work shifts ",
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

    public function getWorkShifts(Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('work_shift_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $work_shifts = WorkShift::where(
                [
                    "work_shifts.business_id" => $business_id
                ]
            )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("work_shifts.name", "like", "%" . $term . "%")
                            ->orWhere("work_shifts.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('work_shifts.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('work_shifts.created_at', "<=", $request->end_date);
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("work_shifts.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("work_shifts.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($work_shifts, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shifts/{id}",
     *      operationId="getWorkShiftById",
     *      tags={"administrator.work_shift"},
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
     *      summary="This method is to get work shift by id",
     *      description="This method is to get work shift by id",
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


    public function getWorkShiftById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('work_shift_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $work_shift =  WorkShift::where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->first();
            if (!$work_shift) {
                return response()->json([
                    "message" => "no work shift found"
                ], 404);
            }

            return response()->json($work_shift, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/work-shifts/{ids}",
     *      operationId="deleteWorkShiftsByIds",
     *      tags={"administrator.work_shift"},
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
     *      summary="This method is to delete work shift by id",
     *      description="This method is to delete work shift by id",
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

    public function deleteWorkShiftsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('work_shift_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = WorkShift::where([
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
            WorkShift::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully"], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
