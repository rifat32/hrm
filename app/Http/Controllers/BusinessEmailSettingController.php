<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateEmailSettingRequest;
use App\Http\Requests\UpdateSystemSettingRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\BusinessEmailSetting;
use Exception;
use Illuminate\Http\Request;

class BusinessEmailSettingController extends Controller
{
    use ErrorUtil,UserActivityUtil;
 /**
     *
     * @OA\Put(
     *      path="/v1.0/email-settings",
     *      operationId="updateEmailSetting",
     *      tags={"email_setting"},
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
 *   @OA\Property(property="mail_driver", type="string", example="smtp"),
 *   @OA\Property(property="mail_host", type="string", example="mail.example.com"),
 *   @OA\Property(property="mail_port", type="integer", example=465),
 *   @OA\Property(property="mail_username", type="string", example="_mainaccount@example.com"),
 *   @OA\Property(property="mail_password", type="string", example="?x(mujD}h}ZV"),
 *   @OA\Property(property="mail_encryption", type="string", example="ssl"),
 *   @OA\Property(property="mail_from_address", type="string", format="email", example="_mainaccount@example.com"),
 *   @OA\Property(property="mail_from_name", type="string", example="Your App Name"),
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

     public function updateEmailSetting(UpdateEmailSettingRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('email_setting_update')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $request_data = $request->validated();

             $emailSettings = BusinessEmailSetting::updateOrCreate(
                ['business_id' => auth()->user()->business_id],
                $request_data
            );

            return response()->json($emailSettings,200);


         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }

 /**
     *
     * @OA\Get(
     *      path="/v1.0/email-settings",
     *      operationId="getEmailSetting",
     *      tags={"email_setting"},
     *       security={
     *           {"bearerAuth": {}}
     *       },



     *      summary="This method is to get email setting",

     *      description="This method is to get  setting",
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

     public function getEmailSetting(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('email_setting_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             $emailSettings = BusinessEmailSetting::where('business_id', auth()->user()->business_id)->first();

             return response()->json($emailSettings,200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

}
