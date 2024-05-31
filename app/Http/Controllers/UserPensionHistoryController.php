<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserPensionHistoryCreateRequest;
use App\Http\Requests\UserPensionHistoryUpdateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\EmployeePensionHistory;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class UserPensionHistoryController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, BasicUtil;



    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-pension-histories",
     *      operationId="createUserPensionHistory",
     *      tags={"user_pension_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user pension history",
     *      description="This method is to store user pension history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *  * @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
     *      @OA\Property(property="pension_eligible", type="boolean", format="boolean", example="1"),
     *      @OA\Property(property="pension_letters", type="string", format="array", example={{"file_name":"sss"}}),
     *      @OA\Property(property="pension_scheme_status", type="string", format="string", example="pension_scheme_status"),
     *      @OA\Property(property="pension_enrollment_issue_date", type="string", format="string", example="pension_enrollment_issue_date"),
     *      @OA\Property(property="pension_scheme_opt_out_date", type="string", format="string", example="pension_scheme_opt_out_date"),
     *      @OA\Property(property="pension_re_enrollment_due_date", type="string", format="date", example="pension_re_enrollment_due_date"),
     *
     *      @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
     *      @OA\Property(property="to_date", type="string", format="date", example="Your To Date")


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

    public function createUserPensionHistory(UserPensionHistoryCreateRequest $request)
    {



       DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

                if (!$request->user()->hasPermissionTo('employee_pension_history_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["pension_letters"] =   $this->storeUploadedFiles($request_data["pension_letters"],"file_name","pension_letters");



                $request_data["created_by"] = $request->user()->id;
                $request_data["is_manual"] = 1;
                $request_data["business_id"] = auth()->user()->business_id;



                $user_pension_history =  EmployeePensionHistory::create($request_data);



                // $this->moveUploadedFiles(collect($request_data["pension_letters"])->pluck("file_name"),"pension_letters");


DB::commit();
                return response($user_pension_history, 201);

        } catch (Exception $e) {
       DB::rollBack();



       try {


        $this->moveUploadedFilesBack($request_data["pension_letters"],"file_name","pension_letters");



    } catch (Exception $innerException) {

        error_log("Failed to move pension letters  files back: " . $innerException->getMessage());

    }


            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-pension-histories",
     *      operationId="updateUserPensionHistory",
     *      tags={"user_pension_histories"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user pension history ",
     *      description="This method is to update user pension history",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *  * @OA\Property(property="user_id", type="string", format="string", example="Your Employee ID"),
     *      @OA\Property(property="pension_eligible", type="boolean", format="boolean", example="1"),
     *      @OA\Property(property="pension_letters", type="string", format="array", example={{"file_name":"sss"}}),
     *      @OA\Property(property="pension_scheme_status", type="string", format="string", example="pension_scheme_status"),
     *      @OA\Property(property="pension_enrollment_issue_date", type="string", format="string", example="pension_enrollment_issue_date"),
     *      @OA\Property(property="pension_scheme_opt_out_date", type="string", format="string", example="pension_scheme_opt_out_date"),
     *      @OA\Property(property="pension_re_enrollment_due_date", type="string", format="date", example="pension_re_enrollment_due_date"),
     *
     *      @OA\Property(property="from_date", type="string", format="date", example="Your From Date"),
     *      @OA\Property(property="to_date", type="string", format="date", example="Your To Date")
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

    public function updateUserPensionHistory(UserPensionHistoryUpdateRequest $request)
    {
            DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

                if (!$request->user()->hasPermissionTo('employee_pension_history_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["pension_letters"] =   $this->storeUploadedFiles($request_data["pension_letters"],"file_name","pension_letters");




                $request_data["created_by"] = auth()->user()->id;
                $request_data["is_manual"] = 1;
                $request_data["business_id"] = auth()->user()->business_id;
                $all_manager_department_ids = $this->get_all_departments_of_manager();

                $current_user_id =  $request_data["user_id"];
                $issue_date_column = 'pension_enrollment_issue_date';
                $expiry_date_column = 'pension_re_enrollment_due_date';

                $current_pension = $this->getCurrentPensionHistory(EmployeePensionHistory::class, 'current_pension_id', $current_user_id, $issue_date_column, $expiry_date_column);


                $user_pension_history_query_params = [
                    "id" => $request_data["id"],
                    // "is_manual" => 1
                ];

                if ($current_pension && $current_pension->id == $request_data["id"]) {
                    $request_data["is_manual"] = 0;
                    $user_pension_history =   EmployeePensionHistory::create($request_data);

                        User::where([
                            "id" => $user_pension_history->user_id
                        ])
                        ->update([
                            "pension_eligible" => $user_pension_history->pension_eligible
                        ]);




                } else {
                    $user_pension_history  =  tap(EmployeePensionHistory::where($user_pension_history_query_params))->update(
                        collect($request_data)->only([
                            'pension_eligible',
                            'pension_enrollment_issue_date',
                            'pension_letters',
                            'pension_scheme_status',
                            'pension_scheme_opt_out_date',
                            'pension_re_enrollment_due_date',
                            "is_manual",
                            'user_id',

                            "from_date",
                            "to_date",

                        ])->toArray()
                    )
                        ->first();
                }


                if (!$user_pension_history) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }








                // $this->moveUploadedFiles(collect($request_data["pension_letters"])->pluck("file_name"),"pension_letters");

                DB::commit();
                return response($user_pension_history, 201);

        } catch (Exception $e) {
            DB::rollBack();
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-pension-histories",
     *      operationId="getUserPensionHistories",
     *      tags={"user_pension_histories"},
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

     *      summary="This method is to get user pension histories  ",
     *      description="This method is to get user pension histories ",
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

    public function getUserPensionHistories(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_pension_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();



            $current_user_id = request()->user_id;
            $issue_date = 'pension_enrollment_issue_date';
            $expiry_date = 'pension_re_enrollment_due_date';
            $current_pension = $this->getCurrentPensionHistory(EmployeePensionHistory::class, 'current_pension_id', $current_user_id, $issue_date, $expiry_date);








            $user_pension_histories = EmployeePensionHistory::with([
                "creator" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },

            ])
                // ->where(["is_manual" => 1])
                ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employee_pension_histories.name", "like", "%" . $term . "%");
                        //     ->orWhere("employee_pension_histories.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_pension_histories.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('employee_pension_histories.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_pension_histories.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_pension_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })

                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("employee_pension_histories.pension_re_enrollment_due_date", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("employee_pension_histories.pension_re_enrollment_due_date", "DESC");
                })

                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });






            return response()->json($user_pension_histories, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-pension-histories/{id}",
     *      operationId="getUserPensionHistoryById",
     *      tags={"user_pension_histories"},
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
     *      summary="This method is to get user pension history by id",
     *      description="This method is to get user pension history by id",
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


    public function getUserPensionHistoryById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_pension_history_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $all_manager_department_ids = $this->get_all_departments_of_manager();







            $user_pension_history =  EmployeePensionHistory::where([
                "id" => $id,
                // "is_manual" => 1
            ])

                ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->first();
            if (!$user_pension_history) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }



            return response()->json($user_pension_history, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-pension-histories/{ids}",
     *      operationId="deleteUserPensionHistoriesByIds",
     *      tags={"user_pension_histories"},
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
     *      summary="This method is to delete user pension history by id",
     *      description="This method is to delete user pension history by id",
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

    public function deleteUserPensionHistoriesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_pension_history_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }




            $business_id =  auth()->user()->business_id;

            $all_manager_department_ids = $this->get_all_departments_of_manager();

            $idsArray = explode(',', $ids);
            $existingIds = EmployeePensionHistory::whereIn('id', $idsArray)
                // ->where(["is_manual" => 1])
                ->whereHas("employee.department_user.department", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
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
            EmployeePensionHistory::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
