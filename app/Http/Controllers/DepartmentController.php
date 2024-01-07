<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentCreateRequest;
use App\Http\Requests\DepartmentUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\DepartmentUser;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/departments",
     *      operationId="createDepartment",
     *      tags={"administrator.department"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store department",
     *      description="This method is to store department",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

     *    @OA\Property(property="name", type="string", format="string",example="name"),
     *    @OA\Property(property="work_location_id", type="string", format="string",example="1"),
     *    @OA\Property(property="description", type="string", format="string",example="description"),
     *   *    @OA\Property(property="manager_id", type="number", format="number",example="1"),
     * *   *    @OA\Property(property="parent_id", type="number", format="number",example="1")
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

    public function createDepartment(DepartmentCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('department_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();



                if (empty($request_data["parent_id"])) {
                    $parent_department = Department::whereNull('parent_id')
                    ->where('departments.business_id', '=', auth()->user()->business_id)
                    ->first();

                    $request_data["parent_id"] = $parent_department["id"];
                }

                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;

                $department =  Department::create($request_data);

                if(!empty($department->manager_id)) {
                    $department->users()->attach($department->manager_id);
                }

                return response($department, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/departments",
     *      operationId="updateDepartment",
     *      tags={"administrator.department"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update department ",
     *      description="This method is to update department",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
     *    @OA\Property(property="name", type="string", format="string",example="name"),
     *    @OA\Property(property="work_location_id", type="string", format="string",example="1"),
     *    @OA\Property(property="description", type="string", format="string",example="description"),
     *    @OA\Property(property="manager_id", type="number", format="number",example="1"),
     *    @OA\Property(property="parent_id", type="number", format="number",example="1")

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

    public function updateDepartment(DepartmentUpdateRequest $request)
    {


        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('department_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();










                $department_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                $department_prev = Department::where($department_query_params)
                    ->first();
                if (!$department_prev) {
                    return response()->json([
                        "message" => "no department found"
                    ], 404);
                }
                $main_parent_department = Department::whereNull('parent_id')
                ->where('departments.business_id', '=', auth()->user()->business_id)
                ->first();

                if (!$main_parent_department) {
                    return response()->json([
                        "message" => "main parent not found."
                    ], 409);
                }


                if (empty($request_data["parent_id"])) {
                    $request_data["parent_id"] = $main_parent_department->id;
                } else {
                    $previous_parent_id =  $department_prev->parent_id;
                    if($previous_parent_id !== $request_data["parent_id"]) {
                        $descendantIds = $department_prev->getAllDescendantIds();
                        if (in_array($request_data["parent_id"], $descendantIds)) {
                         Department::where([
                            "id" => $request_data["parent_id"]
                          ])->update(
                            [
                                "parent_id" => $main_parent_department->id
                            ]
                          );


                        }
                    }


                }


                $department  =  tap(Department::where($department_query_params))->update(
                    collect($request_data)->only([
                        "name",
                        "work_location",
                        "description",
                        // "is_active",
                        "manager_id",
                      "parent_id",
                        // "business_id",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$department) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }





                if ($department_prev->manager_id != $department->manager_id) {
                    // Remove the previous manager's relationship with the department
                    DepartmentUser::where([
                        'department_id' => $department->id,
                        'user_id'       => $department_prev->manager_id
                    ])->delete();

                    // Attach the new manager to the department
                    $department->users()->attach($department->manager_id);
                }





                return response($department, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

  /**
     *
     * @OA\Put(
     *      path="/v1.0/departments/toggle-active",
     *      operationId="toggleActiveDepartment",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle department activity",
     *      description="This method is to toggle department activity",
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


     public function toggleActiveDepartment(GetIdRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('user_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();


             $all_manager_department_ids = [];
             $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
             foreach ($manager_departments as $manager_department) {
                 $all_manager_department_ids[] = $manager_department->id;
                 $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
             }

            $department = Department::where([
                "id" => $request_data["id"],
                "business_id" => auth()->user()->business_id
            ])
                ->first();
            if (!$department) {
                return response()->json([
                    "message" => "no department found"
                ], 404);
            }
            if (!$department->parent_id) {
                return response()->json([
                    "message" => "You can not change the status of main parent department."
                ], 409);
            }
            if(!in_array($department->id,$all_manager_department_ids)){
                return response()->json([
                    "message" => "You don't have access to this department"
                ], 403);
            }


             $department->update([
                 'is_active' => !$department->is_active
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
     *      path="/v1.0/departments",
     *      operationId="getDepartments",
     *      tags={"administrator.department"},
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

     *      summary="This method is to get departments  ",
     *      description="This method is to get departments ",
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

    public function getDepartments(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('department_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;

            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }

            $departments = Department::where(
                [
                    "business_id" => $business_id
                ]
            )
            ->whereIn("id",$all_manager_department_ids)

            ->when(isset($request->show_parent), function ($query) use ($request) {
                if(intval($request->show_parent)) {
                    return $query->whereNotNull("parent_id");
                } else {
                    return $query;
                }

            })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("name", "like", "%" . $term . "%")
                            ->orWhere("description", "like", "%" . $term . "%");
                    });
                })
                ->when(isset($request->is_active), function ($query) use ($request) {
                    return $query->where('departments.is_active', intval($request->is_active));
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('departments.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('departments.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("departments.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("departments.id", "DESC");
                })
                ->select('departments.*',
                DB::raw('
         COALESCE(
             (SELECT COUNT(department_users.user_id) FROM department_users WHERE department_users.department_id = departments.id),
             0
         ) AS total_users
         '),
                 )
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });



            return response()->json($departments, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
  /**
     *
     * @OA\Get(
     *      path="/v2.0/departments",
     *      operationId="getDepartmentsV2",
     *      tags={"administrator.department"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="This method is to get departments  ",
     *      description="This method is to get departments ",
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

     public function getDepartmentsV2(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('department_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $business_id =  $request->user()->business_id;
             $department = Department::with([

                "manager" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                }

            ])
            ->where(
                 [
                     "business_id" => $business_id,
                    //  "parent_id" => NULL,
                     "manager_id" => auth()->user()->id
                 ]
             )

                 ->orderBy("departments.id", "ASC")
                 ->select('departments.*')
                ->first();

                if (!$department) {
                    return response()->json([
                        "message" => "no department found"
                    ], 404);
                }

                $department->children_recursive = $department->children_recursive;

                $department->total_users_counts = User::where([
                    "business_id" => $business_id
                ])
                ->count();


             return response()->json($department, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }
      /**
     *
     * @OA\Get(
     *      path="/v3.0/departments",
     *      operationId="getDepartmentsV3",
     *      tags={"administrator.department"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="This method is to get departments  ",
     *      description="This method is to get departments ",
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

     public function getDepartmentsV3(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('department_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $business_id =  $request->user()->business_id;

             $departments = Department::with([

                "manager" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                }

            ])
            ->where(
                 [
                     "business_id" => $business_id,
                    //  "parent_id" => NULL,
                     "manager_id" => auth()->user()->id
                 ]
             )

                 ->orderBy("departments.id", "ASC")
                 ->select('departments.*')
                ->get();

                foreach($departments as $department){
                    $department->children_recursive = $department->children_recursive;

                    $department->total_users_counts = User::where([
                        "business_id" => $business_id
                    ])
                    ->count();
                }






             return response()->json($departments, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/departments/{id}",
     *      operationId="getDepartmentById",
     *      tags={"administrator.department"},
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
     *      summary="This method is to get department by id",
     *      description="This method is to get department by id",
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


    public function getDepartmentById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('department_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }

            $department =  Department::where([
                "id" => $id,
                "business_id" => $business_id
            ])
            ->select('departments.*',
            DB::raw('
     COALESCE(
         (SELECT COUNT(department_users.user_id) FROM department_users WHERE department_users.department_id = departments.id),
         0
     ) AS total_users
     '),
             )
                ->first();
            if (!$department) {
                return response()->json([
                    "message" => "no department found"
                ], 404);
            }
            if(!in_array($department->id,$all_manager_department_ids)){
                return response()->json([
                    "message" => "You don't have access to this department"
                ], 403);
            }

            return response()->json($department, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/departments/{ids}",
     *      operationId="deleteDepartmentsByIds",
     *      tags={"administrator.department"},
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
     *      summary="This method is to delete department by id",
     *      description="This method is to delete department by id",
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

    public function deleteDepartmentsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('department_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", auth()->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }

            $idsArray = explode(',', $ids);
            $existingIds = Department::where([
                "business_id" => $business_id
            ])
            ->whereNotNull('parent_id')
            ->whereIn("id",$all_manager_department_ids)
                ->whereIn('id', $idsArray)
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {
                return response()->json([
                    "message" => "Some or all of the specified data do not exist. or something else"
                ], 404);
            }

            Department::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
