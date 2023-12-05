<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentCreateRequest;
use App\Http\Requests\DepartmentUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
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
     *    @OA\Property(property="location", type="string", format="string",example="location"),
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
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('department_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                $check_manager = $this->checkManager($request_data["manager_id"]);
                if (!$check_manager["ok"]) {
                    return response()->json([
                        "message" => $check_manager["message"]
                    ], $check_manager["status"]);
                }
                if (!empty($request_data["parent_id"])) {
                    $check_department = $this->checkDepartment($request_data["parent_id"]);
                    if (!$check_department["ok"]) {
                        return response()->json([
                            "message" => $check_department["message"]
                        ], $check_department["status"]);
                    }
                } else {
                    $parent_department = Department::whereNull('parent_id')
                    ->where('departments.business_id', '=', auth()->user()->business_id)
                    ->first();

                    $request_data["parent_id"] = $parent_department["id"];
                }

                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;

                $department =  Department::create($request_data);
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
     *    @OA\Property(property="location", type="string", format="string",example="location"),
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
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('department_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();







                $check_manager = $this->checkManager($request_data["manager_id"]);
                if (!$check_manager["ok"]) {
                    return response()->json([
                        "message" => $check_manager["message"]
                    ], $check_manager["status"]);
                }

                if (!empty($request_data["parent_id"])) {
                    $check_department = $this->checkDepartment($request_data["parent_id"]);
                    if (!$check_department["ok"]) {
                        return response()->json([
                            "message" => $check_department["message"]
                        ], $check_department["status"]);
                    }
                } else {
                    $parent_department = Department::whereNull('parent_id')
                    ->where('departments.business_id', '=', auth()->user()->business_id)
                    ->first();

                    $request_data["parent_id"] = $parent_department["id"];
                }

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

                $department  =  tap(Department::where($department_query_params))->update(
                    collect($request_data)->only([
                        "name",
                        "location",
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

                return response($department, 201);
            });
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
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('department_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $departments = Department::where(
                [
                    "business_id" => $business_id
                ]
            )
            ->whereNotNull("parent_id")
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("name", "like", "%" . $term . "%")
                            ->orWhere("location", "like", "%" . $term . "%")
                            ->orWhere("description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", $request->end_date);
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
             $this->storeActivity($request, "");
             if (!$request->user()->hasPermissionTo('department_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $business_id =  $request->user()->business_id;
             $department = Department::where(
                 [
                     "business_id" => $business_id,
                     "parent_id" => NULL
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

                $department->all_children_data = $department->all_children_data;

             return response()->json($department, 200);
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
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('department_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
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
            $this->storeActivity($request, "");
            if (!$request->user()->hasPermissionTo('department_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Department::where([
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
            Department::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
