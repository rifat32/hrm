<?php

namespace App\Http\Controllers;

use App\Http\Requests\GetIdRequest;
use App\Http\Requests\PayrunCreateRequest;
use App\Http\Requests\PayrunUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\Payrun;
use App\Models\PayrunDepartment;
use App\Models\PayrunUser;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrunController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;

    /**
     *
     * @OA\Post(
     *      path="/v1.0/payruns",
     *      operationId="createPayrun",
     *      tags={"administrator.payruns"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store payrun",
     *      description="This method is to store payrun",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *
     *
     * @OA\Property(property="period_type", type="string", format="string", example="weekly"),
     * @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     * @OA\Property(property="end_date", type="string", format="date", example="2024-01-31"),
     * @OA\Property(property="consider_type", type="string", format="string", example="hour"),
     * @OA\Property(property="consider_overtime", type="boolean", format="boolean", example=true),
     * @OA\Property(property="notes", type="string", format="string", example="Some notes"),
     *      *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3}),
     *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
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

    public function createPayrun(PayrunCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('payrun_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $payrun =  Payrun::create($request_data);

                $request_data['departments'] = Department::where([
                    "business_id" => auth()->user()->business_id
                ])
                ->pluck("id");




                $payrun->departments()->sync($request_data['departments'], []);
                // $payrun->departments()->sync($request_data['departments'], []);
                // $payrun->users()->sync($request_data['users'], []);

                return response($payrun, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/payruns",
     *      operationId="updatePayrun",
     *      tags={"administrator.payruns"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update payrun ",
     *      description="This method is to update payrun",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
   * @OA\Property(property="period_type", type="string", format="string", example="weekly"),
     * @OA\Property(property="start_date", type="string", format="date", example="2024-01-01"),
     * @OA\Property(property="end_date", type="string", format="date", example="2024-01-31"),
     * @OA\Property(property="consider_type", type="string", format="string", example="hour"),
     * @OA\Property(property="consider_overtime", type="boolean", format="boolean", example=true),
     * @OA\Property(property="notes", type="string", format="string", example="Some notes"),
     * @OA\Property(property="departments", type="string",  format="array", example={1,2,3}),
     * @OA\Property(property="users", type="string", format="array", example={1,2,3}),
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

    public function updatePayrun(PayrunUpdateRequest $request)
    {


        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('payrun_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();

                $payrun_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];

                $payrun  =  tap(Payrun::where($payrun_query_params))->update(
                    collect($request_data)->only([
                        "period_type",
                        "start_date",
                        "end_date",
                        "generating_type",
                        "consider_type",
                        "consider_overtime",
                        "notes",

                        "consider_overtime",
                        "notes",
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$payrun) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }


                // $payrun->departments()->sync($request_data['departments'], []);
                // $payrun->users()->sync($request_data['users'], []);





                return response($payrun, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

  /**
     *
     * @OA\Put(
     *      path="/v1.0/payruns/toggle-active",
     *      operationId="toggleActivePayrun",
     *      tags={"user_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle payrun activity",
     *      description="This method is to toggle payrun activity",
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


     public function toggleActivePayrun(GetIdRequest $request)
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
             $manager_departments = Department::where("manager_id", $request->user()->id)->get();
             foreach ($manager_departments as $manager_department) {
                 $all_manager_department_ids[] = $manager_department->id;
                 $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
             }

            $payrun = Payrun::where([
                "id" => $request_data["id"],
                "business_id" => auth()->user()->business_id
            ])
                ->first();
            if (!$payrun) {
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "no payrun found"
                ], 404);
            }


             $payrun_department_exists = PayrunDepartment::where([
                "payrun_id" => $payrun->id
            ])
            ->whereIn("department_id",$all_manager_department_ids)
            ->exists();


            $payrun_user_exists = PayrunUser::where([
                "payrun_id" => $payrun->id
            ])
            ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                 $query->whereIn("departments.id",$all_manager_department_ids);
            })
            ->exists();




            if((!$payrun_department_exists) && !$payrun_user_exists){
                $this->storeError(
                    "You don't have access to this payrun",
                    403,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "You don't have access to this payrun"
                ], 403);
            }


             $payrun->update([
                 'is_active' => !$payrun->is_active
             ]);

             return response()->json(['message' => 'payrun status updated successfully'], 200);
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/payruns",
     *      operationId="getPayruns",
     *      tags={"administrator.payruns"},
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

     *      summary="This method is to get payruns  ",
     *      description="This method is to get payruns ",
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

    public function getPayruns(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('payrun_view')) {
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

            $payruns = Payrun::withCount("payrolls")
            ->where(
                [
                    "business_id" => $business_id
                ]
            )
            ->where(function($query) use($all_manager_department_ids) {
                $query->whereHas("departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                 ->orWhereHas("users.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 });
            })



                // ->when(!empty($request->search_key), function ($query) use ($request) {
                //     return $query->where(function ($query) use ($request) {
                //         $term = $request->search_key;
                //         $query->where("name", "like", "%" . $term . "%")
                //             ->orWhere("description", "like", "%" . $term . "%");
                //     });
                // })
                ->when(isset($request->is_active), function ($query) use ($request) {
                    return $query->where('payruns.is_active', intval($request->is_active));
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('payruns.start_date', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('payruns.end_date', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("payruns.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("payruns.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });



            return response()->json($payruns, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/payruns/{id}",
     *      operationId="getPayrunById",
     *      tags={"administrator.payruns"},
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
     *      summary="This method is to get payrun by id",
     *      description="This method is to get payrun by id",
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


    public function getPayrunById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('payrun_view')) {
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

           $payrun = Payrun::with("users","departments")
           ->where([
               "id" => $id,
               "business_id" => auth()->user()->business_id
           ])
               ->first();
           if (!$payrun) {
            $this->storeError(
                "no data found"
                ,
                404,
                "front end error",
                "front end error"
               );
               return response()->json([
                   "message" => "no payrun found"
               ], 404);
           }


           $payrun_department_exists = PayrunDepartment::where([
            "payrun_id" => $payrun->id
        ])
        ->whereIn("department_id",$all_manager_department_ids)
        ->exists();


        $payrun_user_exists = PayrunUser::where([
            "payrun_id" => $payrun->id
        ])
        ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
             $query->whereIn("departments.id",$all_manager_department_ids);
        })
        ->exists();




        if((!$payrun_department_exists) && !$payrun_user_exists){
            $this->storeError(
                "You don't have access to this payrun",
                403,
                "front end error",
                "front end error"
               );
            return response()->json([
                "message" => "You don't have access to this payrun"
            ], 403);
        }




            return response()->json($payrun, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/payruns/{ids}",
     *      operationId="deletePayrunsByIds",
     *      tags={"administrator.payruns"},
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
     *      summary="This method is to delete payrun by id",
     *      description="This method is to delete payrun by id",
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

    public function deletePayrunsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('payrun_delete')) {
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
            $existingIds = Payrun::where([
                "business_id" => $business_id
            ])
            ->where(function($query) use($all_manager_department_ids) {
                $query->whereHas("departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                 ->orWhereHas("users.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 });
            })

                ->whereIn('id', $idsArray)
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
                    "message" => "Some or all of the specified data do not exist. or something else"
                ], 404);
            }

            Payrun::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}

