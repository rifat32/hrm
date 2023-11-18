<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingAttendanceCreateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\SettingAttendance;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingAttendanceController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-attendance",
     *      operationId="createSettingAttendance",
     *      tags={"settings.setting_attendance"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting attendance",
     *      description="This method is to store setting attendance",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 *     @OA\Property(property="punch_in_time_tolerance", type="number", format="number", example="15"),
 *     @OA\Property(property="work_availability_definition", type="number", format="number", example="80"),
 *     @OA\Property(property="punch_in_out_alert", type="boolean", format="boolean", example="1"),
 *     @OA\Property(property="punch_in_out_interval", type="number", format="number", example="30"),
 *     @OA\Property(property="alert_area", type="string", format="array", example={"web","system"}),
 *     @OA\Property(property="auto_approval", type="boolean", format="boolean", example="1"),
*     @OA\Property(property="special_users", type="string", format="array", example={1,2,3}),
 *     @OA\Property(property="special_roles", type="string", format="array", example={1,2,3})
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

    public function createSettingAttendance(SettingAttendanceCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('setting_attendance_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_active"] = 1;

                $check_user = $this->checkUsers($request_data["special_users"]);
                if (!$check_user["ok"]) {
                    return response()->json([
                        "message" => $check_user["message"]
                    ], $check_user["status"]);
                }
                $check_role = $this->checkRoles($request_data["special_roles"]);
                if (!$check_role["ok"]) {
                    return response()->json([
                        "message" => $check_role["message"]
                    ], $check_role["status"]);
                }
                if ($request->user()->hasRole('superadmin')) {

                $request_data["business_id"] = NULL;
                $request_data["is_default"] = 1;

                $setting_attendance  =  SettingAttendance::updateOrCreate([

                    "business_id" => $request_data["business_id"],

                    "is_default" => $request_data["is_default"]

                ],

             $request_data



            );



                } else {
                   


                    $request_data["business_id"] = $request->user()->business_id;
                    $request_data["is_default"] = 0;
                    $setting_attendance =     SettingAttendance::updateOrCreate([
                        "business_id" => $request_data["business_id"],
                        "is_default" => $request_data["is_default"]
                    ],
                    $request_data
                );
                }



                return response($setting_attendance, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



      /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-attendance",
     *      operationId="getSettingAttendance",
     *      tags={"settings.setting_attendance"},
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

     *      summary="This method is to get setting attendance  ",
     *      description="This method is to get setting attendance ",
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

     public function getSettingAttendance(Request $request)
     {
         try {
             $this->storeActivity($request, "");
             if (!$request->user()->hasPermissionTo('setting_attendance_create')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }


             $setting_attendance = SettingAttendance::when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
                 return $query->where('setting_attendances.business_id', NULL)
                              ->where('setting_attendances.is_default', 1);
             })
             ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                 return $query->where('setting_attendances.business_id', $request->user()->business_id)
                 ->where('setting_attendances.is_default', 0);
             })
                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                        //  $query->where("setting_attendances.name", "like", "%" . $term . "%");
                     });
                 })
                 //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                 //        return $query->where('product_category_id', $request->product_category_id);
                 //    })
                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('setting_attendances.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('setting_attendances.created_at', "<=", $request->end_date);
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("setting_attendances.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("setting_attendances.id", "DESC");
                 })
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });;



             return response()->json($setting_attendance, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }
}
