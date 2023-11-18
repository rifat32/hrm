<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingPayrunCreateRequest;
use App\Models\SettingPayrun;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingPayrollController extends Controller
{
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-payrun",
     *      operationId="createSettingPayrun",
     *      tags={"settings.setting_payroll.payrun"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting payrun",
     *      description="This method is to store setting payrun",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *     @OA\Property(property="payrun_period", type="string", example="monthly"),
 *     @OA\Property(property="consider_type", type="string", example="hour"),
 *     @OA\Property(property="consider_overtime", type="boolean", example=true)
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

     public function createSettingPayrun(SettingPayrunCreateRequest $request)
     {
         try {
             $this->storeActivity($request, "");
             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('setting_payroll_create')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }

                 $request_data = $request->validated();
                 $request_data["created_by"] = $request->user()->id;
                 $request_data["is_active"] = 1;


                 if ($request->user()->hasRole('superadmin')) {

                 $request_data["business_id"] = NULL;
                 $request_data["is_default"] = 1;

                 $setting_payrun  =  SettingPayrun::updateOrCreate([

                     "business_id" => $request_data["business_id"],

                     "is_default" => $request_data["is_default"]

                 ],

              $request_data



             );



                 } else {



                     $request_data["business_id"] = $request->user()->business_id;
                     $request_data["is_default"] = 0;
                     $setting_payrun =     SettingPayrun::updateOrCreate([
                         "business_id" => $request_data["business_id"],
                         "is_default" => $request_data["is_default"]
                     ],
                     $request_data
                 );
                 }



                 return response($setting_payrun, 201);
             });
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }



       /**
      *
      * @OA\Get(
      *      path="/v1.0/setting-payrun",
      *      operationId="getSettingPayrun",
      *      tags={"settings.setting_payroll.payrun"},
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

      *      summary="This method is to get setting payrun  ",
      *      description="This method is to get setting payrun ",
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

      public function getSettingPayrun(Request $request)
      {
          try {
              $this->storeActivity($request, "");
              if (!$request->user()->hasPermissionTo('setting_payroll_create')) {
                  return response()->json([
                      "message" => "You can not perform this action"
                  ], 401);
              }


              $setting_payrun = SettingPayrun::when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
                  return $query->where('setting_payruns.business_id', NULL)
                               ->where('setting_payruns.is_default', 1);
              })
              ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                  return $query->where('setting_payruns.business_id', $request->user()->business_id)
                  ->where('setting_payruns.is_default', 0);
              })
                  ->when(!empty($request->search_key), function ($query) use ($request) {
                      return $query->where(function ($query) use ($request) {
                          $term = $request->search_key;
                         //  $query->where("setting_payruns.name", "like", "%" . $term . "%");
                      });
                  })
                  //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                  //        return $query->where('product_category_id', $request->product_category_id);
                  //    })
                  ->when(!empty($request->start_date), function ($query) use ($request) {
                      return $query->where('setting_payruns.created_at', ">=", $request->start_date);
                  })
                  ->when(!empty($request->end_date), function ($query) use ($request) {
                      return $query->where('setting_payruns.created_at', "<=", $request->end_date);
                  })
                  ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                      return $query->orderBy("setting_payruns.id", $request->order_by);
                  }, function ($query) {
                      return $query->orderBy("setting_payruns.id", "DESC");
                  })
                  ->when(!empty($request->per_page), function ($query) use ($request) {
                      return $query->paginate($request->per_page);
                  }, function ($query) {
                      return $query->get();
                  });;



              return response()->json($setting_payrun, 200);
          } catch (Exception $e) {

              return $this->sendError($e, 500, $request);
          }
      }

      
}
