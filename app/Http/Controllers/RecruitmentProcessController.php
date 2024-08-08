<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\RecruitmentProcessCreateRequest;
use App\Http\Requests\RecruitmentProcessUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Business;
use App\Models\CandidateRecruitmentProcess;
use App\Models\DisabledRecruitmentProcess;
use App\Models\RecruitmentProcess;
use App\Models\User;
use App\Models\UserRecruitmentProcess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecruitmentProcessController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/recruitment-processes",
     *      operationId="createRecruitmentProcess",
     *      tags={"recruitment_processes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store recruitment process ",
     *      description="This method is to store recruitment process ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="name", type="string", format="string", example="tttttt"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
     * @OA\Property(property="use_in_employee", type="string", format="string", example="tttttt"),
     * @OA\Property(property="use_in_on_boarding", type="string", format="string", example="tttttt"),
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

    public function createRecruitmentProcess(RecruitmentProcessCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('recruitment_process_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["is_active"] = 1;
                $request_data["is_default"] = 0;
                $request_data["created_by"] = $request->user()->id;
                $request_data["business_id"] = $request->user()->business_id;

                if (empty($request->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $recruitment_process =  RecruitmentProcess::create($request_data);




                return response($recruitment_process, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/recruitment-processes",
     *      operationId="updateRecruitmentProcess",
     *      tags={"recruitment_processes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update recruitment process  ",
     *      description="This method is to update recruitment process ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     * @OA\Property(property="name", type="string", format="string", example="tttttt"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
     * @OA\Property(property="use_in_employee", type="string", format="string", example="tttttt"),
     * @OA\Property(property="use_in_on_boarding", type="string", format="string", example="tttttt"),

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

    public function updateRecruitmentProcess(RecruitmentProcessUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('recruitment_process_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $recruitment_process_query_params = [
                    "id" => $request_data["id"],
                ];

                $recruitment_process  =  tap(RecruitmentProcess::where($recruitment_process_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                        "use_in_employee",
                        "use_in_on_boarding"

                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$recruitment_process) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($recruitment_process, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/recruitment-processes/toggle-active",
     *      operationId="toggleActiveRecruitmentProcess",
     *      tags={"recruitment_processes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle recruitment process ",
     *      description="This method is to toggle recruitment process ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

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

    public function toggleActiveRecruitmentProcess(GetIdRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('recruitment_process_activate')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $recruitment_process =  RecruitmentProcess::where([
                "id" => $request_data["id"],
            ])
                ->first();
            if (!$recruitment_process) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }
            $should_update = 0;
            $should_disable = 0;
            if (empty(auth()->user()->business_id)) {

                if (auth()->user()->hasRole('superadmin')) {
                    if (($recruitment_process->business_id != NULL || $recruitment_process->is_default != 1)) {

                        return response()->json([
                            "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                        ], 403);
                    } else {
                        $should_update = 1;
                    }
                } else {
                    if ($recruitment_process->business_id != NULL) {

                        return response()->json([
                            "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                        ], 403);
                    } else if ($recruitment_process->is_default == 0) {

                        if($recruitment_process->created_by != auth()->user()->id) {

                            return response()->json([
                                "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                            ], 403);
                        }
                        else {
                            $should_update = 1;
                        }



                    }
                    else {
                     $should_disable = 1;

                    }
                }
            } else {
                if ($recruitment_process->business_id != NULL) {
                    if (($recruitment_process->business_id != auth()->user()->business_id)) {

                        return response()->json([
                            "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                        ], 403);
                    } else {
                        $should_update = 1;
                    }
                } else {
                    if ($recruitment_process->is_default == 0) {
                        if ($recruitment_process->created_by != auth()->user()->created_by) {

                            return response()->json([
                                "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                            ], 403);
                        } else {
                            $should_disable = 1;

                        }
                    } else {
                        $should_disable = 1;

                    }
                }
            }

            if ($should_update) {
                $recruitment_process->update([
                    'is_active' => !$recruitment_process->is_active
                ]);
            }

            if($should_disable) {

                $disabled_recruitment_process =    DisabledRecruitmentProcess::where([
                    'recruitment_process_id' => $recruitment_process->id,
                    'business_id' => auth()->user()->business_id,
                    'created_by' => auth()->user()->id,
                ])->first();
                if(!$disabled_recruitment_process) {
                    DisabledRecruitmentProcess::create([
                        'recruitment_process_id' => $recruitment_process->id,
                        'business_id' => auth()->user()->business_id,
                        'created_by' => auth()->user()->id,
                    ]);
                } else {
                    $disabled_recruitment_process->delete();
                }
            }


            return response()->json(['message' => 'Recruitment Process status updated successfully'], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/recruitment-processes",
     *      operationId="getRecruitmentProcesses",
     *      tags={"recruitment_processes"},
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
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *   @OA\Parameter(
     * name="use_in_employee",
     * in="query",
     * description="use_in_employee",
     * required=true,
     * example="1"
     * ),
     *   @OA\Parameter(
     * name="use_in_on_boarding",
     * in="query",
     * description="use_in_on_boarding",
     * required=true,
     * example="1"
     * ),
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
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get recruitment process s  ",
     *      description="This method is to get recruitment process s ",
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

     public function getRecruitmentProcesses(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             if (!$request->user()->hasPermissionTo('recruitment_process_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $created_by  = NULL;
             if(auth()->user()->business) {
                 $created_by = auth()->user()->business->created_by;
             }



             $recruitment_processes = RecruitmentProcess::when(empty($request->user()->business_id), function ($query) use ($request, $created_by) {
                 if (auth()->user()->hasRole('superadmin')) {
                     return $query->where('recruitment_processes.business_id', NULL)
                         ->where('recruitment_processes.is_default', 1)
                         ->when(isset($request->is_active), function ($query) use ($request) {
                             return $query->where('recruitment_processes.is_active', intval($request->is_active));
                         });
                 } else {
                     return $query

                     ->where(function($query) use($request) {
                         $query->where('recruitment_processes.business_id', NULL)
                         ->where('recruitment_processes.is_default', 1)
                         ->where('recruitment_processes.is_active', 1)
                         ->when(isset($request->is_active), function ($query) use ($request) {
                             if(intval($request->is_active)) {
                                 return $query->whereDoesntHave("disabled", function($q) {
                                     $q->whereIn("disabled_recruitment_processes.created_by", [auth()->user()->id]);
                                 });
                             }

                         })
                         ->orWhere(function ($query) use ($request) {
                             $query->where('recruitment_processes.business_id', NULL)
                                 ->where('recruitment_processes.is_default', 0)
                                 ->where('recruitment_processes.created_by', auth()->user()->id)
                                 ->when(isset($request->is_active), function ($query) use ($request) {
                                     return $query->where('recruitment_processes.is_active', intval($request->is_active));
                                 });
                         });

                     });
                 }
             })
                 ->when(!empty($request->user()->business_id), function ($query) use ($request, $created_by) {
                     return $query
                     ->where(function($query) use($request, $created_by) {


                         $query->where('recruitment_processes.business_id', NULL)
                         ->where('recruitment_processes.is_default', 1)
                         ->where('recruitment_processes.is_active', 1)
                         ->whereDoesntHave("disabled", function($q) use($created_by) {
                             $q->whereIn("disabled_recruitment_processes.created_by", [$created_by]);
                         })
                         ->when(isset($request->is_active), function ($query) use ($request, $created_by)  {
                             if(intval($request->is_active)) {
                                 return $query->whereDoesntHave("disabled", function($q) use($created_by) {
                                     $q->whereIn("disabled_recruitment_processes.business_id",[auth()->user()->business_id]);
                                 });
                             }

                         })


                         ->orWhere(function ($query) use($request, $created_by){
                             $query->where('recruitment_processes.business_id', NULL)
                                 ->where('recruitment_processes.is_default', 0)
                                 ->where('recruitment_processes.created_by', $created_by)
                                 ->where('recruitment_processes.is_active', 1)

                                 ->when(isset($request->is_active), function ($query) use ($request) {
                                     if(intval($request->is_active)) {
                                         return $query->whereDoesntHave("disabled", function($q) {
                                             $q->whereIn("disabled_recruitment_processes.business_id",[auth()->user()->business_id]);
                                         });
                                     }

                                 })


                                 ;
                         })
                         ->orWhere(function ($query) use($request) {
                             $query->where('recruitment_processes.business_id', auth()->user()->business_id)
                                 ->where('recruitment_processes.is_default', 0)
                                 ->when(isset($request->is_active), function ($query) use ($request) {
                                     return $query->where('recruitment_processes.is_active', intval($request->is_active));
                                 });
                         });
                     });


                 })
                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query->where("recruitment_processes.name", "like", "%" . $term . "%")
                             ->orWhere("recruitment_processes.description", "like", "%" . $term . "%");
                     });
                 })
                 //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                 //        return $query->where('product_category_id', $request->product_category_id);
                 //    })


                 ->when(!empty($request->use_in_employee), function ($query) use ($request) {
                     // Convert the request parameter to boolean
                     $useInEmployee = filter_var($request->use_in_employee, FILTER_VALIDATE_BOOLEAN);
                     return $query->where('recruitment_processes.use_in_employee', $useInEmployee);
                 })
                 ->when(!empty($request->use_in_on_boarding), function ($query) use ($request) {
                     // Convert the request parameter to boolean
                     $useInOnBoarding = filter_var($request->use_in_on_boarding, FILTER_VALIDATE_BOOLEAN);
                     return $query->where('recruitment_processes.use_in_on_boarding', $useInOnBoarding);
                 })


                     ->when(!empty($request->start_date), function ($query) use ($request) {
                         return $query->where('recruitment_processes.created_at', ">=", $request->start_date);
                     })
                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('recruitment_processes.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('recruitment_processes.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("recruitment_processes.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("recruitment_processes.id", "DESC");
                 })
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });;



             return response()->json($recruitment_processes, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/client/recruitment-processes",
     *      operationId="getRecruitmentProcessesClient",
     *      tags={"recruitment_processes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
        *              @OA\Parameter(
     *         name="business_id",
     *         in="query",
     *         description="business_id",
     *         required=true,
     *  example="6"
     *      ),
     *              @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),
*      * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *   @OA\Parameter(
     * name="use_in_employee",
     * in="query",
     * description="use_in_employee",
     * required=true,
     * example="1"
     * ),
     *   @OA\Parameter(
     * name="use_in_on_boarding",
     * in="query",
     * description="use_in_on_boarding",
     * required=true,
     * example="1"
     * ),
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
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get recruitment process s  ",
     *      description="This method is to get recruitment process s ",
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

    public function getRecruitmentProcessesClient(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            $business_id =  $request->business_id;
            if(!$business_id) {
               $error = [ "message" => "The given data was invalid.",
               "errors" => ["business_id"=>["The business id field is required."]]
               ];
                   throw new Exception(json_encode($error),422);
            }

            $business = Business::where([
                "id" => $business_id
            ])
            ->first();

            $created_by = $business->created_by;

            $recruitment_processes = RecruitmentProcess::where(function($query) use($request, $created_by, $business) {
                        $query->where('recruitment_processes.business_id', NULL)
                        ->where('recruitment_processes.is_default', 1)
                        ->where('recruitment_processes.is_active', 1)
                        ->whereDoesntHave("disabled", function($q) use($created_by) {
                            $q->whereIn("disabled_recruitment_processes.created_by", [$created_by]);
                        })
                        ->whereDoesntHave("disabled", function($q) use($created_by, $business) {
                                    $q->whereIn("disabled_recruitment_processes.business_id",[$business->id]);
                                })
                        ->orWhere(function ($query) use($request, $created_by, $business){
                            $query->where('recruitment_processes.business_id', NULL)
                                ->where('recruitment_processes.is_default', 0)
                                ->where('recruitment_processes.created_by', $created_by)
                                ->where('recruitment_processes.is_active', 1)

                                ->whereDoesntHave("disabled", function($q) use($created_by, $business) {
                                    $q->whereIn("disabled_recruitment_processes.business_id",[$business->id]);
                                });

                        })
                        ->orWhere(function ($query) use($request, $business) {
                            $query->where('recruitment_processes.business_id', $business->id)
                                ->where('recruitment_processes.is_default', 0)

                                ->where('recruitment_processes.is_active', intval($request->is_active));

                        });
                    })



                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("recruitment_processes.name", "like", "%" . $term . "%")
                            ->orWhere("recruitment_processes.description", "like", "%" . $term . "%");
                    });
                })



                ->when(!empty($request->use_in_employee), function ($query) use ($request) {
                    // Convert the request parameter to boolean
                    $useInEmployee = filter_var($request->use_in_employee, FILTER_VALIDATE_BOOLEAN);
                    return $query->where('recruitment_processes.use_in_employee', $useInEmployee);
                })
                ->when(!empty($request->use_in_on_boarding), function ($query) use ($request) {
                    // Convert the request parameter to boolean
                    $useInOnBoarding = filter_var($request->use_in_on_boarding, FILTER_VALIDATE_BOOLEAN);
                    return $query->where('recruitment_processes.use_in_on_boarding', $useInOnBoarding);
                })


                    ->when(!empty($request->start_date), function ($query) use ($request) {
                        return $query->where('recruitment_processes.created_at', ">=", $request->start_date);
                    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('recruitment_processes.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('recruitment_processes.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("recruitment_processes.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("recruitment_processes.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($recruitment_processes, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/recruitment-processes/{id}",
     *      operationId="getRecruitmentProcessById",
     *      tags={"recruitment_processes"},
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
     *      summary="This method is to get recruitment process  by id",
     *      description="This method is to get recruitment process  by id",
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


    public function getRecruitmentProcessById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('recruitment_process_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $recruitment_process =  RecruitmentProcess::where([
                "recruitment_processes.id" => $id,
            ])

                ->first();

                if (!$recruitment_process) {

                    return response()->json([
                        "message" => "no data found"
                    ], 404);
                }

                if (empty(auth()->user()->business_id)) {

                    if (auth()->user()->hasRole('superadmin')) {
                        if (($recruitment_process->business_id != NULL || $recruitment_process->is_default != 1)) {

                            return response()->json([
                                "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                            ], 403);
                        }
                    } else {
                        if ($recruitment_process->business_id != NULL) {

                            return response()->json([
                                "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                            ], 403);
                        } else if ($recruitment_process->is_default == 0 && $recruitment_process->created_by != auth()->user()->id) {

                                return response()->json([
                                    "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                                ], 403);

                        }
                    }
                } else {
                    if ($recruitment_process->business_id != NULL) {
                        if (($recruitment_process->business_id != auth()->user()->business_id)) {

                            return response()->json([
                                "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                            ], 403);
                        }
                    } else {
                        if ($recruitment_process->is_default == 0) {
                            if ($recruitment_process->created_by != auth()->user()->created_by) {

                                return response()->json([
                                    "message" => "You do not have permission to update this recruitment process  due to role restrictions."
                                ], 403);
                            }
                        }
                    }
                }



            return response()->json($recruitment_process, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/recruitment-processes/{ids}",
     *      operationId="deleteRecruitmentProcessesByIds",
     *      tags={"recruitment_processes"},
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
     *      summary="This method is to delete recruitment process  by id",
     *      description="This method is to delete recruitment process  by id",
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

    public function deleteRecruitmentProcessesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('recruitment_process_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = RecruitmentProcess::whereIn('id', $idsArray)
                ->when(empty($request->user()->business_id), function ($query) use ($request) {
                    if ($request->user()->hasRole("superadmin")) {
                        return $query->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 1);
                    } else {
                        return $query->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 0)
                            ->where('recruitment_processes.created_by', $request->user()->id);
                    }
                })
                ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                    return $query->where('recruitment_processes.business_id', $request->user()->business_id)
                        ->where('recruitment_processes.is_default', 0);
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



            $conflictingUsers = User::whereIn("recruitment_process_id", $existingIds)->get([
                'id', 'first_Name',
                'last_Name',
            ]);

            if ($conflictingUsers->isNotEmpty()) {
                return response()->json([
                    "message" => "Some users are associated with the specified recruitment processes",
                    "conflicting_users" => $conflictingUsers
                ], 409);
            }

            $conflictingUserRecruitmentProcessesExists = UserRecruitmentProcess::whereIn("recruitment_process_id", $existingIds)->exists();

            if ($conflictingUserRecruitmentProcessesExists) {
                return response()->json([
                    "message" => "Some users are associated with the specified recruitment processes",
                    // "conflicting_users" => $conflictingUsers
                ], 409);
            }

            $conflictingCandidateRecruitmentProcessesExists = CandidateRecruitmentProcess::whereIn("recruitment_process_id", $existingIds)->exists();

            if ($conflictingCandidateRecruitmentProcessesExists) {
                return response()->json([
                    "message" => "Some users are associated with the specified recruitment processes",
                    // "conflicting_users" => $conflictingUsers
                ], 409);
            }


            RecruitmentProcess::destroy($existingIds);

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

}

