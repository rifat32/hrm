<?php

namespace App\Http\Controllers;


use App\Http\Requests\ProjectAssignToUserRequest;
use App\Http\Requests\ProjectCreateRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Requests\UserAssignToProjectRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\EmployeeProjectHistory;
use App\Models\Project;
use App\Models\User;
use App\Models\UserProject;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil,ModuleUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/projects",
     *      operationId="createProject",
     *      tags={"project"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store project listing",
     *      description="This method is to store project listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

 *     @OA\Property(property="name", type="string", format="string", example="Project X"),
 *     @OA\Property(property="description", type="string", format="string", example="A brief overview of Project X's objectives and scope."),
 *     @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2023-12-31"),
 *     @OA\Property(property="status", type="string", format="string", example="progress"),
 *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3}),
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

    public function createProject(ProjectCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            // if(!$this->isModuleEnabled("project_and_task_management")) {
            //     return response()->json(['error' => 'Module is not enabled'], 403);
            //  }
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('project_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;

                $project =  Project::create($request_data);
                $project->departments()->sync($request_data['departments'], []);
                return response($project, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

     /**
     *
     * @OA\Put(
     *      path="/v1.0/projects/assign-user",
     *      operationId="assignUser",
     *      tags={"project"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update project listing ",
     *      description="This method is to update project listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
     *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
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

     public function assignUser(UserAssignToProjectRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             // if(!$this->isModuleEnabled("project_and_task_management")) {
             //     return response()->json(['error' => 'Module is not enabled'], 403);
             //  }
             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('project_update')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }
                 $business_id =  $request->user()->business_id;
                 $request_data = $request->validated();




                 $project_query_params = [
                     "id" => $request_data["id"],
                     "business_id" => $business_id
                 ];


                 $project  =  Project::where($project_query_params)
                     ->first();


                 if (!$project) {
                     return response()->json([
                         "message" => "something went wrong."
                     ], 500);
                 }




                 $discharged_users =  User::whereHas("projects",function($query) use($project){
                    $query->where("users.id",$project->id);
                 })
                 ->whereNotIn("id",$request_data['users'])
                 ->get();



                 EmployeeProjectHistory::where([
                    "project_id" => $project->id,
                    "to_date" => NULL
                 ])
                 ->whereIn("project_id",$discharged_users->pluck("id"))
                 ->update([
                    "to_date" => now()
                 ])
                 ;


                 foreach($request_data['users'] as $user_id) {
                  $user = User::
                  whereHas("projects",function($query) use($project){
                    $query->where("projects.id",$project->id);
                 })
                   ->where([
                    "id" => $user_id
                   ])
                    ->first();


                    if(!$user) {

                        $user = User::where([
                           "id" => $user_id
                        ])
                        ->first();

                        if(!$user) {
                            throw new Exception("some thing went wrong");
                        }

                        // UserProject::create([
                        //     "user_id" => $user->id,
                        //     "project_id" => $project->id
                        // ]);



          $employee_project_history_data = $project->toArray();
          $employee_project_history_data["employee_id"] = $user->id;
          $employee_project_history_data["project_id"] = $employee_project_history_data["id"];
          $employee_project_history_data["from_date"] = now();
          $employee_project_history_data["to_date"] = NULL;

          EmployeeProjectHistory::create($employee_project_history_data);


                    }


                 }


                 $project->users()->sync($request_data['users'], []);

                 return response($project, 201);

             });
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }

  /**
     *
     * @OA\Put(
     *      path="/v1.0/projects/assign-project",
     *      operationId="assignProject",
     *      tags={"project"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update project listing ",
     *      description="This method is to update project listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
     *     @OA\Property(property="projects", type="string", format="array", example={1,2,3}),
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

     public function assignProject(ProjectAssignToUserRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             // if(!$this->isModuleEnabled("project_and_task_management")) {
             //     return response()->json(['error' => 'Module is not enabled'], 403);
             //  }
             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('project_update')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }
                 $business_id =  $request->user()->business_id;
                 $request_data = $request->validated();




                 $user_query_params = [
                     "id" => $request_data["id"],
                 ];


                 $user  =  User::where($user_query_params)
                     ->first();


                 if (!$user) {
                     return response()->json([
                         "message" => "something went wrong."
                     ], 500);
                 }


                 $discharged_projects =  Project::whereHas("users",function($query) use($user){
                    $query->where("users.id",$user->id);
                 })
                 ->whereNotIn("id",$request_data['projects'])
                 ->get();



                 EmployeeProjectHistory::where([
                    "employee_id" => $user->id,
                    "to_date" => NULL
                 ])
                 ->whereIn("project_id",$discharged_projects->pluck("id"))
                 ->update([
                    "to_date" => now()
                 ])
                 ;


                 foreach($request_data['projects'] as $project_id) {
                  $project = Project::
                  whereHas("users",function($query) use($user){
                    $query->where("users.id",$user->id);
                 })
                   ->where([
                    "id" => $project_id
                   ])
                    ->first();


                    if(!$project) {

                        $project = Project::where([
                           "id" => $project_id
                        ])
                        ->first();

                        if(!$project) {
                            throw new Exception("some thing went wrong");
                        }

                        // UserProject::create([
                        //     "user_id" => $user->id,
                        //     "project_id" => $project->id
                        // ]);



          $employee_project_history_data = $project->toArray();
          $employee_project_history_data["project_id"] = $employee_project_history_data["id"];
          $employee_project_history_data["employee_id"] = $user->id;
          $employee_project_history_data["from_date"] = now();
          $employee_project_history_data["to_date"] = NULL;

          EmployeeProjectHistory::create($employee_project_history_data);


                    }



                 }




                 $user->projects()->sync($request_data['projects'], []);



                 return response($user, 201);

             });
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/projects",
     *      operationId="updateProject",
     *      tags={"project"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update project listing ",
     *      description="This method is to update project listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
 *     @OA\Property(property="name", type="string", format="string", example="Project X"),
 *     @OA\Property(property="description", type="string", format="string", example="A brief overview of Project X's objectives and scope."),
 *     @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2023-12-31"),
 *     @OA\Property(property="status", type="string", format="string", example="progress"),
 *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3})
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

    public function updateProject(ProjectUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            // if(!$this->isModuleEnabled("project_and_task_management")) {
            //     return response()->json(['error' => 'Module is not enabled'], 403);
            //  }
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('project_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();




                $project_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                // $project_prev = Project::where($project_query_params)
                //     ->first();
                // if (!$project_prev) {
                //     return response()->json([
                //         "message" => "no project listing found"
                //     ], 404);
                // }

                $project  =  tap(Project::where($project_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                        'start_date',
                        'end_date',
                        'status',


                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$project) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                return response($project, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/projects",
     *      operationId="getProjects",
     *      tags={"project"},
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
     *      * *  @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),

     *      summary="This method is to get project listings  ",
     *      description="This method is to get project listings ",
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

    public function getProjects(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            // if(!$this->isModuleEnabled("project_and_task_management")) {
            //     return response()->json(['error' => 'Module is not enabled'], 403);
            //  }
            if (!$request->user()->hasPermissionTo('project_view')) {
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


            $projects = Project::with("departments")
            ->where(
                [
                    "business_id" => $business_id
                ]
            )  ->whereHas("departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
             ->when(!empty($request->user_id), function ($query) use ($request) {
                return $query->whereHas('users', function($query) use($request) {
                        $query->where("users.id",$request->user_id);
                });
            })

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("name", "like", "%" . $term . "%")
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
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("projects.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("projects.id", "DESC");
                })

                ->select('projects.*',

                 )

                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });



            return response()->json($projects, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/projects/{id}",
     *      operationId="getProjectById",
     *      tags={"project"},
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
     *      summary="This method is to get project listing by id",
     *      description="This method is to get project listing by id",
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


    public function getProjectById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            // if(!$this->isModuleEnabled("project_and_task_management")) {
            //     return response()->json(['error' => 'Module is not enabled'], 403);
            //  }
            if (!$request->user()->hasPermissionTo('project_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $business_id =  $request->user()->business_id;
            $project =  Project::
            where([
                "id" => $id,
                "business_id" => $business_id
            ])
            ->whereHas("departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
            ->select('projects.*'
             )
                ->first();

            if (!$project) {
                return response()->json([
                    "message" => "no project listing found"
                ], 404);
            }

            return response()->json($project, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/projects/{ids}",
     *      operationId="deleteProjectsByIds",
     *      tags={"project"},
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
     *      summary="This method is to delete project listing by id",
     *      description="This method is to delete project listing by id",
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

    public function deleteProjectsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            // if(!$this->isModuleEnabled("project_and_task_management")) {
            //     return response()->json(['error' => 'Module is not enabled'], 403);
            //  }

            if (!$request->user()->hasPermissionTo('project_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Project::where([
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
            Project::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
