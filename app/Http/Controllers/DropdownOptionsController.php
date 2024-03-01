<?php

namespace App\Http\Controllers;

use App\Models\Designation;
use App\Models\EmploymentStatus;
use App\Models\WorkLocation;
use Exception;
use Illuminate\Http\Request;

class DropdownOptionsController extends Controller
{


 private function get_work_locations($business_created_by) {
   $work_locations = WorkLocation::where(function($query) use($business_created_by) {
    $query->where('work_locations.business_id', NULL)
    ->where('work_locations.is_default', 1)
    ->where('work_locations.is_active', 1)
    ->whereDoesntHave("disabled", function($q) use($business_created_by) {
        $q->whereIn("disabled_work_locations.created_by", [$business_created_by]);
    })
    ->whereDoesntHave("disabled", function($q) use($business_created_by) {
        $q->whereIn("disabled_work_locations.business_id",[auth()->user()->business_id]);
    })



    ->orWhere(function ($query) use( $business_created_by){
        $query->where('work_locations.business_id', NULL)
            ->where('work_locations.is_default', 0)
            ->where('work_locations.created_by', $business_created_by)
            ->where('work_locations.is_active', 1)
            ->whereDoesntHave("disabled", function($q) {
                $q->whereIn("disabled_work_locations.business_id",[auth()->user()->business_id]);
            });
    })
    ->orWhere(function ($query)  {
        $query->where('work_locations.business_id', auth()->user()->business_id)
        ->where('work_locations.is_active', 1);

    });
})
        ->get();

        return $work_locations;
 }

 private function get_designations($business_created_by) {
    $designations = Designation::where(function($query) use($business_created_by) {
     $query->where('designations.business_id', NULL)
     ->where('designations.is_default', 1)
     ->where('designations.is_active', 1)
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_designations.created_by", [$business_created_by]);
     })
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_designations.business_id",[auth()->user()->business_id]);
     })



     ->orWhere(function ($query) use( $business_created_by){
         $query->where('designations.business_id', NULL)
             ->where('designations.is_default', 0)
             ->where('designations.created_by', $business_created_by)
             ->where('designations.is_active', 1)
             ->whereDoesntHave("disabled", function($q) {
                 $q->whereIn("disabled_designations.business_id",[auth()->user()->business_id]);
             });
     })
     ->orWhere(function ($query)  {
         $query->where('designations.business_id', auth()->user()->business_id)
         ->where('designations.is_active', 1);

     });
 })
         ->get();

         return $designations;
  }

  private function get_employment_statuses($business_created_by) {
    $employment_statuses = EmploymentStatus::where(function($query) use($business_created_by) {
     $query->where('employment_statuses.business_id', NULL)
     ->where('employment_statuses.is_default', 1)
     ->where('employment_statuses.is_active', 1)
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_employment_statuses.created_by", [$business_created_by]);
     })
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
     })



     ->orWhere(function ($query) use( $business_created_by){
         $query->where('employment_statuses.business_id', NULL)
             ->where('employment_statuses.is_default', 0)
             ->where('employment_statuses.created_by', $business_created_by)
             ->where('employment_statuses.is_active', 1)
             ->whereDoesntHave("disabled", function($q) {
                 $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
             });
     })
     ->orWhere(function ($query)  {
         $query->where('employment_statuses.business_id', auth()->user()->business_id)
         ->where('employment_statuses.is_active', 1);

     });
 })
         ->get();

         return $employment_statuses;
  }


  private function get_work_shifts($business_created_by) {
    $employment_statuses = EmploymentStatus::where(function($query) use($business_created_by) {
     $query->where('employment_statuses.business_id', NULL)
     ->where('employment_statuses.is_default', 1)
     ->where('employment_statuses.is_active', 1)
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_employment_statuses.created_by", [$business_created_by]);
     })
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
     })



     ->orWhere(function ($query) use( $business_created_by){
         $query->where('employment_statuses.business_id', NULL)
             ->where('employment_statuses.is_default', 0)
             ->where('employment_statuses.created_by', $business_created_by)
             ->where('employment_statuses.is_active', 1)
             ->whereDoesntHave("disabled", function($q) {
                 $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
             });
     })
     ->orWhere(function ($query)  {
         $query->where('employment_statuses.business_id', auth()->user()->business_id)
         ->where('employment_statuses.is_active', 1);

     });
 })
         ->get();

         return $employment_statuses;
  }
  private function get_roles($business_created_by) {
    $employment_statuses = EmploymentStatus::where(function($query) use($business_created_by) {
     $query->where('employment_statuses.business_id', NULL)
     ->where('employment_statuses.is_default', 1)
     ->where('employment_statuses.is_active', 1)
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_employment_statuses.created_by", [$business_created_by]);
     })
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
     })



     ->orWhere(function ($query) use( $business_created_by){
         $query->where('employment_statuses.business_id', NULL)
             ->where('employment_statuses.is_default', 0)
             ->where('employment_statuses.created_by', $business_created_by)
             ->where('employment_statuses.is_active', 1)
             ->whereDoesntHave("disabled", function($q) {
                 $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
             });
     })
     ->orWhere(function ($query)  {
         $query->where('employment_statuses.business_id', auth()->user()->business_id)
         ->where('employment_statuses.is_active', 1);

     });
 })
         ->get();

         return $employment_statuses;
  }

  private function get_departments($business_created_by) {
    $employment_statuses = EmploymentStatus::where(function($query) use($business_created_by) {
     $query->where('employment_statuses.business_id', NULL)
     ->where('employment_statuses.is_default', 1)
     ->where('employment_statuses.is_active', 1)
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_employment_statuses.created_by", [$business_created_by]);
     })
     ->whereDoesntHave("disabled", function($q) use($business_created_by) {
         $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
     })



     ->orWhere(function ($query) use( $business_created_by){
         $query->where('employment_statuses.business_id', NULL)
             ->where('employment_statuses.is_default', 0)
             ->where('employment_statuses.created_by', $business_created_by)
             ->where('employment_statuses.is_active', 1)
             ->whereDoesntHave("disabled", function($q) {
                 $q->whereIn("disabled_employment_statuses.business_id",[auth()->user()->business_id]);
             });
     })
     ->orWhere(function ($query)  {
         $query->where('employment_statuses.business_id', auth()->user()->business_id)
         ->where('employment_statuses.is_active', 1);

     });
 })
         ->get();

         return $employment_statuses;
  }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/dropdown-options/employee-form",
     *      operationId="getEmployeeFormDropdownData",
     *      tags={"dropdowns"},
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

     *      summary="This method is to get reminders  ",
     *      description="This method is to get reminders ",
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

     public function getEmployeeFormDropdownData(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('user_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $user =  auth()->user();
             $business = $user->business;
             $business_id =  $business->id;
             $business_created_by = $business->created_by;

             $data["work_locations"] = $this->get_work_locations($business_created_by);
             $data["designations"] = $this->get_designations($business_created_by);
             $data["employment_statuses"] = $this->get_employment_statuses($business_created_by);
             $data["work_shifts"] = $this->get_work_shifts($business_created_by);
             $data["roles"] = $this->get_roles($business_created_by);
             $data["departments"] = $this->get_departments($business_created_by);



             return response()->json($data, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }


}
