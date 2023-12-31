<?php

namespace App\Http\Controllers;

use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\EmployeeAddressHistory;
use App\Models\EmployeePassportDetailHistory;
use App\Models\EmployeeSponsorship;
use App\Models\EmployeeSponsorshipHistory;
use App\Models\EmployeeVisaDetail;
use App\Models\EmployeeVisaDetailHistory;
use Exception;
use Illuminate\Http\Request;

class HistoryDetailsController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
   /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-passport-details",
     *      operationId="getUserPassportDetailsHistory",
     *      tags={"histories"},
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
     *   * *  @OA\Parameter(
     * name="employee_id",
     * in="query",
     * description="employee_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee passport history",
     *      description="This method is to get passport history",
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

     public function getUserPassportDetailsHistory(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             if (!$request->user()->hasPermissionTo('user_view')) {
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

             $employee_passport_details_history = EmployeePassportDetailHistory::when(!empty($request->employee_id), function ($query) use ($request) {
                return $query->where('employee_passport_detail_histories.employee_id', $request->employee_id);
            })
            ->when(empty($request->employee_id), function ($query) use ($request) {
                return $query->where('employee_passport_detail_histories.employee_id', $request->user()->id);
            })
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query->where("employee_passport_detail_histories.passport_number", "like", "%" . $term . "%")
                             ->orWhere("employee_passport_detail_histories.place_of_issue", "like", "%" . $term . "%");
                     });
                 })

                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('employee_passport_detail_histories.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('employee_passport_detail_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("employee_passport_detail_histories.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("employee_passport_detail_histories.id", "DESC");
                 })
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });;



             return response()->json($employee_passport_details_history, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

     /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-visa-details",
     *      operationId="getUserVisaDetailsHistory",
     *      tags={"histories"},
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
     *   * *  @OA\Parameter(
     * name="employee_id",
     * in="query",
     * description="employee_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee visa history",
     *      description="This method is to get visa history",
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

     public function getUserVisaDetailsHistory(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             if (!$request->user()->hasPermissionTo('user_view')) {
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


             $employee_visa_details_history = EmployeeVisaDetailHistory::when(!empty($request->employee_id), function ($query) use ($request) {
                return $query->where('employee_visa_detail_histories.employee_id', $request->employee_id);
            })
            ->when(empty($request->employee_id), function ($query) use ($request) {
                return $query->where('employee_visa_detail_histories.employee_id', $request->user()->id);
            })
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })

                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query->where("employee_visa_detail_histories.BRP_number", "like", "%" . $term . "%")
                             ->orWhere("employee_visa_detail_histories.place_of_issue", "like", "%" . $term . "%");
                     });
                 })

                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('employee_visa_detail_histories.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('employee_visa_detail_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("employee_visa_detail_histories.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("employee_visa_detail_histories.id", "DESC");
                 })
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });;



             return response()->json($employee_visa_details_history, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }
       /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-sponsorship-details",
     *      operationId="getUserSponsorshipDetailsHistory",
     *      tags={"histories"},
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
     *   * *  @OA\Parameter(
     * name="employee_id",
     * in="query",
     * description="employee_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee sponsorship history",
     *      description="This method is to get sponsorship history",
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

     public function getUserSponsorshipDetailsHistory(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             if (!$request->user()->hasPermissionTo('user_view')) {
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
             $employee_sponsorship_details_history = EmployeeSponsorshipHistory::when(!empty($request->employee_id), function ($query) use ($request) {
                return $query->where('employee_sponsorship_histories.employee_id', $request->employee_id);
            })
            ->when(empty($request->employee_id), function ($query) use ($request) {
                return $query->where('employee_sponsorship_histories.employee_id', $request->user()->id);
            })
                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query->where("employee_sponsorship_histories.certificate_number", "like", "%" . $term . "%");
                     });
                 })
                 ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('employee_sponsorship_histories.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('employee_sponsorship_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("employee_sponsorship_histories.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("employee_sponsorship_histories.id", "DESC");
                 })
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });;



             return response()->json($employee_sponsorship_details_history, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

       /**
     *
     * @OA\Get(
     *      path="/v1.0/histories/user-address-details",
     *      operationId="getUserAddressDetailsHistory",
     *      tags={"histories"},
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
     *   * *  @OA\Parameter(
     * name="employee_id",
     * in="query",
     * description="employee_id",
     * required=true,
     * example="1"
     * ),
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get employee address history",
     *      description="This method is to get address history",
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

     public function getUserAddressDetailsHistory(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");
             if (!$request->user()->hasPermissionTo('user_view')) {
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
             $employee_address_details_history = EmployeeAddressHistory::when(!empty($request->employee_id), function ($query) use ($request) {
                return $query->where('employee_address_histories.employee_id', $request->employee_id);
            })
            ->when(empty($request->employee_id), function ($query) use ($request) {
                return $query->where('employee_address_histories.employee_id', $request->user()->id);
            })
            ->whereHas("employee.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query->where("employee_address_histories.address_line_1", "like", "%" . $term . "%");
                         $query->orWhere("employee_address_histories.address_line_2", "like", "%" . $term . "%");
                         $query->orWhere("employee_address_histories.country", "like", "%" . $term . "%");
                         $query->orWhere("employee_address_histories.city", "like", "%" . $term . "%");
                         $query->orWhere("employee_address_histories.postcode", "like", "%" . $term . "%");
                     });
                 })

                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('employee_address_histories.created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('employee_address_histories.created_at', "<=", ($request->end_date . ' 23:59:59'));
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("employee_address_histories.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("employee_address_histories.id", "DESC");
                 })
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });;



             return response()->json($employee_address_details_history, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }
}
