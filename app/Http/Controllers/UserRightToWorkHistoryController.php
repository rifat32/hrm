<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRightToWorkHistoryCreateRequest;
use App\Http\Requests\UserRightToWorkHistoryUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\EmployeeRightToWork;
use App\Models\EmployeeRightToWorkHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserRightToWorkHistoryController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;






    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-right-to-work-histories",
     *      operationId="createUserRightToWorkHistory",
     *      tags={"user_right_to_work_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user right to work history",
     *      description="This method is to store user right to work history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
* @OA\Property(property="right_to_work_code", type="string", format="string", example="Your BRP Number"),
* @OA\Property(property="right_to_work_check_date", type="string", format="date", example="Your right_to_work Issue Date"),
* @OA\Property(property="right_to_work_expiry_date", type="string", format="date", example="Your right_to_work Expiry Date"),
* @OA\Property(property="right_to_work_docs", type="string", format="string", example="Your right_to_work Documents"),
* @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
* @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
* @OA\Property(property="to_date", type="string", format="date", example="Your To Date"),
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

    public function createUserRightToWorkHistory(UserRightToWorkHistoryCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_right_to_work_history_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }


                $request_data = $request->validated();
                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_manual"] = 1;


                $current_right_to_work =  EmployeeRightToWorkHistory::where(
                    [
                        "user_id" => $request["user_id"],
                    ]
                )->latest()->first();

                if ($current_right_to_work) {
                    // Parse the new expiry date using Carbon
                    $new_expiry_date = Carbon::parse($request_data["right_to_work_expiry_date"]);
                    $current_expiry_date = Carbon::parse($current_right_to_work->right_to_work_expiry_date);

                    if ($new_expiry_date->gt($current_expiry_date)) {
                        // Update the passport expiry date
                        $request_data["is_manual"] = 0;
                        $user_right_to_work  =  $current_right_to_work->update(
                            collect($request_data)->only([
                                'right_to_work_code',
                                'right_to_work_check_date',
                                'right_to_work_expiry_date',
                                'right_to_work_docs',
                                 "created_by"

                            ])->toArray()
                        );

                        // Now $current_passport_detail holds the updated passport detail with the later expiry date
                    }
                } else {
                    $new_expiry_date = Carbon::parse($request_data["right_to_work_expiry_date"]);
                    $today = now();

                    if ($new_expiry_date->gt($today)) {
                        // Handle the case where the new expiry date is later than today's date
                        $request_data["is_manual"] = 0;
                        $user_right_to_work = EmployeeRightToWork::create($request_data);
                    }
                }


                $user_right_to_work_history =  EmployeeRightToWorkHistory::create($request_data);



                return response($user_right_to_work_history, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-right-to-work-histories",
     *      operationId="updateRightToWorkHistory",
     *      tags={"user_right_to_work_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user right to work history ",
     *      description="This method is to update user right to work history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
* @OA\Property(property="right_to_work_code", type="string", format="string", example="Your BRP Number"),
* @OA\Property(property="right_to_work_check_date", type="string", format="date", example="Your right_to_work Issue Date"),
* @OA\Property(property="right_to_work_expiry_date", type="string", format="date", example="Your right_to_work Expiry Date"),
* @OA\Property(property="right_to_work_docs", type="string", format="string", example="Your right_to_work Documents"),
* @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
* @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
* @OA\Property(property="to_date", type="string", format="date", example="Your To Date"),

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

    public function updateRightToWorkHistory(UserRightToWorkHistoryUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_right_to_work_history_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_manual"] = 1;

                $current_right_to_work =  EmployeeRightToWork::where(
                    [
                        "user_id" => $request["user_id"],
                    ]
                )->latest()->first();

                if ($current_right_to_work) {
                    // Parse the new expiry date using Carbon
                    $new_expiry_date = Carbon::parse($request_data["right_to_work_expiry_date"]);
                    $current_expiry_date = Carbon::parse($current_right_to_work->right_to_work_expiry_date);

                    if ($new_expiry_date->gt($current_expiry_date)) {
                        // Update the passport expiry date
                        $request_data["is_manual"] = 0;
                        $user_right_to_work  =  $current_expiry_date->update(
                            collect($request_data)->only([
                                'right_to_work_code',
                                'right_to_work_check_date',
                                'right_to_work_expiry_date',
                                'right_to_work_docs',
                                 "created_by"

                            ])->toArray()
                        );

                        // Now $current_passport_detail holds the updated passport detail with the later expiry date
                    }
                } else {
                    $new_expiry_date = Carbon::parse($request_data["expiry_date"]);
                    $today = Carbon::now();

                    if ($new_expiry_date->gt($today)) {
                        // Handle the case where the new expiry date is later than today's date
                        $request_data["is_manual"] = 0;
                        $user_right_to_work = EmployeeRightToWork::create($request_data);
                    }
                }


                $user_right_to_work_history =  EmployeeRightToWorkHistory::create($request_data);




                $user_right_to_work_history_query_params = [
                    "id" => $request_data["id"],
                    "is_manual" => 1
                ];

                $user_right_to_work_history  =  tap(EmployeeRightToWorkHistory::where($user_right_to_work_history_query_params))->update(
                    collect($request_data)->only([
                        'right_to_work_code',
                        'right_to_work_check_date',
                        'right_to_work_expiry_date',
                        'right_to_work_docs',

        "is_manual",
        'user_id',
        "from_date",
        "to_date",



                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$user_right_to_work_history) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($user_right_to_work_history, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-right-to-work-histories",
     *      operationId="getUserRightToWorkHistories",
     *      tags={"user_right_to_work_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="user_id",
     *         required=true,
     *  example="1"
     *      ),
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

     *      summary="This method is to get user right to work histories  ",
     *      description="This method is to get user right to work histories ",
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

    public function getUserRightToWorkHistories(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_right_to_work_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $user_right_to_work_histories = EmployeeRightToWorkHistory::with([
                "creator" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
            // ->where(["is_manual" => 1])
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
            ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employee_right_to_work_histories.name", "like", "%" . $term . "%");
                        //     ->orWhere("employee_right_to_work_histories.description", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_right_to_work_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_right_to_work_histories.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_right_to_work_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_right_to_work_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("employee_right_to_work_histories.right_to_work_expiry_date", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("employee_right_to_work_histories.right_to_work_expiry_date", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($user_right_to_work_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-right-to-work-histories/{id}",
     *      operationId="getUserRightToWorkHistoryById",
     *      tags={"user_right_to_work_histories"},
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
     *      summary="This method is to get user right to work history by id",
     *      description="This method is to get user right to work history by id",
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


    public function getUserRightToWorkHistoryById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_right_to work_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $user_right_to_work_history =  EmployeeRightToWorkHistory::where([
                "id" => $id,
                "is_manual" => 1
            ])

            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
                ->first();
            if (!$user_right_to_work_history) {
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_right_to_work_history, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-right-to-work-histories/{ids}",
     *      operationId="deleteUserRightToWorkHistoriesByIds",
     *      tags={"user_right_to_work_histories"},
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
     *      summary="This method is to delete user right to work history by id",
     *      description="This method is to delete user right to work history by id",
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

    public function deleteUserRightToWorkHistoriesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_right_to_work_history_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $idsArray = explode(',', $ids);
            $existingIds = EmployeeRightToWorkHistory::whereIn('id', $idsArray)
            // ->where(["is_manual" => 1])
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }
            EmployeeRightToWorkHistory::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
