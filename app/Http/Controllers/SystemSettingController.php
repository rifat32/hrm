<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSystemSettingRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\SystemSetting;
use Exception;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    use ErrorUtil,UserActivityUtil;
      /**
     *
     * @OA\Put(
     *      path="/v1.0/system-settings",
     *      operationId="updateSystemSetting",
     *      tags={"system_setting"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle module active",
     *      description="This method is to toggle module active",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="self_registration_enabled", type="string", format="number",example="1"),
     * *           @OA\Property(property="STRIPE_KEY", type="string", format="string",example="STRIPE_KEY"),
     * *           @OA\Property(property="STRIPE_SECRET", type="string", format="string",example="STRIPE_SECRET"),
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

     public function updateSystemSetting(UpdateSystemSettingRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('system_setting_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();


            $systemSetting = SystemSetting::first();

            if (!$systemSetting) {
                return response()->json([
                    "message" => "no system setting found"
                ], 404);
            }


                $systemSetting->fill(collect($request_data)->only([
                    'self_registration_enabled',
                    'STRIPE_KEY',
                    "STRIPE_SECRET"
                ])->toArray());
                $systemSetting->save();

                $systemSettingArray = $systemSetting->toArray();

                $systemSettingArray["STRIPE_KEY"] = $systemSetting->STRIPE_KEY;
                $systemSettingArray["STRIPE_SECRET"] = $systemSetting->STRIPE_SECRET;

             return response()->json($systemSettingArray, 200);
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }

 /**
     *
     * @OA\Get(
     *      path="/v1.0/system-settings",
     *      operationId="getSystemSetting",
     *      tags={"system_setting"},
     *       security={
     *           {"bearerAuth": {}}
     *       },



     *      summary="This method is to get system_setting",
     *      description="This method is to get system_setting",
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

     public function getSystemSetting(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('system_setting_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             $systemSetting = SystemSetting::first();

             $systemSettingArray = $systemSetting->toArray();

             $systemSettingArray["STRIPE_KEY"] = $systemSetting->STRIPE_KEY;
             $systemSettingArray["STRIPE_SECRET"] = $systemSetting->STRIPE_SECRET;


             return response()->json($systemSettingArray, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

 /**
     *
     * @OA\Get(
     *      path="/v1.0/client/system-settings",
     *      operationId="getSystemSettingSettingClient",
     *      tags={"system_setting"},
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
     *    * *  @OA\Parameter(
     * name="business_tier_id",
     * in="query",
     * description="business_tier_id",
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

     *      summary="This method is to get system_setting",
     *      description="This method is to get system_setting",
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

     public function getSystemSettingSettingClient(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");

             $systemSetting = SystemSetting::first();

             return response()->json($systemSetting, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }








}
