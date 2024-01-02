<?php

namespace App\Http\Controllers;

use App\Http\Requests\SettingLeaveCreateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\SettingLeave;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingLeaveController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/setting-leave",
     *      operationId="createSettingLeave",
     *      tags={"settings.setting_leave"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store setting leave",
     *      description="This method is to store setting leave",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="start_month", type="number", example="1"),
     *     @OA\Property(property="approval_level", type="string", example="single"),
     *     @OA\Property(property="allow_bypass", type="boolean", format="boolean", example="1"),
     *     @OA\Property(property="special_users", type="string", format="array", example={1,2,3}),
     *     @OA\Property(property="special_roles", type="string", format="array", example={1,2,3}),
     **    @OA\Property(property="paid_leave_employment_statuses", type="string", format="array", example={1,2,3}),
     *     @OA\Property(property="unpaid_leave_employment_statuses", type="string", format="array", example={1,2,3})
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

    public function createSettingLeave(SettingLeaveCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('setting_leave_create')) {
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




                if (empty($request->user()->business_id)) {

               $check_data =     [
                        "business_id" => $request_data["business_id"],
                        "is_default" => $request_data["is_default"]
               ];
               if (!$request->user()->hasRole('superadmin')) {
        $check_data["created_by"] =    $request_data["created_by"];
            }


                    $setting_leave  =  SettingLeave::updateOrCreate(
                        $check_data
                        ,

                        $request_data

                    );
                } else {

                    $setting_leave =     SettingLeave::updateOrCreate(
                        [
                            "business_id" => $request_data["business_id"],
                            "is_default" => $request_data["is_default"]
                        ],
                        $request_data
                    );


                }

                $setting_leave->special_users()->sync($request_data['special_users'],[]);
                $setting_leave->special_roles()->sync($request_data['special_roles'],[]);
                $setting_leave->paid_leave_employment_statuses()->sync($request_data['paid_leave_employment_statuses'],[]);
                $setting_leave->unpaid_leave_employment_statuses()->sync($request_data['unpaid_leave_employment_statuses'],[]);


                return response($setting_leave, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/setting-leave",
     *      operationId="getSettingLeave",
     *      tags={"settings.setting_leave"},
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

     *      summary="This method is to get setting leave  ",
     *      description="This method is to get setting leave ",
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

    public function getSettingLeave(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('setting_leave_create')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }




            $setting_leave = SettingLeave::with("special_users","special_roles","paid_leave_employment_statuses","unpaid_leave_employment_statuses")

            ->when(empty($request->user()->business_id), function ($query) use ($request) {
                if (auth()->user()->hasRole('superadmin')) {
                    return $query->where('setting_leaves.business_id', NULL)
                        ->where('setting_leaves.is_default', 1)
                        ->when(isset($request->is_active), function ($query) use ($request) {
                            return $query->where('setting_leaves.is_active', intval($request->is_active));
                        });
                } else {
                    return   $query->where('setting_leaves.business_id', NULL)
                    ->where('setting_leaves.is_default', 0)
                    ->where('setting_leaves.created_by', auth()->user()->id);
                }
            })
                ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                 return   $query->where('setting_leaves.business_id', auth()->user()->business_id)
                    ->where('setting_leaves.is_default', 0);


                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("setting_leaves.name", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('setting_leaves.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('setting_leaves.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("setting_leaves.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("setting_leaves.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($setting_leave, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }




















}
