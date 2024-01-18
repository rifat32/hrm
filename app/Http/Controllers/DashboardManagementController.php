<?php

namespace App\Http\Controllers;

use App\Http\Utils\ErrorUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Business;
use App\Models\Candidate;
use App\Models\Department;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardManagementController extends Controller
{
    use ErrorUtil, BusinessUtil,UserActivityUtil;

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-owner-dashboard/jobs-in-area/{business_id}",
     *      operationId="getBusinessOwnerDashboardDataJobList",
     *      tags={"dashboard_management.business_owner"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="business_id",
     *         in="path",
     *         description="business_id",
     *         required=true,
     *  example="1"
     *      ),
     *      *      * *  @OA\Parameter(
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
     *      summary="This should return list of jobs posted by drivers within same city and which are still not finalised and this business owner have not applied yet.",
     *      description="This should return list of jobs posted by drivers within same city and which are still not finalised and this business owner have not applied yet.",
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

    public function getBusinessOwnerDashboardDataJobList($business_id, Request $request)
    {
        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            $business = Business::where([
                "id" => $business_id,
                "owner_id" => $request->user()->id
            ])
                ->first();
            if (!$business) {
                return response()->json([
                    "message" => "you are not the owner of the business or the request business does not exits"
                ], 404);
            }

            $prebookingQuery = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
            ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
                ->where([
                    "users.city" => $business->city
                ])
                ->whereNotIn('job_bids.business_id', [$business->id])
                ->where('pre_bookings.status', "pending");


            if (!empty($request->start_date)) {
                $prebookingQuery = $prebookingQuery->where('pre_bookings.created_at', ">=", $request->start_date);
            }
            if (!empty($request->end_date)) {
                $prebookingQuery = $prebookingQuery->where('pre_bookings.created_at', "<=", ($request->end_date . ' 23:59:59'));
            }
            $data = $prebookingQuery->groupBy("pre_bookings.id")
                ->select(
                    "pre_bookings.*",
                    DB::raw('(SELECT COUNT(job_bids.id) FROM job_bids WHERE job_bids.pre_booking_id = pre_bookings.id) AS job_bids_count'),

                    DB::raw('(SELECT COUNT(job_bids.id) FROM job_bids
        WHERE
        job_bids.pre_booking_id = pre_bookings.id
        AND
        job_bids.business_id = ' . $business->id . '

        ) AS business_applied')

                )
                ->havingRaw('(SELECT COUNT(job_bids.id) FROM job_bids WHERE job_bids.pre_booking_id = pre_bookings.id)  < 4')

                ->get();
            return response()->json($data, 200);
        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }

    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-owner-dashboard/jobs-application/{business_id}",
     *      operationId="getBusinessOwnerDashboardDataJobApplications",
     *      tags={"dashboard_management.business_owner"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="business_id",
     *         in="path",
     *         description="business_id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="Total number of Jobs in the area and out of which total number of jobs this business owner have applied",
     *      description="Total number of Jobs in the area and out of which total number of jobs this business owner have applied",
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

    public function getBusinessOwnerDashboardDataJobApplications($business_id, Request $request)
    {
        try{
        $this->storeActivity($request, "DUMMY activity","DUMMY description");
        $business = Business::where([
            "id" => $business_id,
            "owner_id" => $request->user()->id
        ])
            ->first();
        if (!$business) {
            return response()->json([
                "message" => "you are not the owner of the business or the request business does not exits"
            ], 404);
        }

        $data["total_jobs"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->where([
            "users.city" => $business->city
        ])
            //  ->whereNotIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->groupBy("pre_bookings.id")


            ->count();

        $data["weekly_jobs"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->where([
            "users.city" => $business->city
        ])
            //  ->whereNotIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->groupBy("pre_bookings.id")
            ->count();
        $data["monthly_jobs"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->where([
            "users.city" => $business->city
        ])
            //  ->whereNotIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy("pre_bookings.id")
            ->count();




        $data["applied_total_jobs"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
            ->where([
                "users.city" => $business->city
            ])
            ->whereIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->groupBy("pre_bookings.id")

            ->count();
        $data["applied_weekly_jobs"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
            ->where([
                "users.city" => $business->city
            ])
            ->whereIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
            ->groupBy("pre_bookings.id")

            ->count();
        $data["applied_monthly_jobs"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
            ->where([
                "users.city" => $business->city
            ])
            ->whereIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
            ->groupBy("pre_bookings.id")

            ->count();

        return response()->json($data, 200);
    }catch(Exception $e) {
  return $this->sendError($e, 500,$request);
    }

    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-owner-dashboard/winned-jobs-application/{business_id}",
     *      operationId="getBusinessOwnerDashboardDataWinnedJobApplications",
     *      tags={"dashboard_management.business_owner"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="business_id",
     *         in="path",
     *         description="business_id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="Total Job Won( Total job User have selcted this business )",
     *      description="Total Job Won( Total job User have selcted this business )",
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

    public function getBusinessOwnerDashboardDataWinnedJobApplications($business_id, Request $request)
    {
        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            $business = Business::where([
                "id" => $business_id,
                "owner_id" => $request->user()->id
            ])
                ->first();
            if (!$business) {
                return response()->json([
                    "message" => "you are not the owner of the business or the request business does not exits"
                ], 404);
            }

            $data["total"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
                ->where([
                    "bookings.business_id" => $business->id
                ])

                ->where('pre_bookings.status', "booked")
                ->groupBy("pre_bookings.id")
                ->count();

            $data["weekly"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
                ->where([
                    "bookings.business_id" => $business->id
                ])
                ->where('pre_bookings.status', "booked")
                ->whereBetween('pre_bookings.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->groupBy("pre_bookings.id")
                ->count();

            $data["monthly"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
                ->where([
                    "bookings.business_id" => $business->id
                ])

                ->where('pre_bookings.status', "booked")
                ->whereBetween('pre_bookings.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->groupBy("pre_bookings.id")
                ->count();


            return response()->json($data, 200);
        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }

    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-owner-dashboard/completed-bookings/{business_id}",
     *      operationId="getBusinessOwnerDashboardDataCompletedBookings",
     *      tags={"dashboard_management.business_owner"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="business_id",
     *         in="path",
     *         description="business_id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="Total completed Bookings Total Bookings completed by this business owner",
     *      description="Total completed Bookings Total Bookings completed by this business owner",
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

    public function getBusinessOwnerDashboardDataCompletedBookings($business_id, Request $request)
    {
        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            $business = Business::where([
                "id" => $business_id,
                "owner_id" => $request->user()->id
            ])
                ->first();
            if (!$business) {
                return response()->json([
                    "message" => "you are not the owner of the business or the request business does not exits"
                ], 404);
            }

            $data["total"] = Candidate::where([
                "bookings.status" => "converted_to_job",
                "bookings.business_id" => $business->id

            ])
                ->count();
            $data["weekly"] = Candidate::where([
                "bookings.status" => "converted_to_job",
                "bookings.business_id" => $business->id

            ])
                ->whereBetween('bookings.created_at', [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()])
                ->count();
            $data["monthly"] = Candidate::where([
                "bookings.status" => "converted_to_job",
                "bookings.business_id" => $business->id

            ])
                ->whereBetween('bookings.created_at', [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()])
                ->count();




            return response()->json($data, 200);
        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }

    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-owner-dashboard/upcoming-jobs/{business_id}/{duration}",
     *      operationId="getBusinessOwnerDashboardDataUpcomingJobs",
     *      tags={"dashboard_management.business_owner"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="business_id",
     *         in="path",
     *         description="business_id",
     *         required=true,
     *  example="1"
     *      ),
     *   *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="duration",
     *         required=true,
     *  example="7"
     *      ),
     *      summary="Total completed Bookings Total Bookings completed by this business owner",
     *      description="Total completed Bookings Total Bookings completed by this business owner",
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

    public function getBusinessOwnerDashboardDataUpcomingJobs($business_id, $duration, Request $request)
    {
        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            $business = Business::where([
                "id" => $business_id,
                "owner_id" => $request->user()->id
            ])
                ->first();
            if (!$business) {
                return response()->json([
                    "message" => "you are not the owner of the business or the request business does not exits"
                ], 404);
            }
            $startDate = now();
            $endDate = $startDate->copy()->addDays($duration);


            $data = Candidate::where([
                "jobs.status" => "pending",
                "jobs.business_id" => $business->id

            ])
                ->whereBetween('jobs.job_start_date', [$startDate, $endDate])




                ->count();



            return response()->json($data, 200);
        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }

    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-owner-dashboard/expiring-affiliations/{business_id}/{duration}",
     *      operationId="getBusinessOwnerDashboardDataExpiringAffiliations",
     *      tags={"dashboard_management.business_owner"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="business_id",
     *         in="path",
     *         description="business_id",
     *         required=true,
     *  example="1"
     *      ),
     *   *              @OA\Parameter(
     *         name="duration",
     *         in="path",
     *         description="duration",
     *         required=true,
     *  example="7"
     *      ),
     *      summary="Total completed Bookings Total Bookings completed by this business owner",
     *      description="Total completed Bookings Total Bookings completed by this business owner",
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

    public function getBusinessOwnerDashboardDataExpiringAffiliations($business_id, $duration, Request $request)
    {
        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            $business = Business::where([
                "id" => $business_id,
                "owner_id" => $request->user()->id
            ])
                ->first();
            if (!$business) {
                return response()->json([
                    "message" => "you are not the owner of the business or the request business does not exits"
                ], 404);
            }
            $startDate = now();
            $endDate = $startDate->copy()->addDays($duration);


            $data = Candidate::with("affiliation")
                ->where('business_affiliations.end_date', "<",  $endDate)
                ->count();



            return response()->json($data, 200);
        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }

    }




    public function applied_jobs($business)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);

        $data["total_count"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
            ->where([
                "users.city" => $business->city
            ])
            ->whereIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->groupBy("pre_bookings.id")
            ->count();





        $data["this_week_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
            ->where([
                "users.city" => $business->city
            ])
            ->whereIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")

            ->whereBetween('pre_bookings.created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->groupBy("pre_bookings.id")
            ->select("job_bids.id","job_bids.created_at","job_bids.updated_at")
            ->get();

        $data["previous_week_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
            ->where([
                "users.city" => $business->city
            ])
            ->whereIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")

            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->groupBy("pre_bookings.id")
            ->select("job_bids.id","job_bids.created_at","job_bids.updated_at")
            ->get();



        $data["this_month_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
            ->where([
                "users.city" => $business->city
            ])
            ->whereIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->groupBy("pre_bookings.id")
            ->select("job_bids.id","job_bids.created_at","job_bids.updated_at")
            ->get();

        $data["previous_month_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->leftJoin('job_bids', 'pre_bookings.id', '=', 'job_bids.pre_booking_id')
            ->where([
                "users.city" => $business->city
            ])
            ->whereIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->groupBy("pre_bookings.id")
            ->select("job_bids.id","job_bids.created_at","job_bids.updated_at")
            ->get();

            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();

        return $data;
    }
    public function pre_bookings($business)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);

        $data["total_count"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')

        ->where([
            "users.city" => $business->city
        ])
            //  ->whereNotIn('job_bids.business_id', [$business->id])
            ->where('pre_bookings.status', "pending")
            ->count();



        $data["this_week_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')

        ->where([
            "users.city" => $business->city
        ])

            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->select("pre_bookings.id","pre_bookings.created_at","pre_bookings.updated_at")
            ->get();

        $data["previous_week_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->where([
            "users.city" => $business->city
        ])

            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("pre_bookings.id","pre_bookings.created_at","pre_bookings.updated_at")
            ->get();



        $data["this_month_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->where([
            "users.city" => $business->city
        ])

            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("pre_bookings.id","pre_bookings.created_at","pre_bookings.updated_at")
            ->get();

        $data["previous_month_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
        ->where([
            "users.city" => $business->city
        ])

            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("pre_bookings.id","pre_bookings.created_at","pre_bookings.updated_at")
            ->get();


            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();

        return $data;
    }

    public function winned_jobs($business)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);
        $data["total_data_count"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
            ->where([
                "bookings.business_id" => $business->id
            ])

            ->where('pre_bookings.status', "booked")
            ->groupBy("pre_bookings.id")
            ->count();







        $data["this_week_data"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
            ->where([
                "bookings.business_id" => $business->id
            ])
            ->where('pre_bookings.status', "booked")
            ->whereBetween('pre_bookings.created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->groupBy("pre_bookings.id")
            ->select("bookings.id","bookings.created_at","bookings.updated_at")
            ->get();
        $data["previous_week_data"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
            ->where([
                "bookings.business_id" => $business->id
            ])
            ->where('pre_bookings.status', "booked")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->groupBy("pre_bookings.id")
            ->select("bookings.id","bookings.created_at","bookings.updated_at")
            ->get();



        $data["this_month_data"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
            ->where([
                "bookings.business_id" => $business->id
            ])
            ->where('pre_bookings.status', "booked")
            ->whereBetween('pre_bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->groupBy("pre_bookings.id")
            ->select("bookings.id","bookings.created_at","bookings.updated_at")
            ->get();

        $data["previous_month_data"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
            ->where([
                "bookings.business_id" => $business->id
            ])
            ->where('pre_bookings.status', "booked")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->groupBy("pre_bookings.id")
            ->select("bookings.id","bookings.created_at","bookings.updated_at")
            ->get();

            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();

        return $data;
    }


    public function completed_bookings($business)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);

        $data["total_data_count"] = Candidate::where([
            "bookings.status" => "converted_to_job",
            "bookings.business_id" => $business->id

        ])
            ->count();






        $data["this_week_data"] = Candidate::where([
            "bookings.status" => "converted_to_job",
            "bookings.business_id" => $business->id

        ])
            ->whereBetween('bookings.created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->select("bookings.id","bookings.created_at","bookings.updated_at")
            ->get();
        $data["previous_week_data"] = Candidate::where([
            "bookings.status" => "converted_to_job",
            "bookings.business_id" => $business->id

        ])
            ->whereBetween('bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("bookings.id","bookings.created_at","bookings.updated_at")
            ->get();



        $data["this_month_data"] = Candidate::where([
            "bookings.status" => "converted_to_job",
            "bookings.business_id" => $business->id

        ])
            ->whereBetween('bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("bookings.id","bookings.created_at","bookings.updated_at")
            ->get();
        $data["previous_month_data"] = Candidate::where([
            "bookings.status" => "converted_to_job",
            "bookings.business_id" => $business->id

        ])
            ->whereBetween('bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("bookings.id","bookings.created_at","bookings.updated_at")
            ->get();

            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();
        return $data;
    }

    public function upcoming_jobs($business)
    {
        $startDate = now();

        // $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfNextMonth = Carbon::now()->startOfMonth()->addMonth(1);
        $endDateOfNextMonth = Carbon::now()->endOfMonth()->addMonth(1);

        // $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfNextWeek = Carbon::now()->startOfWeek()->addWeek(1);
        $endDateOfNextWeek = Carbon::now()->endOfWeek()->addWeek(1);



        // $weeklyEndDate = $startDate->copy()->addDays(7);
        // $secondWeeklyStartDate = $startDate->copy()->addDays(8);
        // $secondWeeklyEndDate = $startDate->copy()->addDays(14);
        // $monthlyEndDate = $startDate->copy()->addDays(30);
        // $secondMonthlyStartDate = $startDate->copy()->addDays(31);
        // $secondMonthlyStartDate = $startDate->copy()->addDays(60);






        $data["total_data_count"] = Candidate::where([
            "jobs.status" => "pending",
            "jobs.business_id" => $business->id

        ])
            ->count();


        $data["this_week_data"] = Candidate::where([
            "jobs.status" => "pending",
            "jobs.business_id" => $business->id

        ])->whereBetween('jobs.job_start_date', [$startDate, $endDateOfThisWeek])
        ->select("jobs.id","jobs.created_at","jobs.updated_at")
            ->get();
        $data["next_week_data"] = Candidate::where([
            "jobs.status" => "pending",
            "jobs.business_id" => $business->id

        ])->whereBetween('jobs.job_start_date', [$startDateOfNextWeek, $endDateOfNextWeek])
        ->select("jobs.id","jobs.created_at","jobs.updated_at")
            ->get();

        $data["this_month_data"] = Candidate::where([
            "jobs.status" => "pending",
            "jobs.business_id" => $business->id

        ])->whereBetween('jobs.job_start_date', [$startDate, $endDateOfThisMonth])
        ->select("jobs.id","jobs.created_at","jobs.updated_at")
            ->get();
        $data["next_month_data"] = Candidate::where([
            "jobs.status" => "pending",
            "jobs.business_id" => $business->id

        ])->whereBetween('jobs.job_start_date', [$startDateOfNextMonth, $endDateOfNextMonth])
        ->select("jobs.id","jobs.created_at","jobs.updated_at")
            ->get();


            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["next_week_data_count"] = $data["next_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["next_month_data_count"] = $data["next_month_data"]->count();

        return $data;
    }
    public function affiliation_expirings($business)
    {
        $startDate = now();

        // $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfNextMonth = Carbon::now()->startOfMonth()->addMonth(1);
        $endDateOfNextMonth = Carbon::now()->endOfMonth()->addMonth(1);

        // $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfNextWeek = Carbon::now()->startOfWeek()->addWeek(1);
        $endDateOfNextWeek = Carbon::now()->endOfWeek()->addWeek(1);


        $data["total_data_count"] = Candidate::where([
            "business_affiliations.business_id"=>$business->id
        ])
        ->count();


        $data["this_week_data"] = Candidate::where([
            "business_affiliations.business_id"=>$business->id
        ])
        ->whereBetween('business_affiliations.end_date', [$startDate, $endDateOfThisWeek])

        ->select("business_affiliations.id","business_affiliations.created_at","business_affiliations.updated_at")
            ->get();
        $data["next_week_data"] = Candidate::where([
            "business_affiliations.business_id"=>$business->id
        ])
        ->whereBetween('business_affiliations.end_date', [$startDateOfNextWeek, $endDateOfNextWeek])

        ->select("business_affiliations.id","business_affiliations.created_at","business_affiliations.updated_at")
            ->get();

        $data["this_month_data"] = Candidate::where([
            "business_affiliations.business_id"=>$business->id
        ])
        ->whereBetween('business_affiliations.end_date', [$startDate, $endDateOfThisMonth])
        ->select("business_affiliations.id","business_affiliations.created_at","business_affiliations.updated_at")
            ->get();

        $data["next_month_data"] = Candidate::where([
            "business_affiliations.business_id"=>$business->id
        ])
        ->whereBetween('business_affiliations.end_date', [$startDateOfNextMonth, $endDateOfNextMonth])
        ->select("business_affiliations.id","business_affiliations.created_at","business_affiliations.updated_at")
            ->get();

            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["next_week_data_count"] = $data["next_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["next_month_data_count"] = $data["next_month_data"]->count();


        return $data;
    }

    public function employees(
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    )
    {



        $data_query  = User::whereHas("departments", function($query) use($all_manager_department_ids) {
           $query->whereIn("departments.id",$all_manager_department_ids);
        })
            ->whereNotIn('id', [auth()->user()->id])
            ->where('is_in_employee', 1)
            ->where('is_active', 1);

        $data["total_data_count"] = $data_query->count();
        $data["this_week_data_count"] = $data_query->whereBetween('created_at', [$start_date_of_this_week, $end_date_of_this_week])->count();
        $data["previous_week_data_count"] = $data_query->whereBetween('created_at', [$start_date_of_previous_week, $end_date_of_previous_week])->count();
        $data["this_month_data_count"] = $data_query->whereBetween('created_at', [$start_date_of_this_month, $end_date_of_this_month])->count();
        $data["previous_month_data_count"] = $data_query->whereBetween('created_at', [$start_date_of_previous_month, $end_date_of_previous_month])->count();
        return $data;
    }
    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-owner-dashboard",
     *      operationId="getBusinessUserDashboardData",
     *      tags={"dashboard_management.business_user"},
     *       security={
     *           {"bearerAuth": {}}
     *       },


     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
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

    public function getBusinessUserDashboardData(Request $request)
    {

        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");

            $business_id = auth()->user()->business_id;
            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business owner"
                ], 401);
            }
            $start_date_of_this_month = Carbon::now()->startOfMonth();
            $end_date_of_this_month = Carbon::now()->endOfMonth();
            $start_date_of_previous_month = Carbon::now()->startOfMonth()->subMonth(1);
            $end_date_of_previous_month = Carbon::now()->endOfMonth()->subMonth(1);

            $start_date_of_this_week = Carbon::now()->startOfWeek();
            $end_date_of_this_week = Carbon::now()->endOfWeek();
            $start_date_of_previous_week = Carbon::now()->startOfWeek()->subWeek(1);
            $end_date_of_previous_week = Carbon::now()->endOfWeek()->subWeek(1);

            $business = Business::where([
                "id" => $business_id,
                "owner_id" => $request->user()->id
            ])
                ->first();

            if (!$business) {
                return response()->json([
                    "message" => "you are not the owner of the business or the request business does not exits"
                ], 404);
            }
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $data["employees"] = $this->employees(
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids
        );


            // affiliation expiry
            // $data["employees"] = $this->affiliation_expirings($business);

            //    end affiliation expiry
            //   upcoming_jobs
            $data["upcoming_jobs"] = $this->upcoming_jobs($business);

            //  end  upcoming_jobs

            // completed bookings
            $data["completed_bookings"] = $this->completed_bookings($business);
            // end completed bookings

            // winned jobs
            $data["winned_jobs"] = $this->winned_jobs($business);
            // end winned jobs

            //   jobs
            $data["pre_bookings"] = $this->pre_bookings($business);
            // end jobs


            // applied jobs
            $data["applied_jobs"] = $this->applied_jobs($business);
            // end applied jobs


            return response()->json($data, 200);
        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }

    }
    public function businesses($created_by_filter=0)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);



        $total_data_count_query = new Candidate();
        if($created_by_filter) {
            $total_data_count_query =  $total_data_count_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }

        $data["total_data_count"] = $total_data_count_query->count();



        $this_week_data_query = Business::whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek]);

        if($created_by_filter) {
            $this_week_data_query =  $this_week_data_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }
        $data["this_week_data"] = $this_week_data_query->select("id","created_at","updated_at")->get();




        $previous_week_data_query = Business::whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
        ;

        if($created_by_filter) {
            $previous_week_data_query =  $previous_week_data_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }

        $data["previous_week_data"] = $total_data_count_query->select("id","created_at","updated_at")->get();




        $this_month_data_query =Business::whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth]);

        if($created_by_filter) {
            $this_month_data_query =  $this_month_data_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }
        $data["this_month_data"] = $this_month_data_query->select("id","created_at","updated_at")->get();




        $previous_month_data_query =Business::whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth]);

        if($created_by_filter) {
            $previous_month_data_query =  $previous_month_data_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }
        $data["previous_month_data"] = $previous_month_data_query->select("id","created_at","updated_at")->get();



            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();
        return $data;
    }
    public function fuel_stations($created_by_filter = 0)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);


        $total_data_count_query = new Candidate();
        if($created_by_filter) {
            $total_data_count_query =  $total_data_count_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }
        $data["total_data_count"] = $total_data_count_query->count();


        $this_week_data_query = Candidate::whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek]);
        if($created_by_filter) {
            $this_week_data_query =  $this_week_data_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }
        $data["this_week_data"] = $this_week_data_query->select("id","created_at","updated_at")
        ->get();


        $previous_week_data_query = Candidate::whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek]);
        if($created_by_filter) {
            $previous_week_data_query =  $previous_week_data_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }
        $data["previous_week_data"] = $previous_week_data_query->select("id","created_at","updated_at")
        ->get();


        $this_month_data_query =  Candidate::whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth]);
        if($created_by_filter) {
            $this_month_data_query =  $this_month_data_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }
        $data["this_month_data"] = $this_month_data_query->select("id","created_at","updated_at")
        ->get();

        $previous_month_data_query =  Candidate::whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth]);
        if($created_by_filter) {
            $previous_month_data_query =  $previous_month_data_query->where([
                "created_by" =>auth()->user()->id
            ]);
        }
        $data["previous_month_data"] = $previous_month_data_query->select("id","created_at","updated_at")
        ->get();




            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();
        return $data;
    }

    public function customers()
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);



        $data["total_data_count"] = User::with("roles")->whereHas("roles", function($q) {
            $q->whereIn("name", ["customer"]);
        })->count();


        $data["this_week_data"] = User::with("roles")->whereHas("roles", function($q) {
            $q->whereIn("name", ["customer"]);
        })->whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->select("id","created_at","updated_at")
            ->get();

        $data["previous_week_data"] = User::with("roles")->whereHas("roles", function($q) {
            $q->whereIn("name", ["customer"]);
        })->whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("id","created_at","updated_at")
            ->get();



        $data["this_month_data"] = User::with("roles")->whereHas("roles", function($q) {
            $q->whereIn("name", ["customer"]);
        })->whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("id","created_at","updated_at")
            ->get();
        $data["previous_month_data"] = User::with("roles")->whereHas("roles", function($q) {
            $q->whereIn("name", ["customer"]);
        })->whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("id","created_at","updated_at")
            ->get();

            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();
        return $data;
    }
    public function overall_customer_jobs()
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);



        $data["total_data_count"] = Candidate::count();


        $data["this_week_data"] = Candidate::whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->select("id","created_at","updated_at")
            ->get();

        $data["previous_week_data"] = Candidate::whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("id","created_at","updated_at")
            ->get();



        $data["this_month_data"] = Candidate::whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("id","created_at","updated_at")
            ->get();

        $data["previous_month_data"] = Candidate::whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("id","created_at","updated_at")
            ->get();

            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();
        return $data;
    }

    public function overall_bookings($created_by_filter = 0)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);


        $total_data_count_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id');
        if($created_by_filter) {
            $total_data_count_query =  $total_data_count_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["total_data_count"] = $total_data_count_query->count();



        $this_week_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id')
        ->whereBetween('bookings.created_at', [$startDateOfThisWeek, $endDateOfThisWeek]);
        if($created_by_filter) {
            $this_week_data_query =  $this_week_data_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["this_week_data"] = $this_week_data_query->select("bookings.id","bookings.created_at","bookings.updated_at")
        ->get();




        $previous_week_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id')
        ->whereBetween('bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek]);
        if($created_by_filter) {
            $previous_week_data_query =  $previous_week_data_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["previous_week_data"] = $previous_week_data_query->select("bookings.id","bookings.created_at","bookings.updated_at")
        ->get();






        $this_month_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id')
        ->whereBetween('bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth]);
        if($created_by_filter) {
            $this_month_data_query =  $this_month_data_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["this_month_data"] = $this_month_data_query->select("bookings.id","bookings.created_at","bookings.updated_at")
        ->get();


        $previous_month_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id')
        ->whereBetween('bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth]);
        if($created_by_filter) {
            $previous_month_data_query =  $previous_month_data_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["previous_month_data"] = $previous_month_data_query->select("bookings.id","bookings.created_at","bookings.updated_at")
        ->get();


            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();
        return $data;
    }

    public function overall_jobs($created_by_filter = 0)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);


        $total_data_count_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id');
        if($created_by_filter) {
            $total_data_count_query =  $total_data_count_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["total_data_count"] = $total_data_count_query->count();





        $this_week_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id')
        ->whereBetween('jobs.created_at', [$startDateOfThisWeek, $endDateOfThisWeek]);
        if($created_by_filter) {
            $this_week_data_query =  $this_week_data_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["this_week_data"] = $this_week_data_query
        ->select("jobs.id","jobs.created_at","jobs.updated_at")
        ->get();




        $previous_week_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id')
        ->whereBetween('jobs.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek]);
        if($created_by_filter) {
            $previous_week_data_query =  $previous_week_data_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["previous_week_data"] = $previous_week_data_query
        ->select("jobs.id","jobs.created_at","jobs.updated_at")
        ->get();





        $this_month_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id')
        ->whereBetween('jobs.created_at', [$startDateOfThisMonth, $endDateOfThisMonth]);
        if($created_by_filter) {
            $this_month_data_query =  $this_month_data_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["this_month_data"] = $this_month_data_query
        ->select("jobs.id","jobs.created_at","jobs.updated_at")
        ->get();



        $previous_month_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id')
        ->whereBetween('jobs.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth]);
        if($created_by_filter) {
            $previous_month_data_query =  $previous_month_data_query->where([
                "businesses.created_by" =>auth()->user()->id
            ]);
        }
        $data["previous_month_data"] = $previous_month_data_query
        ->select("jobs.id","jobs.created_at","jobs.updated_at")
        ->get();



            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();
        return $data;
    }



    public function overall_services()
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->endOfMonth()->subMonth(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);



        $data["total_data_count"] = Candidate::count();


        $data["this_week_data"] = Candidate::whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->select("id","created_at","updated_at")
            ->get();

        $data["previous_week_data"] = Candidate::whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("id","created_at","updated_at")
            ->get();



        $data["this_month_data"] = Candidate::whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("id","created_at","updated_at")
            ->get();
        $data["previous_month_data"] = Candidate::whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("id","created_at","updated_at")
            ->get();

            $data["this_week_data_count"] = $data["this_week_data"]->count();
            $data["previous_week_data_count"] = $data["previous_week_data"]->count();
            $data["this_month_data_count"] = $data["this_month_data"]->count();
            $data["previous_month_data_count"] = $data["previous_month_data"]->count();
        return $data;
    }
      /**
     *
     * @OA\Get(
     *      path="/v1.0/superadmin-dashboard",
     *      operationId="getSuperAdminDashboardData",
     *      tags={"dashboard_management.superadmin"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
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

    public function getSuperAdminDashboardData( Request $request)
    {
        try{
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasRole('superadmin')) {
                return response()->json([
                    "message" => "You are not a superadmin"
                ], 401);
            }

            $data["businesses"] = $this->businesses();

            $data["fuel_stations"] = $this->fuel_stations();

            $data["customers"] = $this->customers();

            $data["overall_customer_jobs"] = $this->overall_customer_jobs();

            $data["overall_bookings"] = $this->overall_bookings();

            $data["overall_jobs"] = $this->overall_jobs();



            $data["overall_services"] = $this->overall_services();






            return response()->json($data, 200);
        }catch(Exception $e) {
      return $this->sendError($e, 500,$request);
        }

    }

        /**
     *
     * @OA\Get(
     *      path="/v1.0/data-collector-dashboard",
     *      operationId="getDataCollectorDashboardData",
     *      tags={"dashboard_management.data_collector"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="get all dashboard data combined",
     *      description="get all dashboard data combined",
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

     public function getDataCollectorDashboardData( Request $request)
     {
         try{
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasRole('data_collector')) {
                 return response()->json([
                     "message" => "You are not a superadmin"
                 ], 401);
             }

             $data["businesses"] = $this->businesses(1);

             $data["fuel_stations"] = $this->fuel_stations(1);

             $data["overall_bookings"] = $this->overall_bookings(1);

             $data["overall_jobs"] = $this->overall_jobs(1);

            //  $data["customers"] = $this->customers();

            //  $data["overall_customer_jobs"] = $this->overall_customer_jobs();



            //  $data["overall_services"] = $this->overall_services();






             return response()->json($data, 200);
         }catch(Exception $e) {
       return $this->sendError($e, 500,$request);
         }

     }



}
