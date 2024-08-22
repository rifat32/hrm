<?php

namespace App\Http\Controllers;

use App\Http\Requests\CheckDiscountRequest;
use App\Http\Requests\ServicePlanCreateRequest;
use App\Http\Requests\ServicePlanUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\DiscountUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Business;
use App\Models\BusinessModule;
use App\Models\Module;
use App\Models\ServicePlan;
use App\Models\ServicePlanModule;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServicePlanController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, DiscountUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/service-plans",
     *      operationId="createServicePlan",
     *      tags={"service_plans"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store service plan",
     *      description="This method is to store service plan",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * * @OA\Property(property="name", type="string", format="string", example="tttttt"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
 * @OA\Property(property="set_up_amount", type="number", format="number", example="10"),
 * @OA\Property(property="duration_months", type="number", format="number", example="12"),
 *  * @OA\Property(property="number_of_employees_allowed", type="number", format="number", example="12"),
 *
 *  * @OA\Property(property="price", type="number", format="number", example="50"),
 * @OA\Property(property="business_tier_id", type="number", format="number", example="1"),
 *
 * * @OA\Property(property="discount_codes", type="string", format="string", example={
 *{"code" : "ddedddd",
 * "discount_amount" : 50,
 *},
  *{"code" : "ddedddd",
 * "discount_amount" : 50,
 *}
 * }),
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

    public function createServicePlan(ServicePlanCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('business_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $request_data["is_active"] = 1;
                $request_data["created_by"] = $request->user()->id;


                $service_plan =  ServicePlan::create($request_data);

                $service_plan->discount_codes()->createMany($request_data['discount_codes']);



            foreach($request_data["active_module_ids"] as $active_module_id){
                ServicePlanModule::create([
                "is_enabled" => 1,
                "service_plan_id" => $service_plan->id,
                "module_id" => $active_module_id,
                'created_by' => auth()->user()->id
               ]);
            }

            $disabled_modules = Module::where('modules.is_enabled', 1)
            ->whereNotIn("id",$request_data["active_module_ids"])
            ->pluck("id")->toArray();

            foreach($disabled_modules as $inactive_module_id) {
                ServicePlanModule::create([
                    "is_enabled" => 0,
                    "service_plan_id" => $service_plan->id,
                    "module_id" => $inactive_module_id,
                    'created_by' => auth()->user()->id
                   ]);
          }



                return response($service_plan, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/service-plans",
     *      operationId="updateServicePlan",
     *      tags={"service_plans"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update business tier ",
     *      description="This method is to update business tier",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
* @OA\Property(property="name", type="string", format="string", example="tttttt"),
     * @OA\Property(property="description", type="string", format="string", example="erg ear ga&nbsp;"),
* @OA\Property(property="set_up_amount", type="number", format="number", example="10"),
 *  * @OA\Property(property="number_of_employees_allowed", type="number", format="number", example="12"),
 * @OA\Property(property="duration_months", type="number", format="number", example="30"),
 *  *  * @OA\Property(property="price", type="number", format="number", example="50"),
 * @OA\Property(property="business_tier_id", type="number", format="number", example="1"),
 *  * * @OA\Property(property="discount_codes", type="string", format="string", example={
 *{
 *   "id" :1,
   * "code" : "ddedddd",
 * "discount_amount" : 50,
 *},
  *{
     *   "id" :1,
  *  "code" : "ddedddd",
 * "discount_amount" : 50,
 *}
 * }),


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

    public function updateServicePlan(ServicePlanUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('business_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                // $business_id =  auth()->user()->business_id;
                $request_data = $request->validated();





                $service_plan  =  tap(ServicePlan::where([
                    "id" => $request_data["id"],

                ]))->update(
                    collect($request_data)->only([
                        "name",
                        "description",
                        'set_up_amount',
                        "number_of_employees_allowed",
                        'duration_months',
                        "price",
                        'business_tier_id',
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$service_plan) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }


                foreach ($request_data['discount_codes'] as $discountCode) {
                    $service_plan->discount_codes()->updateOrCreate(
                        ['id' => $discountCode['id']],
                        $discountCode
                    );
                }

              // Step 1: Clear existing ServicePlanModule records
ServicePlanModule::where('service_plan_id', $service_plan->id)->delete();

// Step 2: Determine active and disabled module IDs
$active_module_ids = $request_data['active_module_ids'];
$all_module_ids = Module::where('is_enabled', 1)->pluck('id')->toArray();


// Step 3: Prepare ServicePlanModule data for bulk insertion
$service_plan_modules = [];
foreach ($all_module_ids as $module_id) {
    $service_plan_modules[] = [
        'is_enabled' => in_array($module_id, $active_module_ids) ? 1 : 0,
        'service_plan_id' => $service_plan->id,
        'module_id' => $module_id,
        'created_by' => auth()->user()->id,
        'created_at' => now(),
        'updated_at' => now(),
    ];
}

// Bulk insert ServicePlanModule records
ServicePlanModule::insert($service_plan_modules);

// Step 4: Retrieve all businesses associated with the service plan
$service_business_ids = Business::where('service_plan_id', $service_plan->id)->pluck('id')->toArray();

// Step 5: Prepare BusinessModule data for bulk insertion
$business_modules = [];
foreach ($service_business_ids as $business_id) {
    foreach ($all_module_ids as $module_id) {
        $business_modules[] = [
            'is_enabled' => in_array($module_id, $active_module_ids) ? 1 : 0,
            'business_id' => $business_id,
            'module_id' => $module_id,
            'created_by' => auth()->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

// Step 6: Delete existing BusinessModule records for these businesses
BusinessModule::whereIn('business_id', $service_business_ids)->delete();

// Step 7: Bulk insert new BusinessModule records
BusinessModule::insert($business_modules);








                return response($service_plan, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/service-plans",
     *      operationId="getServicePlans",
     *      tags={"service_plans"},
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

     *      summary="This method is to get business tiers  ",
     *      description="This method is to get business tiers ",
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

    public function getServicePlans(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");

            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $service_plans = ServicePlan::with("business_tier")
            ->where('service_plans.created_by', ">=", auth()->user()->id)

            // ->when(!auth()->user()->hasRole("superadmin"), function ($query) use ($request) {
            //     return $query->where('service_plans.created_by', ">=", auth()->user()->id);
            // }
            // )

            ->when(!empty($request->search_key), function ($query) use ($request) {
                return $query->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("service_plans.name", "like", "%" . $term . "%");
                });
            })

            //     when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('service_plans.business_id', NULL)
            //                  ->where('service_plans.is_default', 1);
            // })
            // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('service_plans.business_id', auth()->user()->business_id);
            // })


                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('service_plans.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('service_plans.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("service_plans.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("service_plans.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($service_plans, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
      /**
     *
     * @OA\Get(
     *      path="/v1.0/client/service-plans",
     *      operationId="getServicePlanClient",
     *      tags={"service_plans"},
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

     *      summary="This method is to get business tiers  ",
     *      description="This method is to get business tiers ",
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

     public function getServicePlanClient(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");



             $service_plans = ServicePlan::with("business_tier")
             ->when(!empty($request->search_key), function ($query) use ($request) {
                 return $query->where(function ($query) use ($request) {
                     $term = $request->search_key;
                    //  $query->where("service_plans.name", "like", "%" . $term . "%");
                 });
             })


                 ->when(!empty($request->reseller_id), function ($query) use ($request) {
                    return $query->where('service_plans.created_by', $request->reseller_id);
                })

                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('service_plans.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('service_plans.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("service_plans.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("service_plans.id", "DESC");
                 })
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });;



             return response()->json($service_plans, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/service-plans/{id}",
     *      operationId="getServicePlanById",
     *      tags={"service_plans"},
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
     *      summary="This method is to get business tier by id",
     *      description="This method is to get business tier by id",
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


    public function getServicePlanById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $service_plan =  ServicePlan::with("discount_codes")->where([
                "id" => $id,
            ])
            // ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('service_plans.business_id', NULL)
            //                  ->where('service_plans.is_default', 1);
            // })
            // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('service_plans.business_id', auth()->user()->business_id);
            // })
                ->first();
            if (!$service_plan) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            $modules = Module::where('modules.is_enabled', 1)
            ->orderBy("modules.name", "ASC")

            ->select("id","name")
           ->get()

           ->map(function($item) use($service_plan) {
               $item->is_enabled = 0;

           $servicePlanModule =    ServicePlanModule::where([
               "service_plan_id" => $service_plan->id,
               "module_id" => $item->id
           ])
           ->first();

           if(!empty($servicePlanModule)) {
               if($servicePlanModule->is_enabled) {
                   $item->is_enabled = 1;
               }
           }

               return $item;
           });

           $service_plan->modules = $modules;

            return response()->json($service_plan, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/service-plans/{ids}",
     *      operationId="deleteServicePlansByIds",
     *      tags={"service_plans"},
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
     *      summary="This method is to delete business tier by id",
     *      description="This method is to delete business tier by id",
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

    public function deleteServicePlansByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('business_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = ServicePlan::whereIn('id', $idsArray)
            // ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('service_plans.business_id', NULL)
            //                  ->where('service_plans.is_default', 1);
            // })
            // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('service_plans.business_id', auth()->user()->business_id)
            //     ->where('service_plans.is_default', 0);
            // })
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

            $conflictingBusinesses = Business::

           whereIn("service_plan_id",$nonExistingIds)


            ->get([
                "id"
            ]);

            if ($conflictingBusinesses->isNotEmpty()) {
                return response()->json([
                    "message" => "Some businesses are associated with the specified service plans",
                    "conflicting_users" => $conflictingBusinesses
                ], 409);
            }

            ServicePlan::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


       /**
     *
     * @OA\Post(
     *      path="/v1.0/client/check-discount",
     *      operationId="checkDiscountClient",
     *      tags={"service_plans"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to check discount",
     *      description="This method is to check discount",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * * @OA\Property(property="service_plan_discount_code", type="string", format="string", example="tttttt"),

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

     public function checkDiscountClient(CheckDiscountRequest $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             return DB::transaction(function () use ($request) {


                 $request_data = $request->validated();

                 $response_data['service_plan_discount_amount'] = $this->getDiscountAmount($request_data);


                 return response($response_data, 201);
             });
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }
}
