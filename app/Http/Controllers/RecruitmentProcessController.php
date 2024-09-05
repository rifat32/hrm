<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\RecruitmentProcessCreateRequest;
use App\Http\Requests\RecruitmentProcessPositionMultipleUpdateRequest;
use App\Http\Requests\RecruitmentProcessPositionUpdateRequest;
use App\Http\Requests\RecruitmentProcessUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Business;
use App\Models\CandidateRecruitmentProcess;
use App\Models\DisabledRecruitmentProcess;
use App\Models\RecruitmentProcess;
use App\Models\RecruitmentProcessOrder;
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
     * @OA\Property(property="is_required", type="string", format="string", example=""),
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
                $request_data["business_id"] = auth()->user()->business_id;

                if (empty(auth()->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $recruitment_process =  RecruitmentProcess::create($request_data);

                $order_no_count = RecruitmentProcess::count();

                    $recruitment_process->employee_order_no = $order_no_count;
                    $recruitment_process->candidate_order_no = $order_no_count;

                $recruitment_process->save();




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
     * @OA\Property(property="employee_order_no", type="string", format="string", example="tttttt"),
     * @OA\Property(property="candidate_order_no", type="string", format="string", example="tttttt"),
     *   * @OA\Property(property="is_required", type="string", format="string", example=""),
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

                $recruitment_process = tap(RecruitmentProcess::where($recruitment_process_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                        "use_in_employee",
                        "use_in_on_boarding",
                    ])->toArray()
                )->first();

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
     *      path="/v1.0/recruitment-processes/position",
     *      operationId="updateRecruitmentProcessPosition",
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
     * @OA\Property(property="employee_order_no", type="string", format="string", example="tttttt"),
     * @OA\Property(property="candidate_order_no", type="string", format="string", example="tttttt"),

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

     public function updateRecruitmentProcessPosition(RecruitmentProcessPositionUpdateRequest $request)
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

                 $request_data["business_id"] = auth()->user()->business_id?auth()->user()->business_id:NULL;


                 $no_order_recruitment_processes = RecruitmentProcess::

     where(function($query) {
         $query->where("business_id", auth()->user()->business_id)
               ->orWhereNull("business_id");
     })
     ->whereDoesntHave("orders", function($query) use($request_data) {
         $query->when(!empty(auth()->user()->business_id), function($query) use($request_data) {
             $query->where("recruitment_process_orders.business_id", auth()->user()->business_id);
         }, function($query) {
             $query->whereNull("recruitment_process_orders.business_id");
         });
     })
     ->get();

 foreach ($no_order_recruitment_processes as $no_order_recruitment_process) {
     RecruitmentProcessOrder::create([
         'recruitment_process_id' => $no_order_recruitment_process->id,
         'employee_order_no' => $no_order_recruitment_process->employee_order_no ?? 0, // Adjust as needed
         'candidate_order_no' => $no_order_recruitment_process->candidate_order_no ?? 0, // Adjust as needed
         'business_id' => $request_data["business_id"]
     ]);
 }

 // Continue with the rest of your sorting logic...


                 // Handle sorting logic for employee order number
                 $employee_order_query = [
                     'recruitment_process_id' => $request_data["id"],
                     'business_id' => $request_data["business_id"],
                 ];

                 $recruitment_process_order = RecruitmentProcessOrder::updateOrCreate(
                     $employee_order_query,
                     [
                         'employee_order_no' => $request_data["employee_order_no"],
                         'candidate_order_no' => $request_data["candidate_order_no"],
                     ]
                 );

                 // Check for overlapping employee order numbers and adjust if necessary
                 $employee_order_no_overlapped = RecruitmentProcessOrder::where([
                     'business_id' => $recruitment_process_order->business_id,
                     'employee_order_no' => $recruitment_process_order->employee_order_no,
                 ])
                 ->whereNotIn('id', [$recruitment_process_order->id])
                 ->exists();

                 if ($employee_order_no_overlapped) {
                     RecruitmentProcessOrder::where([
                         'business_id' => $recruitment_process_order->business_id,
                     ])
                     ->where('employee_order_no', '>=', $recruitment_process_order->employee_order_no)
                     ->whereNotIn('id', [$recruitment_process_order->id])
                     ->increment('employee_order_no');
                 }

                 // Check for overlapping candidate order numbers and adjust if necessary
                 $candidate_order_no_overlapped = RecruitmentProcessOrder::where([
                     'business_id' => $recruitment_process_order->business_id,
                     'candidate_order_no' => $recruitment_process_order->candidate_order_no,
                 ])
                 ->whereNotIn('id', [$recruitment_process_order->id])
                 ->exists();

                 if ($candidate_order_no_overlapped) {
                     RecruitmentProcessOrder::where([
                         'business_id' => $recruitment_process_order->business_id,
                     ])
                     ->where('candidate_order_no', '>=', $recruitment_process_order->candidate_order_no)
                     ->whereNotIn('id', [$recruitment_process_order->id])
                     ->increment('candidate_order_no');
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
     *      path="/v1.0/recruitment-processes/position/multiple",
     *      operationId="updateRecruitmentProcessPositionMultiple",
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
 * @OA\Property(
 *     property="recruitment_processes",
 *     type="array",
 *     @OA\Items(
 *         type="object",
 *         @OA\Property(property="id", type="number", format="number", example=1),
 *         @OA\Property(property="employee_order_no", type="string", format="string", example="12345"),
 *         @OA\Property(property="candidate_order_no", type="string", format="string", example="67890")
 *     )
 * )

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

     public function updateRecruitmentProcessPositionMultiple(RecruitmentProcessPositionMultipleUpdateRequest $request)
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

                 $request_data["business_id"] = auth()->user()->business_id?auth()->user()->business_id:NULL;





foreach($request_data["recruitment_processes"] as $recruitment_processes) {
  // Handle sorting logic for employee order number
  $order_query = [
    'recruitment_process_id' => $recruitment_processes["id"],
    'business_id' => $request_data["business_id"],
];

$recruitment_process_order = RecruitmentProcessOrder::updateOrCreate(
    $order_query,
    [
        'employee_order_no' => $recruitment_processes["employee_order_no"],
        'candidate_order_no' => $recruitment_processes["candidate_order_no"],
    ]
);

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

            $this->toggleActivation(
                RecruitmentProcess::class,
                DisabledRecruitmentProcess::class,
                'recruitment_process_id',
                $request_data["id"],
                auth()->user()
            );


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



             $recruitment_processes = RecruitmentProcess::when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
                $query->when(auth()->user()->hasRole('superadmin'), function ($query) use ($request) {
                    $query->forSuperAdmin('recruitment_processes');
                }, function ($query) use ($request, $created_by) {
                    $query->forNonSuperAdmin('recruitment_processes', 'disabled_recruitment_processes', $created_by);
                });
            })
            ->when(!empty(auth()->user()->business_id), function ($query) use ( $created_by) {
                $query->forBusiness('recruitment_processes', "disabled_recruitment_processes", $created_by);
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
                 });



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

                                ->where('recruitment_processes.is_active', request()->boolean("is_active"));

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
                        if (($recruitment_process->business_id != NULL)) {

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
                ->when(empty(auth()->user()->business_id), function ($query) use ($request) {
                    if ($request->user()->hasRole("superadmin")) {
                        return $query->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 1);
                    } else {
                        return $query->where('recruitment_processes.business_id', NULL)
                            ->where('recruitment_processes.is_default', 0)
                            ->where('recruitment_processes.created_by', $request->user()->id);
                    }
                })
                ->when(!empty(auth()->user()->business_id), function ($query) use ($request) {
                    return $query->where('recruitment_processes.business_id', auth()->user()->business_id)
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



            $conflictingUsersExists = User::whereIn("recruitment_process_id", $existingIds)->exists();
            if ($conflictingUsersExists) {
                return response()->json([
                    "message" => config('messages.delete_restricted'),
                ], 409);
            }

            $conflictingUserRecruitmentProcessesExists = UserRecruitmentProcess::whereIn("recruitment_process_id", $existingIds)->exists();

            if ($conflictingUserRecruitmentProcessesExists) {
                return response()->json([
                    "message" => config('messages.delete_restricted'),
                ], 409);
            }

            $conflictingCandidateRecruitmentProcessesExists = CandidateRecruitmentProcess::whereIn("recruitment_process_id", $existingIds)->exists();

            if ($conflictingCandidateRecruitmentProcessesExists) {
                return response()->json([
                    "message" => config('messages.delete_restricted'),
                ], 409);
            }


            RecruitmentProcess::destroy($existingIds);

            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }

}

