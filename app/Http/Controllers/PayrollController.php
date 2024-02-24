<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayrollCreateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\PayrunUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\Payroll;
use App\Models\Payrun;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, PayrunUtil;



    /**
     *
     * @OA\Post(
     *      path="/v1.0/payrolls",
     *      operationId="createPayroll",
     *      tags={"administrator.payrolls"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store payroll",
     *      description="This method is to store payrolls",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *
     *  @OA\Property(property="payrun_id", type="number", format="number", example="1"),
     *  @OA\Property(property="users", type="string", format="array", example={1,2,3}),
     *    @OA\Property(property="start_date", type="number", format="number", example="10-02-2024"),
     *   @OA\Property(property="end_date", type="number", format="number", example="25-02-2024"),
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

    public function createPayroll(PayrollCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('payrun_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $payrun = Payrun::where([
                    "id" => $request_data["payrun_id"],
                    "business_id" => auth()->user()->business_id
                ])

                    ->first();

                if (!$payrun) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => ["payrun_id" => ["The payrun_id field is invalid."]]
                    ];
                    throw new Exception(json_encode($error), 500);
                }

                $employees = User::whereIn("id", $request_data["users"])
                    ->whereDoesntHave("payrolls", function ($q) use ($payrun) {
                        $q->where("payrolls.start_date", $payrun->start_date)
                            ->where("payrolls.end_date", $payrun->end_date);
                    })
                    ->where(function ($query) use ($payrun) {
                        $query->whereHas("departments.payrun_department", function ($query) use ($payrun) {
                            $query->where("payrun_departments.payrun_id", $payrun->id);
                        })
                            ->orWhereHas("payrun_user", function ($query) use ($payrun) {
                                $query->where("payrun_users.payrun_id", $payrun->id);
                            });
                    })






                    ->get();






                $processed_employees =  $this->process_payrun($payrun, $employees, $request_data["start_date"], $request_data["end_date"], true, true);

                return response()->json($processed_employees, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }












    /**
     *
     * @OA\Get(
     *      path="/v1.0/payrolls",
     *      operationId="getPayrolls",
     *      tags={"administrator.payrolls"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      * *  @OA\Parameter(
     * name="payrun_id",
     * in="query",
     * description="payrun_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="user_ids",
     * in="query",
     * description="user_ids",
     * required=true,
     * example="1,2,3"
     * ),
     *
     *
     *
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
     *  * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get payrolls  ",
     *      description="This method is to get payrolls ",
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


    public function getPayrolls(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('payrun_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            };
            if (!$request->payrun_id) {
                $error = [
                    "message" => "The given data was invalid.",
                    "errors" => ["payrun_id" => ["The payrun_id field is required."]]
                ];
                throw new Exception(json_encode($error), 422);
            }

            // if(!isset($request->start_date)) {
            //     $error = [ "message" => "The given data was invalid.",
            //     "errors" => ["start_date"=>["The start date field is required."]]
            //     ];
            //         throw new Exception(json_encode($error),422);
            //  }

            //  if(!isset($request->end_date)) {
            //     $error = [ "message" => "The given data was invalid.",
            //     "errors" => ["start_date"=>["The end date field is required."]]
            //     ];
            //         throw new Exception(json_encode($error),422);
            //  }






            $payrun = Payrun::where([
                "id" => $request->payrun_id,
                "business_id" => auth()->user()->business_id
            ])
                ->where(function ($query) use ($all_manager_department_ids) {
                    $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    })
                        ->orWhereHas("users.departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        });
                })
                ->first();

            if (!$payrun) {
                $error = [
                    "message" => "The given data was invalid.",
                    "errors" => ["payrun_id" => ["The payrun_id field is invalid."]]
                ];
                throw new Exception(json_encode($error), 422);
            }





            $employees = User::where([
                "business_id" => $payrun->business_id,
                "is_active" => 1
            ])
                ->whereDoesntHave("payrolls", function ($q) use ($payrun) {
                    $q->where("payrolls.start_date", $payrun->start_date)
                        ->where("payrolls.end_date", $payrun->end_date);
                })

                ->whereNotIn("id", [auth()->user()->id])
                ->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                    $query->whereIn("departments.id", $all_manager_department_ids);
                })
                ->when(!empty($request->user_ids), function ($query) use ($request, $all_manager_department_ids) {
                    $user_ids = explode(',', $request->user_ids);
                    $query->whereIn("users.id", $user_ids);
                })
                ->where(function ($query) use ($payrun) {
                    $query->whereHas("departments.payrun_department", function ($query) use ($payrun) {
                        $query->where("payrun_departments.payrun_id", $payrun->id);
                    })
                        ->orWhereHas("payrun_user", function ($query) use ($payrun) {
                            $query->where("payrun_users.payrun_id", $payrun->id);
                        });
                })


                ->get();


            $processed_employees =  $this->process_payrun($payrun, $employees, $request->start_date, $request->end_date, true, false);


            return response()->json($processed_employees, 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/payroll-list",
     *      operationId="getPayrollList",
     *      tags={"administrator.payrolls"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      * *  @OA\Parameter(
     * name="payrun_id",
     * in="query",
     * description="payrun_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="user_ids",
     * in="query",
     * description="user_ids",
     * required=true,
     * example="1,2,3"
     * ),
     *
     *
     *
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
     *  * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get payrolls  ",
     *      description="This method is to get payrolls ",
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


    public function getPayrollList(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if (!$request->user()->hasPermissionTo('payrun_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            };
            //  if(!$request->payrun_id) {
            //     $error = [ "message" => "The given data was invalid.",
            //     "errors" => ["payrun_id"=>["The payrun_id field is required."]]
            //     ];
            //         throw new Exception(json_encode($error),422);
            //  }







            if (!empty($request->payrun_id)) {
                $payrun = Payrun::where([

                    "business_id" => auth()->user()->business_id
                ])


                    ->where(function ($query) use ($all_manager_department_ids) {
                        $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                            $query->whereIn("departments.id", $all_manager_department_ids);
                        })
                            ->orWhereHas("users.departments", function ($query) use ($all_manager_department_ids) {
                                $query->whereIn("departments.id", $all_manager_department_ids);
                            });
                    })
                    ->first();

                if (!$payrun) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => ["payrun_id" => ["The payrun_id field is invalid."]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }
            }



            $payrolls = Payroll::with("user", "payrun")
                ->when(!empty($request->payrun_id), function ($query) use ($request) {
                    $query->where([
                        "payrun_id" => $request->payrun_id
                    ]);
                })

                ->when(!empty($request->user_ids), function ($query) use ($request) {
                    $user_ids = explode(',', $request->user_ids);
                    $query->orWhereHas("user", function ($query) use ($user_ids) {
                        $query->whereIn("users..id", $user_ids);
                    });
                })
                ->where(function ($query) use ($all_manager_department_ids) {
                    $query->whereHas("user.departments", function ($query) use ($all_manager_department_ids) {
                        $query->whereIn("departments.id", $all_manager_department_ids);
                    });
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("payrolls.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("payrolls.id", "DESC");
                })

                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });


            return response()->json($payrolls, 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
}
