<?php

namespace App\Http\Controllers;

use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\UserPayslipCreateRequest;
use App\Http\Requests\UserPayslipUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\Payslip;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPayslipController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;



 /**
     *
     * @OA\Post(
     *      path="/v1.0/user-payslips/single-file-upload",
     *      operationId="createPayslipFileSingle",
     *      tags={"user_payslips"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user payslip file ",
     *      description="This method is to store user payslip file",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"file"},
     *         @OA\Property(
     *             description="file to upload",
     *             property="file",
     *             type="file",
     *             collectionFormat="multi",
     *         )
     *     )
     * )



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

     public function createPayslipFileSingle(SingleFileUploadRequest $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             // if(!$request->user()->hasPermissionTo('business_create')){
             //      return response()->json([
             //         "message" => "You can not perform this action"
             //      ],401);
             // }

             $request_data = $request->validated();

             $location =  config("setup-config.payslip_files_location");

             $new_file_name = time() . '_' . str_replace(' ', '_', $request_data["file"]->getClientOriginalName());

             $request_data["file"]->move(public_path($location), $new_file_name);


             return response()->json(["file" => $new_file_name, "location" => $location, "full_location" => ("/" . $location . "/" . $new_file_name)], 200);
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-payslips",
     *      operationId="createUserPayslip",
     *      tags={"user_payslips"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user payslips",
     *      description="This method is to store user payslips",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="payroll_id", type="integer", format="int", example=null),
 *     @OA\Property(property="month", type="integer", format="int", example=1),
 *     @OA\Property(property="year", type="integer", format="int", example=2024),
 *
 *   @OA\Property(property="payment_notes", type="number", format="double", example=1000.00),
 *     @OA\Property(property="payment_amount", type="number", format="double", example=1000.00),
 *     @OA\Property(property="payment_date", type="string", format="date", example="2024-02-02"),
 *     @OA\Property(property="payslip_file", type="string", format="string", example="path/to/payslip.pdf"),
 *  *   @OA\Property(property="payment_record_file", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"}),
 *
 **     @OA\Property(
 *         property="gross_pay",
 *         type="number",
 *         format="double",
 *         example=1000.50,
 *         description="Gross pay amount"
 *     ),
 *     @OA\Property(
 *         property="tax",
 *         type="number",
 *         format="double",
 *         example=200.75,
 *         description="Tax amount"
 *     ),
 *     @OA\Property(
 *         property="employee_ni_deduction",
 *         type="number",
 *         format="double",
 *         example=50.25,
 *         description="Employee NI deduction amount"
 *     ),
 *     @OA\Property(
 *         property="employer_ni",
 *         type="number",
 *         format="double",
 *         example=75.30,
 *         description="Employer NI amount"
 *     )
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

    public function createPayslip(UserPayslipCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_payslip_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                $request_data["created_by"] = $request->user()->id;
                $user_payslip =  Payslip::create($request_data);

                return response($user_payslip, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-payslips",
     *      operationId="updateUserPayslip",
     *      tags={"user_payslips"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user payslip  ",
     *      description="This method is to update user payslip ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
*     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="payroll_id", type="integer", format="int", example=null),
 *     @OA\Property(property="month", type="integer", format="int", example=1),
 *     @OA\Property(property="year", type="integer", format="int", example=2024),
 *     @OA\Property(property="payment_amount", type="number", format="double", example=1000.00),
 * *     @OA\Property(property="payment_notes", type="number", format="double", example=1000.00),
 *
 *     @OA\Property(property="payment_date", type="string", format="date", example="2024-02-02"),
 *     @OA\Property(property="payslip_file", type="string", format="string", example="path/to/payslip.pdf"),
 *  *   @OA\Property(property="payment_record_file", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"}),
 * *     @OA\Property(
 *         property="gross_pay",
 *         type="number",
 *         format="double",
 *         example=1000.50,
 *         description="Gross pay amount"
 *     ),
 *     @OA\Property(
 *         property="tax",
 *         type="number",
 *         format="double",
 *         example=200.75,
 *         description="Tax amount"
 *     ),
 *     @OA\Property(
 *         property="employee_ni_deduction",
 *         type="number",
 *         format="double",
 *         example=50.25,
 *         description="Employee NI deduction amount"
 *     ),
 *     @OA\Property(
 *         property="employer_ni",
 *         type="number",
 *         format="double",
 *         example=75.30,
 *         description="Employer NI amount"
 *     ),
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

    public function updateUserPayslip(UserPayslipUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_payslip_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();




                $user_payslip_query_params = [
                    "id" => $request_data["id"],
                ];
                // $user_education_history_prev = UserEducationHistory::where($user_education_history_query_params)
                //     ->first();
                // if (!$user_education_history_prev) {
                //     return response()->json([
                //         "message" => "no user education history found"
                //     ], 404);
                // }

                $user_payslip  =  tap(Payslip::where($user_payslip_query_params))->update(
                    collect($request_data)->only([
                        'user_id',
                         'month',
                          'year',
                          "payment_notes",
                           'payment_amount',
                            'payment_date',
                             'payslip_file',
                              'payment_record_file',
                               "payroll_id",


    'gross_pay',
    'tax',
    'employee_ni_deduction',
    'employer_ni'
                                // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$user_payslip) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($user_payslip, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-payslips",
     *      operationId="getUserPayslips",
     *      tags={"user_payslips"},
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

     *      summary="This method is to get user payslip ",
     *      description="This method is to get user  payslip ",
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

    public function getUserPayslips(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_payslip_view')) {
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
            $user_payslips = Payslip::with([
                "creator" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })

            ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("payslips.payment_notes", "like", "%" . $term . "%");
                        //     ->orWhere("user_education_histories.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('payslips.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('payslips.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('payslips.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('payslips.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("payslips.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("payslips.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($user_payslips, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-payslips/{id}",
     *      operationId="getUserPayslipById",
     *      tags={"user_payslips"},
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
     *      summary="This method is to get user payslip by id",
     *      description="This method is to get user payslip by id",
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


    public function getUserPayslipById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_payslip_view')) {
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
            $user_payslip =  Payslip::where([
                "id" => $id,
            ])
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
              $query->whereIn("departments.id",$all_manager_department_ids);
           })
                ->first();
            if (!$user_payslip) {
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

            return response()->json($user_payslip, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-payslips/{ids}",
     *      operationId="deleteUserPayslipsByIds",
     *      tags={"user_payslips"},
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
     *      summary="This method is to delete user payslip by id",
     *      description="This method is to delete user payslip by id",
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

    public function deleteUserPayslipsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_payslip_delete')) {
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
            $existingIds = Payslip::whereIn('id', $idsArray)
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
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
            Payslip::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


}
