<?php

namespace App\Http\Controllers;

use App\Exports\ProjectsExport;
use App\Http\Components\ProjectComponent;
use App\Http\Requests\ProjectAssignToUserRequest;
use App\Http\Requests\ProjectCreateRequest;
use App\Http\Requests\ProjectUpdateRequest;
use App\Http\Requests\UserAssignToProjectRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\AttendanceProject;
use App\Models\Department;
use App\Models\EmployeeProjectHistory;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\TaskCategory;
use App\Models\User;
use App\Models\UserProject;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use PDF;
use Maatwebsite\Excel\Facades\Excel;

class ProjectController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, ModuleUtil, BasicUtil;


    protected $projectComponent;


    public function __construct(ProjectComponent $projectComponent)
    {
        $this->projectComponent = $projectComponent;
    }



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
     *     @OA\Property(property="status", type="string", format="string", example="in_progress"),
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
        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");


            if (!$request->user()->hasPermissionTo('project_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $request_data = $request->validated();

            $request_data["is_active"] = 1;
            $request_data["is_default"] = 0;
            $request_data["created_by"] = $request->user()->id;
            $request_data["business_id"] = auth()->user()->business_id;

            if (empty(auth()->user()->business_id)) {
                $request_data["business_id"] = NULL;
                if ($request->user()->hasRole('superadmin')) {
                    $request_data["is_default"] = 1;
                }
            }


            $project =  Project::create($request_data);



            // $business_created_by  = NULL;
            // if(auth()->user()->business) {
            //     $business_created_by = auth()->user()->business->created_by;
            // }


            //         $default_task_categories = TaskCategory::where('task_categories.business_id', NULL)
            //         ->where('task_categories.is_default', 1)
            //         ->where('task_categories.is_active', 1)
            //         ->whereDoesntHave("disabled", function($q) use($business_created_by) {
            //             $q->whereIn("disabled_task_categories.created_by", [$business_created_by])
            //             ->whereIn("disabled_task_categories.business_id",[auth()->user()->business_id]);

            //         })
            //          ->orWhere(function ($query) use( $business_created_by){
            //             $query->where('task_categories.business_id', NULL)
            //                 ->where('task_categories.is_default', 0)
            //                 ->where('task_categories.created_by', $business_created_by)
            //                 ->where('task_categories.is_active', 1)
            //                 ;
            //         })
            //         ->orWhere(function ($query) {
            //             $query->where('task_categories.business_id', auth()->user()->business_id)
            //                 ->where('task_categories.is_default', 0)
            //                 ->where('task_categories.is_active', 1);
            //         })
            // ->get();

            // foreach($default_task_categories as $index => $default_task_category){

            //     $default_task_category->project_id =  $project->id;
            //     $default_task_category->business_id =  auth()->user()->business_id;
            //     $default_task_category->is_default =  0;
            //     $default_task_category->is_active =  0;
            //     $default_task_category->created_by =  auth()->user()->id;


            //     $default_task_category->order_no = $index;



            //  $task_category =   TaskCategory::create($default_task_category->toArray());





            // }








            if (empty($request_data['departments'])) {
                $request_data['departments'] = [Department::where("business_id", auth()->user()->business_id)->whereNull("parent_id")->first()->id];
            }


            $project->departments()->sync($request_data['departments']);




            DB::commit();
            return response($project, 201);
        } catch (Exception $e) {
            DB::rollBack();
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

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");



            if (!$request->user()->hasPermissionTo('project_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
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




            //  $discharged_users =  User::whereHas("projects",function($query) use($project){
            //     $query->where("users.id",$project->id);
            //  })
            //  ->whereIn("id",$request_data['users'])
            //  ->get();



            //  EmployeeProjectHistory::where([
            //     "project_id" => $project->id,
            //     "to_date" => NULL
            //  ])
            //  ->whereIn("project_id",$discharged_users->pluck("id"))
            //  ->update([
            //     "to_date" => now()
            //  ])
            //  ;


            foreach ($request_data['users'] as $index => $user_id) {
                $user = User::whereHas("projects", function ($query) use ($project) {
                        $query->where("projects.id", $project->id);
                    })
                    ->where([
                        "id" => $user_id
                    ])
                    ->first();

                if ($user) {

                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("users." . $index) => ["The project is already belongs to that user."]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }




                $user = User::where([
                    "id" => $user_id
                ])
                    ->first();

                if (!$user) {
                    throw new Exception("some thing went wrong");
                }

                // UserProject::create([
                //     "user_id" => $user->id,
                //     "project_id" => $project->id
                // ]);



                $employee_project_history_data = $project->toArray();
                $employee_project_history_data["user_id"] = $user->id;
                $employee_project_history_data["project_id"] = $employee_project_history_data["id"];
                $employee_project_history_data["from_date"] = now();
                $employee_project_history_data["to_date"] = NULL;

                EmployeeProjectHistory::create($employee_project_history_data);
            }


            $project->users()->attach($request_data['users']);


            $notification_data = collect($request_data['users'])->map(function($user_id) use($project) {

                $notification_description = "You have been assigned to a project.";
                $notification_link = "http://example.com/projects/{$project->id}"; // Dynamic link based on project ID
                return [
                    "entity_id" => $project->id,
                    "entity_ids" => json_encode([$project->id]),
                    "entity_name" => "project",
                    'notification_title' => "Project Assignment Notification",
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id, // Assuming you have a variable for the assigner's ID
                    "receiver_id" => $user_id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            });


            Notification::insert($notification_data->toArray());



            DB::commit();
            return response($project, 201);
        } catch (Exception $e) {
            DB::rollBack();
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/projects/discharge-user",
     *      operationId="dischargeUser",
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

    public function dischargeUser(UserAssignToProjectRequest $request)
    {

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");




            if (!$request->user()->hasPermissionTo('project_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
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




            $discharged_users =  User::whereHas("projects", function ($query) use ($project) {
                $query->where("users.id", $project->id);
            })
                ->whereIn("id", $request_data['users'])
                ->get();



            EmployeeProjectHistory::where([
                "project_id" => $project->id,
                "to_date" => NULL
            ])
                ->whereIn("project_id", $discharged_users->pluck("id"))
                ->update([
                    "to_date" => now()
                ]);


            foreach ($request_data['users'] as $index => $user_id) {
                $user = User::whereHas("projects", function ($query) use ($project) {
                        $query->where("projects.id", $project->id);
                    })
                    ->where([
                        "id" => $user_id
                    ])
                    ->first();

                if (!$user) {

                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("projects." . $index) => ["The project is already belongs to that user."]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }
            }


            $project->users()->detach($request_data['users']);

            $notification_data = collect($request_data['users'])->map(function($user_id) use($project) {

                $notification_description = "You have been removed from a project.";
                $notification_link = "http://example.com/projects/{$project->id}"; // Dynamic link based on project ID
                return [
                    "entity_id" => $project->id,
                    "entity_ids" => json_encode([$project->id]),

                    "entity_name" => "project",
                    'notification_title' => "Project Deassignment Notification",
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id, // Assuming you have a variable for the deassigner's ID
                    "receiver_id" => $user_id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            });

            Notification::insert($notification_data->toArray());

            DB::commit();
            return response($project, 201);
        } catch (Exception $e) {
            DB::rollBack();
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

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");



            if (!$request->user()->hasPermissionTo('project_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
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

            // need this in remove api
            //  $discharged_projects =  Project::whereHas("users",function($query) use($user){
            //     $query->where("users.id",$user->id);
            //  })
            //  ->whereNotIn("id",$request_data['projects'])
            //  ->get();
            //  EmployeeProjectHistory::where([
            //     "user_id" => $user->id,
            //     "to_date" => NULL
            //  ])
            //  ->whereIn("project_id",$discharged_projects->pluck("id"))
            //  ->update([
            //     "to_date" => now()
            //  ])
            //  ;


            foreach ($request_data['projects'] as $index => $project_id) {
                $project = Project::whereHas("users", function ($query) use ($user) {
                        $query->where("users.id", $user->id);
                    })
                    ->where([
                        "id" => $project_id
                    ])
                    ->first();

                if ($project) {
                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("projects." . $index) => ["The project is already belongs to that user."]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }




                $project = Project::where([
                    "id" => $project_id
                ])
                    ->first();

                if (!$project) {
                    throw new Exception("some thing went wrong");
                }

                // UserProject::create([
                //     "user_id" => $user->id,
                //     "project_id" => $project->id
                // ]);



                $employee_project_history_data = $project->toArray();
                $employee_project_history_data["project_id"] = $employee_project_history_data["id"];
                $employee_project_history_data["user_id"] = $user->id;
                $employee_project_history_data["from_date"] = now();
                $employee_project_history_data["to_date"] = NULL;

                EmployeeProjectHistory::create($employee_project_history_data);
            }




            $user->projects()->attach($request_data['projects']);


            $notification_data = collect($request_data['projects'])->map(function($project_id) use($user) {

                $notification_description = "You have been assigned to a project.";
                $notification_link = "http://example.com/projects/{$project_id}"; // Dynamic link based on project ID
                return [
                    "entity_id" => $project_id,
                    "entity_ids" => json_encode([$project_id]),
                    "entity_name" => "project",
                    'notification_title' => "Project Deassignment Notification",
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id, // Assuming you have a variable for the deassigner's ID
                    "receiver_id" => $user->id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            });

            Notification::insert($notification_data->toArray());

            DB::commit();

            return response($user, 201);
        } catch (Exception $e) {


            DB::rollBack();

            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/projects/discharge-project",
     *      operationId="dischargeProject",
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

    public function dischargeProject(ProjectAssignToUserRequest $request)
    {

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");



            if (!$request->user()->hasPermissionTo('project_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
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
            $discharged_projects =  Project::whereHas("users", function ($query) use ($user) {
                $query->where("users.id", $user->id);
            })
                ->whereIn("id", $request_data['projects'])
                ->get();
            EmployeeProjectHistory::where([
                "user_id" => $user->id,
                "to_date" => NULL
            ])
                ->whereIn("project_id", $discharged_projects->pluck("id"))
                ->update([
                    "to_date" => now()
                ]);


            foreach ($request_data['projects'] as $index => $project_id) {
                $project = Project::whereHas("users", function ($query) use ($user) {
                        $query->where("users.id", $user->id);
                    })
                    ->where([
                        "id" => $project_id
                    ])
                    ->first();


                if (!$project) {

                    $error = [
                        "message" => "The given data was invalid.",
                        "errors" => [("projects." . $index) => ["The project is not belongs to that user."]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }
            }


            $user->projects()->detach($request_data['projects']);

            $notification_data = collect($request_data['projects'])->map(function($project_id) use($user) {

                $notification_description = "You have been removed from a project.";
                $notification_link = "http://example.com/projects/{$project_id}"; // Dynamic link based on project ID
                return [
                    "entity_id" => $project_id,
                    "entity_ids" => json_encode([$project_id]),
                    "entity_name" => "project",
                    'notification_title' => "Project Deassignment Notification",
                    'notification_description' => $notification_description,
                    'notification_link' => $notification_link,
                    "sender_id" => auth()->user()->id, // Assuming you have a variable for the deassigner's ID
                    "receiver_id" => $user->id,
                    "business_id" => auth()->user()->business_id,
                    "is_system_generated" => 1,
                    "status" => "unread",
                    "created_at" => now(),
                    "updated_at" => now()
                ];
            });
            Notification::insert($notification_data->toArray());


            DB::commit();

            return response($user, 201);
        } catch (Exception $e) {
            DB::rollBack();
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
     *     @OA\Property(property="status", type="string", format="string", example="in_progress"),
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

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");


            if (!$request->user()->hasPermissionTo('project_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
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

            $project = Project::where($project_query_params)->first();

            if (!$project) {
                return response()->json([
                    "message" => "something went wrong."
                ], 500);
            }

            if ($project->is_default) {
                $request_data["end_date"] = NULL;
            }


            $project->fill(collect($request_data)->only([
                'name',
                'description',
                'start_date',
                'end_date',
                'status',
                // "is_active",
                // "business_id",
                // "created_by"

            ])->toArray());
            $project->save();




            if (empty($request_data['departments'])) {
                $request_data['departments'] = [Department::where("business_id", auth()->user()->business_id)->whereNull("parent_id")->first()->id];
            }
            $project->departments()->sync($request_data['departments']);

            DB::commit();

            return response($project, 201);
        } catch (Exception $e) {
            DB::rollBack();
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
     *
     *
     *
     *     @OA\Parameter(
     *         name="response_type",
     *         in="query",
     *         description="response_type: in pdf,csv,json",
     *         required=true,
     *  example="json"
     *      ),
     *     @OA\Parameter(
     *         name="file_name",
     *         in="query",
     *         description="file_name",
     *         required=true,
     *  example="employee"
     *      ),
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
     *  @OA\Parameter(
     * name="in_date",
     * in="query",
     * description="in_date",
     * required=true,
     * example="2019-06-29"
     * ),
     *
     * *  @OA\Parameter(
     * name="search_key",
     * in="query",
     * description="search_key",
     * required=true,
     * example="search_key"
     * ),
     *
     *  * *  @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     *
     *
     * @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="status"
     * ),
     *
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
     * @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     * @OA\Parameter(
     * name="assigned_user_id_not",
     * in="query",
     * description="assigned_user_id_not",
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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            $user_id = intval(!empty(request()->user_id)?request()->user_id :0);
            $auth_user_id = auth()->user()->id;
            if (!$request->user()->hasPermissionTo('project_view') && ($auth_user_id !== $user_id)) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }





            $projects = $this->projectComponent->getProjects();

            if (!empty($request->response_type) && in_array(strtoupper($request->response_type), ['PDF', 'CSV'])) {
                if (strtoupper($request->response_type) == 'PDF') {
                    $pdf = PDF::loadView('pdf.projects', ["projects" => $projects]);
                    return $pdf->download(((!empty($request->file_name) ? $request->file_name : 'employee') . '.pdf'));
                } elseif (strtoupper($request->response_type) === 'CSV') {
                    return Excel::download(new ProjectsExport($projects), ((!empty($request->file_name) ? $request->file_name : 'employee') . '.csv'));
                }
            } else {
                return response()->json($projects, 200);
            }

            // @@@@@@@@@@@@@@@@

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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");



            if (!$request->user()->hasPermissionTo('project_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $business_id =  auth()->user()->business_id;
            $project =  $project = Project::with(['departments', 'users'])
            ->where([
                'id' => $id,
                'business_id' => $business_id
            ])
            ->select([
                'projects.*', // Select all columns from the projects table
            ])
            ->selectSub(
                Task::selectRaw('COUNT(*)')
                    ->whereColumn('tasks.project_id', 'projects.id')
                    ->where('status', 'done'),
                'tasks_count'
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
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");


            if (!$request->user()->hasPermissionTo('project_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  auth()->user()->business_id;
            $idsArray = explode(',', $ids);
            $projects = Project::where([
                "business_id" => $business_id
            ])
                ->whereIn('id', $idsArray)
                ->select('id')
                ->get();

            $canDeleteProjectIds = $projects->filter(function ($asset) {
                return $asset->can_delete;
            })->pluck('id')->toArray();
            $nonExistingIds = array_diff($idsArray, $canDeleteProjectIds);


            if (!empty($nonExistingIds)) {
                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }

            $attendanceExists = AttendanceProject::whereIn("project_id", $idsArray)->exists();

            if ($attendanceExists) {
                return response()->json([
                    "message" => config('messages.delete_restricted'),
                ], 409);
            }


            Project::destroy($idsArray);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $canDeleteProjectIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
