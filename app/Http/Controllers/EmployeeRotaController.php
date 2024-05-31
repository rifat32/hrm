<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRotaCreateRequest;
use App\Http\Requests\EmployeeRotaUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\EmployeeRota;
use App\Models\User;
use App\Models\UserEmployeeRota;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PDF;
use Maatwebsite\Excel\Facades\Excel;


class EmployeeRotaController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, BasicUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/employee-rotas",
     *      operationId="createEmployeeRota",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store employee rota",
     *      description="This method is to store employee rota",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *     @OA\Property(property="type", type="string", format="string", example="regular"),
     *  *     @OA\Property(property="description", type="string", format="string", example="description"),
     *      *  *     @OA\Property(property="is_personal", type="boolean", format="boolean", example="0"),
     *

     *
     *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3}),

     *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
     * *     @OA\Property(property="details", type="string", format="array", example={
     *         {
     *             "day": "0",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "1",
     *             "start_at": "",
     *             "end_at": "",
      *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "2",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "3",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "4",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "5",
     *             "start_at": "",
     *             "end_at": "",
      *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "6",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         }
     *     }),

     *     @OA\Property(property="start_date", type="string", format="date", example="2023-11-16"),
     *     @OA\Property(property="end_date", type="string", format="date", example="2023-11-16"),
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

    public function createEmployeeRota(EmployeeRotaCreateRequest $request)
    {

        try {

            $this->storeActivity($request, "DUMMY activity","DUMMY description");


            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_rota_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();


                // @@@remove_field
                $request_data["type"] = "flexible";
                $request_data["is_personal"] = 0;
               // @@@remove_field





                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_default"] = false;





                if(!empty($request_data['departments'])){
                    foreach($request_data['departments'] as $department_id) {
                        $employee_rota =  EmployeeRota::create($request_data);
                        $employee_rota->details()->createMany($request_data['details']);
                        $employee_rota->departments()->sync([$department_id]);
                    }
                }


                if(!empty($request_data['users'])){
                    foreach($request_data['users'] as $user_id) {
                        $employee_rota =  EmployeeRota::create($request_data);
                        $employee_rota->details()->createMany($request_data['details']);
                        $employee_rota->users()->sync([$user_id]);
                    }
                }


















                return response(["ok" => true], 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/employee-rotas",
     *      operationId="updateEmployeeRota",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update employee rota ",
     *      description="This method is to update employee_rota",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *     @OA\Property(property="type", type="string", format="string", example="regular"),
     *     @OA\Property(property="description", type="string", format="string", example="description"),
     *    *      *  *     @OA\Property(property="is_personal", type="boolean", format="boolean", example="0"),
     *
     *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3,4}),

     *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
     * *     @OA\Property(property="details", type="string", format="array", example={
     *         {
     *             "day": "0",
     *             "start_at": "",
     *             "end_at": "",
       *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "1",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "2",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "3",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "4",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "5",
     *             "start_at": "",
     *             "end_at": "",
       *             "break_type": "paid",
     *             "break_hours" : 0.25
     *         },
     *         {
     *             "day": "6",
     *             "start_at": "",
     *             "end_at": "",
     *             "break_type": "paid",
     *             "break_hours" : 0.25
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

    public function updateEmployeeRota(EmployeeRotaUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");

            return DB::transaction(function () use ($request) {

                if (!$request->user()->hasPermissionTo('employee_rota_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }


                $request_data = $request->validated();
                if(empty($request_data['departments'])) {
                    $request_data['departments'] = [Department::where("business_id",auth()->user()->business_id)->whereNull("parent_id")->first()->id];
                }







                $employee_rota_query_params = [
                    "id" => $request_data["id"],
                ];



                $employee_rota  =  tap(EmployeeRota::where($employee_rota_query_params))->update(
                    collect($request_data)->only([
        'name',
        'type',
        "description",
        'attendances_count',
        'is_personal',



        'start_date',
        'end_date',


                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$employee_rota) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }


                $employee_rota->departments()->sync($request_data['departments']);


                $employee_rota->details()->delete();
                $employee_rota->details()->createMany($request_data['details']);




                return response($employee_rota, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


  /**
     *
     * @OA\Put(
     *      path="/v1.0/employee-rotas/toggle-active",
     *      operationId="toggleActiveEmployeeRota",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle employee rota activity",
     *      description="This method is to toggle employee rota activity",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
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


     public function toggleActiveEmployeeRota(GetIdRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('user_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();


             $all_manager_department_ids = $this->get_all_departments_of_manager();

            $employee_rota = EmployeeRota::where([
                "id" => $request_data["id"],
                "business_id" => auth()->user()->business_id
            ])
            ->whereHas("departments",function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
            })
                ->first();
            if (!$employee_rota) {
                return response()->json([
                    "message" => "no department found"
                ], 404);
            }
            $is_active = !$employee_rota->is_active;




             $employee_rota->update([
                 'is_active' => $is_active
             ]);


             return response()->json(['message' => 'department status updated successfully'], 200);
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/employee-rotas",
     *      operationId="getEmployeeRotas",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *   *   *              @OA\Parameter(
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
     *
     *
     * @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     * @OA\Parameter(
     * name="description",
     * in="query",
     * description="description",
     * required=true,
     * example="description"
     * ),
     *    * @OA\Parameter(
     * name="type",
     * in="query",
     * description="type",
     * required=true,
     * example="type"
     * ),
     *
     *
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *      * *  @OA\Parameter(
     * name="is_personal",
     * in="query",
     * description="is_personal",
     * required=true,
     * example="1"
     * ),
     *
     *
     * @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *

     *      summary="This method is to get employee rotas  ",
     *      description="This method is to get employee rotas ",
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

    public function getEmployeeRotas(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_rota_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $user_department = auth()->user()->departments[0];

            $employee_rotas = EmployeeRota::with("details","departments","users")

            ->when(!empty(auth()->user()->business_id), function ($query) use ( $all_manager_department_ids, $user_department) {
                 $query
               ->where(function($query) use($all_manager_department_ids, $user_department) {
                $query
                ->where([
                    "employee_rotas.business_id" => auth()->user()->business_id
                ])
                ->where(function($query) use ($all_manager_department_ids, $user_department) {
                    $query->whereHas("departments", function ($query) use ($all_manager_department_ids, $user_department) {
                        $query->whereIn("departments.id", array_merge($all_manager_department_ids,[$user_department]));
                    })
                    ->orWhereHas("users", function($query) {
                        $query->whereNotIn("users.id", [auth()->user()->id]);
                    });
                })
                ;

            })

                ->orWhere(function($query)  {
                    $query->where([
                        "is_active" => 1,
                        "business_id" => NULL,
                        "is_default" => 1
                    ]);

                });
            })

            ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                return $query->where([
                    "employee_rotas.is_default" => 1,
                    "employee_rotas.business_id" => NULL
                ]);
            })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("employee_rotas.name", "like", "%" . $term . "%")
                            ->orWhere("employee_rotas.description", "like", "%" . $term . "%");
                    });
                })





                ->when(isset($request->name), function ($query) use ($request) {
                    $term = $request->name;
                    return $query->where("employee_rotas.name", "like", "%" . $term . "%");
                })
                ->when(isset($request->description), function ($query) use ($request) {
                    $term = $request->description;
                    return $query->where("employee_rotas.description", "like", "%" . $term . "%");
                })

                ->when(isset($request->type), function ($query) use ($request) {
                    return $query->where('employee_rotas.type', ($request->type));
                })






                ->when(isset($request->is_personal), function ($query) use ($request) {
                    return $query->where('employee_rotas.is_personal', intval($request->is_personal));
                })
                ->when(!isset($request->is_personal), function ($query) use ($request) {
                    return $query->where('employee_rotas.is_personal', 0);
                })


                ->when(isset($request->is_default), function ($query) use ($request) {
                    return $query->where('employee_rotas.is_default', intval($request->is_personal));
                })


                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('employee_rotas.created_at', ">=", $request->start_date);
                })

                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('employee_rotas.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("employee_rotas.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("employee_rotas.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });


                if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                    // if (strtoupper($request->response_type) == 'PDF') {
                    //     $pdf = PDF::loadView('pdf.employee_rotas', ["employee_rotas" => $employee_rotas]);
                    //     return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                    // } elseif (strtoupper($request->response_type) === 'CSV') {

                    //     return Excel::download(new EmployeeRotasExport($employee_rotas), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));
                    // }
                } else {
                    return response()->json($employee_rotas, 200);
                }


            return response()->json($employee_rotas, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/employee-rotas/{id}",
     *      operationId="getEmployeeRotaById",
     *      tags={"administrator.employee_rota"},
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
     *      summary="This method is to get employee rota by id",
     *      description="This method is to get employee rota by id",
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


    public function getEmployeeRotaById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_rota_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $all_manager_department_ids = $this->get_all_departments_of_manager();
            $user_department = auth()->user()->departments[0];

            $employee_rota =  EmployeeRota::with("details")
            ->where([
                "id" => $id
            ])
            ->where(function($query) use($all_manager_department_ids, $user_department) {
                $query
                ->where([
                    "employee_rotas.business_id" => auth()->user()->business_id
                ])
                ->where(function($query) use ($all_manager_department_ids, $user_department) {
                    $query->whereHas("departments", function ($query) use ($all_manager_department_ids, $user_department) {
                        $query->whereIn("departments.id", array_merge($all_manager_department_ids,[$user_department]));
                    })
                    ->orWhereHas("users.department_user.department", function ($query) use ($all_manager_department_ids, $user_department) {
                        $query->whereIn("departments.id", array_merge($all_manager_department_ids,[$user_department]));
                    });
                })
                ;

            })


                ->first();
            if (empty($employee_rota)) {

                return response()->json([
                    "message" => "no employee rota found"
                ], 404);
            }
            $employee_rota->departments = $employee_rota->departments;
            $employee_rota->users = $employee_rota->users;

            return response()->json($employee_rota, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }

    }


  /**
     *
     * @OA\Get(
     *      path="/v1.0/employee-rotas/get-by-user-id/{user_id}",
     *      operationId="getEmployeeRotaByUserId",
     *      tags={"administrator.employee_rota"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="user_id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get employee rota by user id",
     *      description="This method is to get employee rota by user id",
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


     public function getEmployeeRotaByUserId($user_id, Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('employee_rota_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $business_id =  auth()->user()->business_id;


             $employee_rota =   EmployeeRota::with("details")
            ->where(function($query) use($business_id,$user_id) {
                $query->where([
                    "business_id" => $business_id
                ])->whereHas('users', function ($query) use ($user_id) {
                    $query->where('users.id', $user_id);
                });
            })

            ->orWhere(function($query)  {
                $query->where([
                    "is_active" => 1,
                    "business_id" => NULL,
                    "is_default" => 1
                ])
            //     ->whereHas('details', function($query) use($business_times) {

            //     foreach($business_times as $business_time) {
            //         $query->where([
            //             "day" => $business_time->day,
            //         ]);
            //         if($business_time["is_weekend"]) {
            //             $query->where([
            //                 "is_weekend" => 1,
            //             ]);
            //         } else {
            //             $query->where(function($query) use($business_time) {
            //                 $query->whereTime("start_at", ">=", $business_time->start_at);
            //                 $query->orWhereTime("end_at", "<=", $business_time->end_at);
            //             });
            //         }

            //     }
            // })
            ;

            })

            ->first();



             if (!$employee_rota) {

                 return response()->json([
                     "message" => "no employee rota found for the user"
                 ], 404);
             }

             return response()->json($employee_rota, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/employee-rotas/{ids}",
     *      operationId="deleteEmployeeRotasByIds",
     *      tags={"administrator.employee_rota"},
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
     *      summary="This method is to delete employee rota by id",
     *      description="This method is to delete employee rota by id",
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

    public function deleteEmployeeRotasByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_rota_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = EmployeeRota::where([
                "business_id" => $business_id,
                "is_default" => 0
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

            $user_exists =  User::

            whereHas("employee_rotas", function($query) use($existingIds) {
                $query->whereIn("employee_rotas.id", $existingIds);
            })
            ->where("users.business_id",auth()->user()->id)
           ->exists();

            if ($user_exists) {
                $conflictingUsers = User:: whereHas("employee_rotas", function($query) use($existingIds) {
                    $query->whereIn("employee_rotas.id", $existingIds);
                })
                ->where("users.business_id",auth()->user()->id)
                ->select([
                    'users.id',
                    'users.first_Name',
                    'users.last_Name',
                ])
                ->get()
                ;

                return response()->json([
                    "message" => "Some users are associated with the specified employee rotas",
                    "conflicting_users" => $conflictingUsers,
                    "conflicting_users2" => $conflictingUsers
                ], 409);
            }

            EmployeeRota::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
