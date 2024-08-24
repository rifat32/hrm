<?php

namespace App\Http\Controllers;

use App\Exports\HolidayExport;
use App\Http\Components\DepartmentComponent;
use App\Http\Requests\HolidayCreateRequest;
use App\Http\Requests\HolidaySelfCreateRequest;
use App\Http\Requests\HolidayUpdateRequest;
use App\Http\Requests\HolidayUpdateStatusRequest;
use App\Http\Utils\BasicNotificationUtil;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\HolidayUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use PDF;
use Maatwebsite\Excel\Facades\Excel;

class HolidayController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, BasicUtil, BasicUtil, ModuleUtil, BasicNotificationUtil;


    protected $departmentComponent;


    public function __construct(DepartmentComponent $departmentComponent)
    {

        $this->departmentComponent = $departmentComponent;
    }

      /**
     *
     * @OA\Post(
     *      path="/v1.0/holidays/self",
     *      operationId="createSelfHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store holiday",
     *      description="This method is to store holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *             @OA\Property(property="description", type="string", format="string", example="Updated holiday celebration"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="repeats_annually", type="boolean", format="boolean", example=false),
     *  *     @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
     *  *     @OA\Property(property="users", type="string", format="array", example={1,2,3})
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

     public function createSelfHoliday(HolidaySelfCreateRequest $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             $this->isModuleEnabled("employee_login");

             return DB::transaction(function () use ($request) {


                 $request_data = $request->validated();


                 $request_data["business_id"] = auth()->user()->business_id;
                 $request_data["is_active"] = true;
                 $request_data["created_by"] = $request->user()->id;
                 $request_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");

                 $holiday =  Holiday::create($request_data);



                 $holiday->users()->sync([auth()->user()->id]);


                 $this->send_notification($holiday, auth()->user(), "Holiday Request Taken", "create", "holiday");



                 return response()->json($holiday, 201);
             });
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/holidays",
     *      operationId="createHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store holiday",
     *      description="This method is to store holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *             @OA\Property(property="description", type="string", format="string", example="Updated holiday celebration"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="repeats_annually", type="boolean", format="boolean", example=false),
     *  *     @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
     *  *     @OA\Property(property="users", type="string", format="array", example={1,2,3})
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

    public function createHoliday(HolidayCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('holiday_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();




                $request_data["business_id"] = auth()->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["status"] = (auth()->user()->hasRole("business_owner") ? "approved" : "pending_approval");

                $holiday =  Holiday::create($request_data);


                $holiday->departments()->sync($request_data['departments']);
                $holiday->users()->sync($request_data['users']);


                foreach($holiday->departments() as $department) {
                    $this->send_notification($holiday, $department->manager, "Holiday Request Taken", "create", "holiday",1,$department->name );

                }

                foreach($holiday->users() as $user) {
                    $this->send_notification($holiday, $user, "Holiday Request Taken", "create", "holiday");
                }








                return response()->json($holiday, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

     /**
     *
     * @OA\Put(
     *      path="/v1.0/holidays/approve",
     *      operationId="approveHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update holiday ",
     *      description="This method is to update holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *             @OA\Property(property="status", type="string", format="string", example="Updated Christmas"),


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

     public function approveHoliday(HolidayUpdateStatusRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('holiday_update')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }
                 $business_id =  auth()->user()->business_id;
                 $request_data = $request->validated();



                 $holiday_query_params = [
                     "id" => $request_data["id"],
                     "business_id" => $business_id
                 ];


                 $holiday = Holiday::where($holiday_query_params)->first();



                 if ($holiday) {
                     $holiday->fill(collect($request_data)->only(['status'])->toArray());
                     $holiday->save();
                 }



             if (!$holiday) {
                 return response()->json([
                     "message" => "Something went wrong."
                 ], 500);
             }

             foreach($holiday->departments() as $department) {
                $this->send_notification($holiday, $department->manager, "Holiday Request Status Updated", $request_data["status"], "holiday",1,$department->name);
            }

            foreach($holiday->users() as $user) {
                $this->send_notification($holiday, $user, "Holiday Request Status Updated", $request_data["status"], "holiday");
            }







            return response()->json($holiday, 201);
             });
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/holidays",
     *      operationId="updateHoliday",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update holiday ",
     *      description="This method is to update holiday",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *             @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *             @OA\Property(property="description", type="string", format="string", example="Updated holiday celebration"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2023-12-25"),
     *             @OA\Property(property="repeats_annually", type="boolean", format="boolean", example=false),
     *  *  *     @OA\Property(property="departments", type="string", format="array", example={1,2,3}),
     *  *     @OA\Property(property="users", type="string", format="array", example={1,2,3})

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

    public function updateHoliday(HolidayUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('holiday_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();



                $holiday_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $holiday_prev = Holiday::where($holiday_query_params)
                    ->first();
                if (!$holiday_prev) {

                    return response()->json([
                        "message" => "no holiday found"
                    ], 404);
                }

                $holiday  =  tap(Holiday::where($holiday_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                        'start_date',
                        'end_date', '
                        repeats_annually',
                        // 'business_id',
                        // 'is_active',

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$holiday) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }
                $holiday->departments()->sync($request_data['departments']);
                $holiday->users()->sync($request_data['users']);

                foreach($holiday->departments() as $department) {
                    $this->send_notification($holiday, $department->manager, "Holiday Status Updated", $request_data["status"], "holiday",1,$department->name);
                }

                foreach($holiday->users() as $user) {
                    $this->send_notification($holiday, $user, "Holiday Request Updated", $request_data["status"], "holiday");
                }

                return response($holiday, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/holidays",
     *      operationId="getHolidays",
     *      tags={"administrator.holiday"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *    *   *              @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf,csv,json",
     *         required=true,
     *  example="json"
     *      ),
     *      *   *              @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),

     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
     *

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
     *     * *  @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     *     *     * *  @OA\Parameter(
     * name="repeat",
     * in="query",
     * description="repeat",
     * required=true,
     * example="repeat"
     * ),
     *   *     *     * *  @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),
     *
     *     *   *     *     * *  @OA\Parameter(
     * name="department_id",
     * in="query",
     * description="department_id",
     * required=true,
     * example="department_id"
     * ),
     *
     *     *     *   *     *     * *  @OA\Parameter(
     * name="show_my_data",
     * in="query",
     * description="show_my_data",
     * required=true,
     * example="show_my_data"
     * ),
     *
     *
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *      summary="This method is to get holidays  ",
     *      description="This method is to get holidays ",
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

    public function getHolidays(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");


            if(request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('holiday_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $business_id =  auth()->user()->business_id;

            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $all_user_of_manager = $this->get_all_user_of_manager($all_manager_department_ids);
            $all_parent_department_ids = $this->departmentComponent->all_parent_departments_of_user(auth()->user()->id);


            $holidays = Holiday::with([
                "creator" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "departments" => function ($query) {
                    $query->select('departments.id', 'departments.name'); // Specify the fields for the creator relationship
                },
                "users"
            ])
                ->where(
                    [
                        "holidays.business_id" => $business_id
                    ]
                )

                ->when(
                    (request()->has('show_my_data') && intval(request()->show_my_data) == 1),
                    function ($query) use ($all_parent_department_ids) {


                        $query->where(function ($query) use ($all_parent_department_ids) {
                            $query->whereHas("departments", function ($query) use ($all_parent_department_ids) {
                                $query->whereIn("departments.id", $all_parent_department_ids);
                            })
                                ->orWhereHas("users", function ($query) {
                                    $query->whereIn(
                                        "users.id",
                                        [auth()->user()->id]
                                    );
                                })
                                ->orWhere(function ($query) {
                                    $query->whereDoesntHave("users")
                                        ->whereDoesntHave("departments");
                                });
                        });
                    },
                    function ($query) use ($all_manager_department_ids, $all_user_of_manager) {

                        $query->where(function ($query) use ($all_manager_department_ids, $all_user_of_manager) {
                            $query->whereHas("departments", function ($query) use ($all_manager_department_ids) {
                                $query->whereIn("departments.id", $all_manager_department_ids);
                            })
                                ->orWhereHas("users", function ($query) use ($all_user_of_manager) {
                                    $query->whereIn(
                                        "users.id",
                                        $all_user_of_manager
                                    );
                                });

                                if (auth()->user()->hasRole('business_owner')) {
                                    $query ->orWhere(function ($query) {
                                        $query->whereDoesntHave("users")
                                            ->whereDoesntHave("departments");
                                    });
                                }



                        });

                    }

                )





                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("holidays.name", "like", "%" . $term . "%")
                            ->orWhere("holidays.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->name), function ($query) use ($request) {
                    return $query->where("holidays.name", "like", "%" . $request->name . "%");
                })

                ->when(isset($request->repeat), function ($query) use ($request) {
                    return $query->where('holidays.repeats_annually', intval($request->repeat));
                })
                ->when(!empty($request->description), function ($query) use ($request) {
                    return $query->where("holidays.description", "like", "%" . $request->description . "%");
                })

                ->when(!empty($request->department_id), function ($query) use ($request) {
                    $idsArray = explode(',', $request->department_id);
                    $query->whereHas('departments', function ($query) use ($idsArray) {
                        $query->whereIn("departments.id", $idsArray);
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('holidays.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('holidays.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("holidays.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("holidays.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });


            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    $pdf = PDF::loadView('pdf.holidays', ["holidays" => $holidays]);
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {

                    return Excel::download(new HolidayExport($holidays), ((!empty($request->file_name) ? $request->file_name : 'leave') . '.csv'));
                }
            } else {
                return response()->json($holidays, 200);
            }


            return response()->json($holidays, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/holidays/{id}",
     *      operationId="getHolidayById",
     *      tags={"administrator.holiday"},
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
     *      summary="This method is to get holiday by id",
     *      description="This method is to get holiday by id",
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


    public function getHolidayById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            if(request()->boolean("show_my_data")) {
                $this->isModuleEnabled("employee_login");
            }

            if (!$request->user()->hasPermissionTo('holiday_view') && !request()->boolean("show_my_data")) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $all_user_of_manager = $this->get_all_user_of_manager($all_manager_department_ids);
            $all_parent_department_ids = $this->departmentComponent->all_parent_departments_of_user(auth()->user()->id);
            $holiday =  Holiday::with([
                "creator" => function ($query) {
                    $query->select(
                        'users.id',
                        'users.first_Name',
                        'users.middle_Name',
                        'users.last_Name'
                    );
                },
                "departments" => function ($query) {
                    $query->select('departments.id', 'departments.name'); // Specify the fields for the creator relationship
                },
                "users"
            ])->where([
                "id" => $id,
                "business_id" => auth()->user()->business_id
            ])
            ->where(function ($query) use ($all_parent_department_ids, $all_manager_department_ids,$all_user_of_manager) {
                $query->whereHas("departments", function ($query) use ($all_parent_department_ids,$all_manager_department_ids) {
                    $query->whereIn("departments.id", array_merge($all_parent_department_ids,$all_manager_department_ids));
                })
                    ->orWhereHas("users", function ($query) use($all_user_of_manager) {
                        $query->whereIn(
                            "users.id",
                            array_merge([auth()->user()->id],$all_user_of_manager)
                        );
                    })
                    ->orWhere(function ($query) {
                        $query->whereDoesntHave("users")
                            ->whereDoesntHave("departments");
                    });
            })
                ->first();
            if (!$holiday) {

                return response()->json([
                    "message" => "no holiday found"
                ], 404);
            }

            return response()->json($holiday, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/holidays/{ids}",
     *      operationId="deleteHolidaysByIds",
     *      tags={"administrator.holiday"},
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
     *      summary="This method is to delete holiday by id",
     *      description="This method is to delete holiday by id",
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

    public function deleteHolidaysByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('holiday_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Holiday::where([
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
            $holidays = Holiday::whereIn("id", $existingIds)->get();
            foreach($holidays as $holiday) {
                foreach($holiday->departments() as $department) {
                    $this->send_notification($holiday, $department->manager, "Holiday Request Deleted", "delete", "holiday", 1,$department->name );

                }

                foreach($holiday->users() as $user) {
                    $this->send_notification($holiday, $user, "Holiday Request Deleted", "delete", "holiday");
                }
            }


            Holiday::destroy($existingIds);



            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

}
