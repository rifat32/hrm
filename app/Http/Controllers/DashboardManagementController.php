<?php

namespace App\Http\Controllers;

use App\Http\Requests\WidgetCreateRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Business;
use App\Models\Candidate;
use App\Models\DashboardWidget;
use App\Models\Department;
use App\Models\EmployeePassportDetail;
use App\Models\EmployeeRightToWork;
use App\Models\EmployeeSponsorship;
use App\Models\EmployeePension;
use App\Models\EmployeePensionHistory;
use App\Models\EmployeeVisaDetail;
use App\Models\JobListing;
use App\Models\LeaveRecord;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardManagementController extends Controller
{
    use ErrorUtil, BusinessUtil, UserActivityUtil, BasicUtil;

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
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
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
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
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
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
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
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
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
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
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
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
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
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
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
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
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
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
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
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
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
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
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
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }




    public function applied_jobs($business)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

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
            ->select("job_bids.id", "job_bids.created_at", "job_bids.updated_at")
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
            ->select("job_bids.id", "job_bids.created_at", "job_bids.updated_at")
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
            ->select("job_bids.id", "job_bids.created_at", "job_bids.updated_at")
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
            ->select("job_bids.id", "job_bids.created_at", "job_bids.updated_at")
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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

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
            ->select("pre_bookings.id", "pre_bookings.created_at", "pre_bookings.updated_at")
            ->get();

        $data["previous_week_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
            ->where([
                "users.city" => $business->city
            ])

            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("pre_bookings.id", "pre_bookings.created_at", "pre_bookings.updated_at")
            ->get();



        $data["this_month_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
            ->where([
                "users.city" => $business->city
            ])

            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("pre_bookings.id", "pre_bookings.created_at", "pre_bookings.updated_at")
            ->get();

        $data["previous_month_data"] = Candidate::leftJoin('users', 'pre_bookings.customer_id', '=', 'users.id')
            ->where([
                "users.city" => $business->city
            ])

            ->where('pre_bookings.status', "pending")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("pre_bookings.id", "pre_bookings.created_at", "pre_bookings.updated_at")
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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

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
            ->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();
        $data["previous_week_data"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
            ->where([
                "bookings.business_id" => $business->id
            ])
            ->where('pre_bookings.status', "booked")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->groupBy("pre_bookings.id")
            ->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();



        $data["this_month_data"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
            ->where([
                "bookings.business_id" => $business->id
            ])
            ->where('pre_bookings.status', "booked")
            ->whereBetween('pre_bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->groupBy("pre_bookings.id")
            ->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();

        $data["previous_month_data"] = Candidate::leftJoin('bookings', 'pre_bookings.id', '=', 'bookings.pre_booking_id')
            ->where([
                "bookings.business_id" => $business->id
            ])
            ->where('pre_bookings.status', "booked")
            ->whereBetween('pre_bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->groupBy("pre_bookings.id")
            ->select("bookings.id", "bookings.created_at", "bookings.updated_at")
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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

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
            ->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();
        $data["previous_week_data"] = Candidate::where([
            "bookings.status" => "converted_to_job",
            "bookings.business_id" => $business->id

        ])
            ->whereBetween('bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();



        $data["this_month_data"] = Candidate::where([
            "bookings.status" => "converted_to_job",
            "bookings.business_id" => $business->id

        ])
            ->whereBetween('bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();
        $data["previous_month_data"] = Candidate::where([
            "bookings.status" => "converted_to_job",
            "bookings.business_id" => $business->id

        ])
            ->whereBetween('bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("bookings.id", "bookings.created_at", "bookings.updated_at")
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
            ->select("jobs.id", "jobs.created_at", "jobs.updated_at")
            ->get();
        $data["next_week_data"] = Candidate::where([
            "jobs.status" => "pending",
            "jobs.business_id" => $business->id

        ])->whereBetween('jobs.job_start_date', [$startDateOfNextWeek, $endDateOfNextWeek])
            ->select("jobs.id", "jobs.created_at", "jobs.updated_at")
            ->get();

        $data["this_month_data"] = Candidate::where([
            "jobs.status" => "pending",
            "jobs.business_id" => $business->id

        ])->whereBetween('jobs.job_start_date', [$startDate, $endDateOfThisMonth])
            ->select("jobs.id", "jobs.created_at", "jobs.updated_at")
            ->get();
        $data["next_month_data"] = Candidate::where([
            "jobs.status" => "pending",
            "jobs.business_id" => $business->id

        ])->whereBetween('jobs.job_start_date', [$startDateOfNextMonth, $endDateOfNextMonth])
            ->select("jobs.id", "jobs.created_at", "jobs.updated_at")
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
            "business_affiliations.business_id" => $business->id
        ])
            ->count();


        $data["this_week_data"] = Candidate::where([
            "business_affiliations.business_id" => $business->id
        ])
            ->whereBetween('business_affiliations.end_date', [$startDate, $endDateOfThisWeek])

            ->select("business_affiliations.id", "business_affiliations.created_at", "business_affiliations.updated_at")
            ->get();
        $data["next_week_data"] = Candidate::where([
            "business_affiliations.business_id" => $business->id
        ])
            ->whereBetween('business_affiliations.end_date', [$startDateOfNextWeek, $endDateOfNextWeek])

            ->select("business_affiliations.id", "business_affiliations.created_at", "business_affiliations.updated_at")
            ->get();

        $data["this_month_data"] = Candidate::where([
            "business_affiliations.business_id" => $business->id
        ])
            ->whereBetween('business_affiliations.end_date', [$startDate, $endDateOfThisMonth])
            ->select("business_affiliations.id", "business_affiliations.created_at", "business_affiliations.updated_at")
            ->get();

        $data["next_month_data"] = Candidate::where([
            "business_affiliations.business_id" => $business->id
        ])
            ->whereBetween('business_affiliations.end_date', [$startDateOfNextMonth, $endDateOfNextMonth])
            ->select("business_affiliations.id", "business_affiliations.created_at", "business_affiliations.updated_at")
            ->get();

        $data["this_week_data_count"] = $data["this_week_data"]->count();
        $data["next_week_data_count"] = $data["next_week_data"]->count();
        $data["this_month_data_count"] = $data["this_month_data"]->count();
        $data["next_month_data_count"] = $data["next_month_data"]->count();


        return $data;
    }

    public function employees(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    ) {

        $data_query  = User::whereHas("departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereNotIn('id', [auth()->user()->id])


            ->where('is_in_employee', 1)
            ->where('is_active', 1);
            $data["total_data"] = $data_query->get();

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('users.created_at', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();


        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('users.created_at', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();



        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('created_at', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();




        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween('created_at', [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();


        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('created_at', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween('created_at', [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();




        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];


        return $data;
    }

    // public function approved_leaves(
    //     $today,
    //     $start_date_of_this_month,
    //     $end_date_of_this_month,
    //     $start_date_of_previous_month,
    //     $end_date_of_previous_month,
    //     $start_date_of_this_week,
    //     $end_date_of_this_week,
    //     $start_date_of_previous_week,
    //     $end_date_of_previous_week,
    //     $all_manager_department_ids
    // )
    // {



    //     $data_query  = LeaveRecord::whereHas("leave.employee.departments", function($query) use($all_manager_department_ids) {
    //        $query->whereIn("departments.id",$all_manager_department_ids);
    //     })
    //     ->whereHas("leave", function($query) use($all_manager_department_ids) {
    //         $query->where([
    //             "leaves.business_id" => auth()->user()->business_id,
    //             "leaves.status" => "approved"
    //             ]);
    //      })
    //         ->whereNotIn('id', [auth()->user()->id])
    //         ->where('is_in_employee', 1)
    //         ->where('is_active', 1);

    //     $data["total_data_count"] = $data_query->count();
    //     $data["today_data_count"] = $data_query->whereBetween('date', [$today, ($today . ' 23:59:59')])->count();
    //     $data["this_week_data_count"] = $data_query->whereBetween('date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();
    //     $data["previous_week_data_count"] = $data_query->whereBetween('date', [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();
    //     $data["this_month_data_count"] = $data_query->whereBetween('date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();
    //     $data["previous_month_data_count"] = $data_query->whereBetween('date', [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();

    //     return $data;
    // }

    public function employee_on_holiday(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids

    ) {
        $total_departments = Department::where([
            "business_id" => auth()->user()->business_id,
            "is_active" => 1
        ])->count();

        $data_query  = User::whereHas("departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereNotIn('id', [auth()->user()->id])
            ->where('is_in_employee', 1)
            ->where('is_active', 1)
            ->where("business_id",auth()->user()->id);


        // $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]

        ->where(function($query) use ($today, $total_departments)  {
                 $query->where(function($query) use ($today, $total_departments) {

                    $query->where(function($query) use ($today,$total_departments) {
                        $query->whereHas('holidays', function ($query) use ($today) {
                            $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                            ->where('holidays.end_date', ">=",  $today->copy()->endOfDay());

                        })
                        ->orWhere(function($query) use($today, $total_departments) {
                              $query->whereHasRecursiveHolidays($today,$total_departments);
                        });

                        // ->whereHas('departments.holidays', function ($query) use ($today) {
                        //     $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                        //     ->where('holidays.end_date', ">=",  $today->copy()->endOfDay());
                        // });

                    })
                    ->where(function($query) use ($today) {
                        $query->orWhereDoesntHave('holidays', function ($query) use ($today) {
                            $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay())
                                  ->where('holidays.end_date', ">=",  $today->copy()->endOfDay())
                                  ->orWhere(function ($query) {
                                    $query->whereDoesntHave("users")
                                        ->whereDoesntHave("departments");
                                });


                        });
                    });





                })
                ->orWhere(
                    function($query) use ($today) {
                    $query->orWhereDoesntHave('holidays', function ($query) use ($today) {
                        $query->where('holidays.start_date', "<=",  $today->copy()->startOfDay());
                        $query->where('holidays.end_date', ">=",  $today->copy()->endOfDay());
                        $query->doesntHave('users');

                    });

                }
            );
        })



       ->count();

        // $data["next_week_data_count"] = clone $data_query;
        // $data["next_week_data_count"] = $data["next_week_data_count"]

        // ->where(function($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //     $query->whereHas('departments.holidays', function ($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_week . ' 23:59:59');
        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_week . ' 23:59:59');
        //         $query->doesntHave('departments');

        //     });
        // })
        // ->orWhere(function($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //     $query->whereHas('holidays', function ($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_week . ' 23:59:59');
        //     })->orWhereDoesntHave('holidays', function ($query) use ($start_date_of_next_week,$end_date_of_next_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_week . ' 23:59:59');
        //         $query->doesntHave('users');

        //     });
        // })

        // ->count();

        // $data["this_week_data_count"] = clone $data_query;
        // $data["this_week_data_count"] = $data["this_week_data_count"]

        // ->where(function($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //     $query->whereHas('departments.holidays', function ($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_week . ' 23:59:59');
        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_week . ' 23:59:59');
        //         $query->doesntHave('departments');
        //     });
        // })

        // ->orWhere(function($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //     $query->whereHas('holidays', function ($query) use ( $start_date_of_this_week,$end_date_of_this_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_week . ' 23:59:59');
        //     })->orWhereDoesntHave('holidays', function ($query) use ( $start_date_of_this_week,$end_date_of_this_week) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_week . ' 23:59:59');
        //         $query->doesntHave('users');


        //     });
        // })



        // ->count();

        // $data["previous_week_data_count"] = clone $data_query;
        // $data["previous_week_data_count"] = $data["previous_week_data_count"]
        // ->where(function($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {
        //     $query->whereHas('departments.holidays', function ($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_week . ' 23:59:59');
        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_week . ' 23:59:59');
        //         $query->doesntHave('departments');

        //     });
        // })
        // ->orWhere(function($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {
        //     $query->whereHas('holidays', function ($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_week . ' 23:59:59');
        //     })->orWhereDoesntHave('holidays', function ($query) use ($start_date_of_previous_week,$end_date_of_previous_week) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_week);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_week . ' 23:59:59');
        //         $query->doesntHave('users');

        //     });
        // })



        // ->count();

        // $data["next_month_data_count"] = clone $data_query;
        // $data["next_month_data_count"] = $data["next_month_data_count"]
        // ->where(function($query) use ($start_date_of_next_month,$end_date_of_next_month) {
        //     $query->whereHas('departments.holidays', function ($query) use ( $start_date_of_next_month,$end_date_of_next_month) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_month . ' 23:59:59');
        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ($start_date_of_next_month,$end_date_of_next_month) {

        //          $query->where('holidays.start_date', "<=",  $start_date_of_next_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_month . ' 23:59:59');
        //         $query->doesntHave('departments');


        //     });
        // })
        // ->orWhere(function($query) use ($start_date_of_next_month,$end_date_of_next_month) {
        //     $query->whereHas('holidays', function ($query) use ( $start_date_of_next_month,$end_date_of_next_month) {
        //         $query->where('holidays.start_date', "<=",  $start_date_of_next_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_month . ' 23:59:59');
        //     })->orWhereDoesntHave('holidays', function ($query) use ($start_date_of_next_month,$end_date_of_next_month) {

        //          $query->where('holidays.start_date', "<=",  $start_date_of_next_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_next_month . ' 23:59:59');
        //         $query->doesntHave('users');


        //     });
        // })


        // ->count();

        // $data["this_month_data_count"] = clone $data_query;
        // $data["this_month_data_count"] = $data["this_month_data_count"]
        // ->where(function($query) use ( $start_date_of_this_month,$end_date_of_this_month) {
        //     $query->whereHas('departments.holidays', function ($query) use ( $start_date_of_this_month,$end_date_of_this_month) {


        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_month . ' 23:59:59');


        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ( $start_date_of_this_month,$end_date_of_this_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_month . ' 23:59:59');
        //         $query->doesntHave('departments');


        //     });

        // })

        // ->orWhere(function($query) use ( $start_date_of_this_month,$end_date_of_this_month) {
        //     $query->whereHas('holidays', function ($query) use ( $start_date_of_this_month,$end_date_of_this_month) {


        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_month . ' 23:59:59');


        //     })->orWhereDoesntHave('holidays', function ($query) use ( $start_date_of_this_month,$end_date_of_this_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_this_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_this_month . ' 23:59:59');
        //         $query->doesntHave('users');


        //     });

        // })

        // ->count();


        // $data["previous_month_data_count"] = clone $data_query;
        // $data["previous_month_data_count"] = $data["previous_month_data_count"]

        // ->where(function($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {
        //     $query ->whereHas('departments.holidays', function ($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_month . ' 23:59:59');


        //     })->orWhereDoesntHave('departments.holidays', function ($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_month . ' 23:59:59');
        //         $query->doesntHave('departments');

        //     });



        // })

        // ->orWhere(function($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {
        //     $query ->whereHas('holidays', function ($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_month . ' 23:59:59');




        //     })->orWhereDoesntHave('holidays', function ($query) use ($start_date_of_previous_month,$end_date_of_previous_month) {

        //         $query->where('holidays.start_date', "<=",  $start_date_of_previous_month);
        //         $query->where('holidays.end_date', ">=",  $end_date_of_previous_month . ' 23:59:59');
        //         $query->doesntHave('users');

        //     });



        // })




        // ->count();


        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];

        return $data;
    }




    public function leaves(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids,
        $status
    ) {

        $data_query  = LeaveRecord::whereHas("leave.employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
            ->whereHas("leave", function ($query) use ($status) {
                $query->where([
                    "leaves.business_id" => auth()->user()->business_id,
                    "leaves.status" => $status
                ]);
            });

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();

        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween('date', [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();

        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();

        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween('date', [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();

        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];
        return $data;
    }

    public function open_roles(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    ) {

        $data_query  = JobListing::where("application_deadline",">=", today())
        ->where("business_id",auth()->user()->business_id);

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('application_deadline', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('application_deadline', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('application_deadline', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('application_deadline', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('application_deadline', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];

        return $data;
    }
    public function upcoming_passport_expiries(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    ) {

        $data_query  = EmployeePassportDetail::
        whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->where("passport_expiry_date",">=", today())
        ->where("business_id",auth()->user()->business_id);

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('passport_expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
$data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('passport_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('passport_expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('passport_expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('passport_expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('passport_expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween('passport_expiry_date', [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }
        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];

        return $data;
    }

    public function upcoming_visa_expiries(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    ) {

        $data_query  = EmployeeVisaDetail::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->where("visa_expiry_date",">=", today())
        ->where("business_id",auth()->user()->business_id);

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('visa_expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('visa_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('visa_expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('visa_expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('visa_expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('visa_expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween('visa_expiry_date', [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }

        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];

        return $data;
    }
    public function upcoming_right_to_work_expiries(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    ) {

        $data_query  = EmployeeRightToWork::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->where("right_to_work_expiry_date",">=", today())
        ->where("business_id",auth()->user()->business_id);

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('right_to_work_expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('right_to_work_expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('right_to_work_expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('right_to_work_expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('right_to_work_expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('right_to_work_expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween('right_to_work_expiry_date', [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }

        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];

        return $data;
    }
    public function upcoming_sponsorship_expiries(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids
    ) {

        $data_query  = EmployeeSponsorship::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })

        ->where("expiry_date",">=", today())
        ->where("business_id",auth()->user()->business_id);

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween('expiry_date', [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween('expiry_date', [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }


        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];

        return $data;
    }

    public function upcoming_pension_expiries(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids


    ) {


        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';


        $employee_pension_history_ids = EmployeePensionHistory::select('id','user_id')
        ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereHas("employee", function ($query) use ($all_manager_department_ids) {
            $query->where("users.pension_eligible",">",0);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->whereNotNull($expiry_date_column)
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {
            $latest_expired_record = EmployeePensionHistory::where('user_id', $record->user_id)
            ->where($issue_date_column, '<', now())
            ->orderByDesc($expiry_date_column)
            ->first();

                $current_data = EmployeePensionHistory::where('user_id', $record->user_id)
                ->where($expiry_date_column, $latest_expired_record->expiry_date_column)
                ->orderByDesc($issue_date_column)
                ->first();

                return $current_data->id;

        });

        $data_query  = EmployeePensionHistory::whereIn('id', $employee_pension_history_ids);














        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween($expiry_date_column, [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["yesterday_data_count"] = clone $data_query;
        $data["yesterday_data_count"] = $data["yesterday_data_count"]->whereBetween($expiry_date_column, [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();



        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();


        $expires_in_days = [15,30,60];
        foreach($expires_in_days as $expires_in_day){
            $query_day = Carbon::now()->addDays($expires_in_day);
            $data[("expires_in_". $expires_in_day ."_days")] = clone $data_query;
            $data[("expires_in_". $expires_in_day ."_days")] = $data[("expires_in_". $expires_in_day ."_days")]->whereBetween($expiry_date_column, [$today, ($query_day->endOfDay() . ' 23:59:59')])->count();
        }


        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];

        return $data;
    }





    public function sponsorships(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids,
        $current_certificate_status
    ) {

        $data_query  = EmployeeSponsorship::whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->where([
            "current_certificate_status"=>$current_certificate_status,
            "business_id"=>auth()->user()->business_id
        ]);

        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween('expiry_date', [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween('expiry_date', [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween('expiry_date', [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();

        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween('expiry_date', [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();

        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween('expiry_date', [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween('expiry_date', [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();

        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween('expiry_date', [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();
        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];


        return $data;
    }


    public function pensions(
        $today,
        $start_date_of_next_month,
        $end_date_of_next_month,
        $start_date_of_this_month,
        $end_date_of_this_month,
        $start_date_of_previous_month,
        $end_date_of_previous_month,
        $start_date_of_next_week,
        $end_date_of_next_week,
        $start_date_of_this_week,
        $end_date_of_this_week,
        $start_date_of_previous_week,
        $end_date_of_previous_week,
        $all_manager_department_ids,
        $status_column,
        $status_value
    ) {




        $issue_date_column = 'pension_enrollment_issue_date';
        $expiry_date_column = 'pension_re_enrollment_due_date';


        $employee_pension_history_ids = EmployeePensionHistory::select('user_id')
        ->whereHas("employee.departments", function ($query) use ($all_manager_department_ids) {
            $query->whereIn("departments.id", $all_manager_department_ids);
        })
        ->whereHas("employee", function ($query)  {
            $query->where("users.pension_eligible",">",0)
            ->where("is_active",1);
        })
        ->whereNotIn('user_id', [auth()->user()->id])
        ->where($issue_date_column, '<', now())
        ->whereNotNull($expiry_date_column)
        ->groupBy('user_id')
        ->get()
        ->map(function ($record) use ($issue_date_column, $expiry_date_column) {
            $latest_expired_record = EmployeePensionHistory::where('user_id', $record->user_id)
            ->where($issue_date_column, '<', now())
            ->orderByDesc($expiry_date_column)
            ->first();

                $current_data = EmployeePensionHistory::where('user_id', $record->user_id)
                ->where($expiry_date_column, $latest_expired_record->expiry_date_column)
                ->orderByDesc($issue_date_column)
                ->first();
                return $current_data->id;
        });




        $data_query  = EmployeePensionHistory::whereIn('id', $employee_pension_history_ids)
        ->when(!empty($status_column), function($query) use ($status_column,$status_value) {
         $query->where($status_column, $status_value);
        });









        $data["total_data_count"] = $data_query->count();

        $data["today_data_count"] = clone $data_query;
        $data["today_data_count"] = $data["today_data_count"]->whereBetween($expiry_date_column, [$today->copy()->startOfDay(), $today->copy()->endOfDay()])->count();

        $data["next_week_data_count"] = clone $data_query;
        $data["next_week_data_count"] = $data["next_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_next_week, ($end_date_of_next_week . ' 23:59:59')])->count();

        $data["this_week_data_count"] = clone $data_query;
        $data["this_week_data_count"] = $data["this_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_this_week, ($end_date_of_this_week . ' 23:59:59')])->count();

        $data["previous_week_data_count"] = clone $data_query;
        $data["previous_week_data_count"] = $data["previous_week_data_count"]->whereBetween($expiry_date_column, [$start_date_of_previous_week, ($end_date_of_previous_week . ' 23:59:59')])->count();

        $data["next_month_data_count"] = clone $data_query;
        $data["next_month_data_count"] = $data["next_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_next_month, ($end_date_of_next_month . ' 23:59:59')])->count();

        $data["this_month_data_count"] = clone $data_query;
        $data["this_month_data_count"] = $data["this_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_this_month, ($end_date_of_this_month . ' 23:59:59')])->count();

        $data["previous_month_data_count"] = clone $data_query;
        $data["previous_month_data_count"] = $data["previous_month_data_count"]->whereBetween($expiry_date_column, [$start_date_of_previous_month, ($end_date_of_previous_month . ' 23:59:59')])->count();

        $data["date_ranges"] = [
            "today_data_count_date_range" => [$today->copy()->startOfDay(), $today->copy()->endOfDay() ],
            "yesterday_data_count_date_range" => [now()->subDay()->startOfDay(), now()->subDay()->endOfDay()],
            "next_week_data_count_date_range" => [$start_date_of_next_week, ($end_date_of_next_week )],
            "this_week_data_count_date_range" => [$start_date_of_this_week, ($end_date_of_this_week )],
            "previous_week_data_count_date_range" => [$start_date_of_previous_week, ($end_date_of_previous_week )],
            "next_month_data_count_date_range" => [$start_date_of_next_month, ($end_date_of_next_month )],
            "this_month_data_count_date_range" => [$start_date_of_this_month, ($end_date_of_this_month )],
            "previous_month_data_count_date_range" => [$start_date_of_previous_month, ($end_date_of_previous_month)],
          ];


        return $data;
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/business-user-dashboard",
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

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            $business_id = auth()->user()->business_id;
            if (!$business_id) {
                return response()->json([
                    "message" => "You are not a business user"
                ], 401);
            }
            $today = today();

            $start_date_of_next_month = Carbon::now()->startOfMonth()->addMonth(1);
            $end_date_of_next_month = Carbon::now()->endOfMonth()->addMonth(1);
            $start_date_of_this_month = Carbon::now()->startOfMonth();
            $end_date_of_this_month = Carbon::now()->endOfMonth();
            $start_date_of_previous_month = Carbon::now()->startOfMonth()->subMonth(1);
            $end_date_of_previous_month = Carbon::now()->startOfMonth()->subDay(1);

            $start_date_of_next_week = Carbon::now()->startOfWeek()->addWeek(1);
            $end_date_of_next_week = Carbon::now()->endOfWeek()->addWeek(1);
            $start_date_of_this_week = Carbon::now()->startOfWeek();
            $end_date_of_this_week = Carbon::now()->endOfWeek();
            $start_date_of_previous_week = Carbon::now()->startOfWeek()->subWeek(1);
            $end_date_of_previous_week = Carbon::now()->endOfWeek()->subWeek(1);











            // $business = Business::where([
            //     "id" => $business_id,
            //     "owner_id" => $request->user()->id
            // ])
            //     ->first();

            // if (!$business) {
            //     return response()->json([
            //         "message" => "you are not the owner of the business or the request business does not exits"
            //     ], 404);
            // }

      $dashboard_widgets =  DashboardWidget::where([
                "user_id" => auth()->user()->id
            ])
            ->get()
            ->keyBy('widget_name');

            // $data["dashboard_widgets"] = $dashboard_widgets;


            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $user_ids =  User::whereHas("departments", function ($query) use ($all_manager_department_ids) {
                $query->whereIn("departments.id", $all_manager_department_ids);
            })
                ->whereNotIn('id', [auth()->user()->id])


                ->where('is_in_employee', 1)
                ->where('is_active', 1)
                ->pluck("id");


            $data["employees"] = $this->employees(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids
            );

            $widget = $dashboard_widgets->get("employees");

            $data["employees"]["id"] = 1;
            if($widget) {
                $data["employees"]["widget_id"] = $widget->id;
                $data["employees"]["widget_order"] = $widget->widget_order;
            }
            else {
                $data["employees"]["widget_id"] = 0;
                $data["employees"]["widget_order"] = 0;
            }

            $data["employees"]["widget_name"] = "employees";
            $data["employees"]["route"] =  '/employee/all-employees?';

            //     $data["approved_leaves"] = $this->approved_leaves(
            //         $today,
            //         $start_date_of_this_month,
            //         $end_date_of_this_month,
            //         $start_date_of_previous_month,
            //         $end_date_of_previous_month,
            //         $start_date_of_this_week,
            //         $end_date_of_this_week,
            //         $start_date_of_previous_week,
            //         $end_date_of_previous_week,
            //         $all_manager_department_ids
            // );

            $data["employee_on_holiday"] = $this->employee_on_holiday(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids,

            );
            $widget = $dashboard_widgets->get("employee_on_holiday");


            $data["employee_on_holiday"]["id"] = 2;
            if($widget) {
                $data["employee_on_holiday"]["widget_id"] = $widget->id;
                $data["employee_on_holiday"]["widget_order"] = $widget->widget_order;
            }
            else {
                $data["employee_on_holiday"]["widget_id"] = 0;
                $data["employee_on_holiday"]["widget_order"] = 0;
            }

            $data["employee_on_holiday"]["widget_name"] = "employee_on_holiday";
            $data["employee_on_holiday"]["route"] =  '/employee/all-employees?is_on_holiday=1&';

            $start_id = 3;
            $leave_statuses = ['pending_approval','progress', 'approved','rejected'];
            foreach ($leave_statuses as $leave_status) {
                $data[($leave_status . "_leaves")] = $this->leaves(
                    $today,
                    $start_date_of_next_month,
                    $end_date_of_next_month,
                    $start_date_of_this_month,
                    $end_date_of_this_month,
                    $start_date_of_previous_month,
                    $end_date_of_previous_month,
                    $start_date_of_next_week,
                    $end_date_of_next_week,
                    $start_date_of_this_week,
                    $end_date_of_this_week,
                    $start_date_of_previous_week,
                    $end_date_of_previous_week,
                    $all_manager_department_ids,
                    $leave_status
                );
                $widget = $dashboard_widgets->get(($leave_status . "_leaves"));



                $data[($leave_status . "_leaves")]["id"] = $start_id++;
                if($widget) {
                    $data[($leave_status . "_leaves")]["widget_id"] = $widget->id;
                    $data[($leave_status . "_leaves")]["widget_order"] = $widget->widget_order;
                }
                else {
                    $data[($leave_status . "_leaves")]["widget_id"] = 0;
                    $data[($leave_status . "_leaves")]["widget_order"] = 0;
                }


                $data[($leave_status . "_leaves")]["widget_name"] = ($leave_status . "_leaves");

                $data[($leave_status . "_leaves")]["route"] = ('/leave/leaves?status=' . $leave_status . "&");
            }



            $data["open_roles"] = $this->open_roles(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids
            );
            $widget = $dashboard_widgets->get("open_roles");


            $data["open_roles"]["id"] = $start_id++;
            if($widget) {
                $data["open_roles"]["widget_id"] = $widget->id;
                $data["open_roles"]["widget_order"] = $widget->widget_order;
            }
            else {
                $data["open_roles"]["widget_id"] = 0;
                $data["open_roles"]["widget_order"] = 0;
            }


            $data["open_roles"]["widget_name"] = "open_roles";
            $data["open_roles"]["route"] = "/job-desk/job-list?is_open_roles=1&";


            $data["upcoming_passport_expiries"] = $this->upcoming_passport_expiries(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids
            );
            $widget = $dashboard_widgets->get("upcoming_passport_expiries");


            $data["upcoming_passport_expiries"]["id"] =$start_id++;
            if($widget) {
                $data["upcoming_passport_expiries"]["widget_id"] = $widget->id;
                $data["upcoming_passport_expiries"]["widget_order"] = $widget->widget_order;
            }
            else {
                $data["upcoming_passport_expiries"]["widget_id"] = 0;
                $data["upcoming_passport_expiries"]["widget_order"] = 0;
            }





            $data["upcoming_passport_expiries"]["widget_name"] = "upcoming_passport_expiries";
            $data["upcoming_passport_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=passport&";

            $data["upcoming_visa_expiries"] = $this->upcoming_visa_expiries(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids
            );
            $widget = $dashboard_widgets->get("upcoming_visa_expiries");


            $data["upcoming_visa_expiries"]["id"] = $start_id++;
            if($widget) {
                $data["upcoming_visa_expiries"]["widget_id"] = $widget->id;
                $data["upcoming_visa_expiries"]["widget_order"] = $widget->widget_order;
            }
            else {
                $data["upcoming_visa_expiries"]["widget_id"] = 0;
                $data["upcoming_visa_expiries"]["widget_order"] = 0;
            }


            $data["upcoming_visa_expiries"]["widget_name"] = "upcoming_visa_expiries";


            $data["upcoming_visa_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=visa&";








            $data["upcoming_right_to_work_expiries"] = $this->upcoming_right_to_work_expiries(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids
            );
            $widget = $dashboard_widgets->get("upcoming_right_to_work_expiries");


            $data["upcoming_right_to_work_expiries"]["id"] = $start_id++;
            if($widget) {
                $data["upcoming_right_to_work_expiries"]["widget_id"] = $widget->id;
                $data["upcoming_right_to_work_expiries"]["widget_order"] = $widget->widget_order;
            }
            else {
                $data["upcoming_right_to_work_expiries"]["widget_id"] = 0;
                $data["upcoming_right_to_work_expiries"]["widget_order"] = 0;
            }


            $data["upcoming_right_to_work_expiries"]["widget_name"] = "upcoming_right_to_work_expiries";
            $data["upcoming_right_to_work_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=right_to_work&";











            $data["upcoming_sponsorship_expiries"] = $this->upcoming_sponsorship_expiries(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids
            );
            $widget = $dashboard_widgets->get("upcoming_sponsorship_expiries");



            $data["upcoming_sponsorship_expiries"]["id"] = $start_id++;
            if($widget) {
                $data["upcoming_sponsorship_expiries"]["widget_id"] = $widget->id;
                $data["upcoming_sponsorship_expiries"]["widget_order"] = $widget->widget_order;
            }
            else {
                $data["upcoming_sponsorship_expiries"]["widget_id"] = 0;
                $data["upcoming_sponsorship_expiries"]["widget_order"] = 0;
            }


            $data["upcoming_sponsorship_expiries"]["widget_name"] = "upcoming_sponsorship_expiries";
            $data["upcoming_sponsorship_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=sponsorship&";





            $sponsorship_statuses = ['unassigned', 'assigned', 'visa_applied','visa_rejected','visa_grantes','withdrawal'];
            foreach ($sponsorship_statuses as $sponsorship_status) {
                $data[($sponsorship_status . "_sponsorships")] = $this->sponsorships(
                    $today,
                    $start_date_of_next_month,
                    $end_date_of_next_month,
                    $start_date_of_this_month,
                    $end_date_of_this_month,
                    $start_date_of_previous_month,
                    $end_date_of_previous_month,
                    $start_date_of_next_week,
                    $end_date_of_next_week,
                    $start_date_of_this_week,
                    $end_date_of_this_week,
                    $start_date_of_previous_week,
                    $end_date_of_previous_week,
                    $all_manager_department_ids,
                    $sponsorship_status
                );
                $widget = $dashboard_widgets->get(($sponsorship_status . "_sponsorships"));


                $data[($sponsorship_status . "_sponsorships")]["id"] = $start_id++;
                if($widget) {
                    $data[($sponsorship_status . "_sponsorships")]["widget_id"] = $widget->id;
                    $data[($sponsorship_status . "_sponsorships")]["widget_order"] = $widget->widget_order;
                }
                else {
                    $data[($sponsorship_status . "_sponsorships")]["widget_id"] = 0;
                    $data[($sponsorship_status . "_sponsorships")]["widget_order"] = 0;
                }


                $data[($sponsorship_status . "_sponsorships")]["widget_name"] = ($sponsorship_status . "_sponsorships");
                $data[($sponsorship_status . "_sponsorships")]["route"] = '/employee/all-employees?sponsorship_status=' . $sponsorship_status . "&";

            }









            $data["upcoming_pension_expiries"] = $this->upcoming_pension_expiries(
                $today,
                $start_date_of_next_month,
                $end_date_of_next_month,
                $start_date_of_this_month,
                $end_date_of_this_month,
                $start_date_of_previous_month,
                $end_date_of_previous_month,
                $start_date_of_next_week,
                $end_date_of_next_week,
                $start_date_of_this_week,
                $end_date_of_this_week,
                $start_date_of_previous_week,
                $end_date_of_previous_week,
                $all_manager_department_ids,

            );


            $widget = $dashboard_widgets->get("upcoming_pension_expiries");



            $data["upcoming_pension_expiries"]["id"] = $start_id++;
            if($widget) {
                $data["upcoming_pension_expiries"]["widget_id"] = $widget->id;
                $data["upcoming_pension_expiries"]["widget_order"] = $widget->widget_order;
            }
            else {
                $data["upcoming_pension_expiries"]["widget_id"] = 0;
                $data["upcoming_pension_expiries"]["widget_order"] = 0;
            }



            $data["upcoming_pension_expiries"]["widget_name"] = "upcoming_pension_expiries";
            $data["upcoming_pension_expiries"]["route"] = "/employee/all-employees?upcoming_expiries=pension&";





            $pension_statuses = ["opt_in", "opt_out"];
            foreach ($pension_statuses as $pension_status) {
                $data[($pension_status . "_pensions")] = $this->pensions(
                    $today,
                    $start_date_of_next_month,
                    $end_date_of_next_month,
                    $start_date_of_this_month,
                    $end_date_of_this_month,
                    $start_date_of_previous_month,
                    $end_date_of_previous_month,
                    $start_date_of_next_week,
                    $end_date_of_next_week,
                    $start_date_of_this_week,
                    $end_date_of_this_week,
                    $start_date_of_previous_week,
                    $end_date_of_previous_week,
                    $all_manager_department_ids,
                    "pension_scheme_status",
                    $pension_status
                );
                $widget = $dashboard_widgets->get(($pension_status . "_pensions"));


                $data[($pension_status . "_pensions")]["id"] = $start_id++;
                if($widget) {
                    $data[($pension_status . "_pensions")]["widget_id"] = $widget->id;
                    $data[($pension_status . "_pensions")]["widget_order"] = $widget->widget_order;
                }
                else {
                    $data[($pension_status . "_pensions")]["widget_id"] = 0;
                    $data[($pension_status . "_pensions")]["widget_order"] = 0;
                }


                $data[($pension_status . "_pensions")]["widget_name"] = ($pension_status . "_pensions");
                $data[($pension_status . "_pensions")]["route"] = '/employee/all-employees?pension_scheme_status=' . $pension_status . "&";
            }





            return response()->json($data, 200);
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }






 /**
     *
     * @OA\Post(
     *      path="/v1.0/dashboard-widgets",
     *      operationId="createDashboardWidget",
     *      tags={"dashboard_management.dashboard_widgets"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store dashboard widgets",
     *      description="This method is to store dashboard widgets",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *
     *
 *     @OA\Property(property="widgets", type="string", format="array", example={
 *    {"id":1,
 *    "widget_name":"passport",
 *    "widget_order":1}
 * }),
 *
 *
 *
 *
 *
 *
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

     public function createDashboardWidget(WidgetCreateRequest $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             return DB::transaction(function () use ($request) {

                $request_data = $request->validated();

                foreach ($request_data["widgets"] as $widget) {
                    $widget["user_id"] = auth()->user()->id;

                    DashboardWidget::updateOrCreate(
                        [
                            "widget_name" => $widget["widget_name"],
                            "user_id" => $widget["user_id"],
                        ],
                        $widget
                    );
                }

                return response(["ok" => true], 201);
             });


         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }

 /**
     *
     *     @OA\Delete(
    *      path="/v1.0/dashboard-widgets/{ids}",
     *      operationId="deleteDashboardWidgetsByIds",
     *      tags={"dashboard_management.dashboard_widgets"},
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
     *      summary="This method is to delete widget by id",
     *      description="This method is to delete widget by id",
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

     public function deleteDashboardWidgetsByIds(Request $request, $ids)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");

             $idsArray = explode(',', $ids);
             $existingIds = DashboardWidget::where([
                 "user_id" => auth()->user()->id
             ])
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
                     "message" => "Some or all of the specified data do not exist."
                 ], 404);
             }
             DashboardWidget::destroy($existingIds);


             return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }




















    public function businesses($created_by_filter = 0)
    {
        $startDateOfThisMonth = Carbon::now()->startOfMonth();
        $endDateOfThisMonth = Carbon::now()->endOfMonth();
        $startDateOfPreviousMonth = Carbon::now()->startOfMonth()->subMonth(1);
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);



        $total_data_count_query = new Candidate();
        if ($created_by_filter) {
            $total_data_count_query =  $total_data_count_query->where([
                "created_by" => auth()->user()->id
            ]);
        }

        $data["total_data_count"] = $total_data_count_query->count();



        $this_week_data_query = Business::whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek]);

        if ($created_by_filter) {
            $this_week_data_query =  $this_week_data_query->where([
                "created_by" => auth()->user()->id
            ]);
        }
        $data["this_week_data"] = $this_week_data_query->select("id", "created_at", "updated_at")->get();




        $previous_week_data_query = Business::whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek]);

        if ($created_by_filter) {
            $previous_week_data_query =  $previous_week_data_query->where([
                "created_by" => auth()->user()->id
            ]);
        }

        $data["previous_week_data"] = $total_data_count_query->select("id", "created_at", "updated_at")->get();




        $this_month_data_query = Business::whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth]);

        if ($created_by_filter) {
            $this_month_data_query =  $this_month_data_query->where([
                "created_by" => auth()->user()->id
            ]);
        }
        $data["this_month_data"] = $this_month_data_query->select("id", "created_at", "updated_at")->get();




        $previous_month_data_query = Business::whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth]);

        if ($created_by_filter) {
            $previous_month_data_query =  $previous_month_data_query->where([
                "created_by" => auth()->user()->id
            ]);
        }
        $data["previous_month_data"] = $previous_month_data_query->select("id", "created_at", "updated_at")->get();



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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);


        $total_data_count_query = new Candidate();
        if ($created_by_filter) {
            $total_data_count_query =  $total_data_count_query->where([
                "created_by" => auth()->user()->id
            ]);
        }
        $data["total_data_count"] = $total_data_count_query->count();


        $this_week_data_query = Candidate::whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek]);
        if ($created_by_filter) {
            $this_week_data_query =  $this_week_data_query->where([
                "created_by" => auth()->user()->id
            ]);
        }
        $data["this_week_data"] = $this_week_data_query->select("id", "created_at", "updated_at")
            ->get();


        $previous_week_data_query = Candidate::whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek]);
        if ($created_by_filter) {
            $previous_week_data_query =  $previous_week_data_query->where([
                "created_by" => auth()->user()->id
            ]);
        }
        $data["previous_week_data"] = $previous_week_data_query->select("id", "created_at", "updated_at")
            ->get();


        $this_month_data_query =  Candidate::whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth]);
        if ($created_by_filter) {
            $this_month_data_query =  $this_month_data_query->where([
                "created_by" => auth()->user()->id
            ]);
        }
        $data["this_month_data"] = $this_month_data_query->select("id", "created_at", "updated_at")
            ->get();

        $previous_month_data_query =  Candidate::whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth]);
        if ($created_by_filter) {
            $previous_month_data_query =  $previous_month_data_query->where([
                "created_by" => auth()->user()->id
            ]);
        }
        $data["previous_month_data"] = $previous_month_data_query->select("id", "created_at", "updated_at")
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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);



        $data["total_data_count"] = User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["customer"]);
        })->count();


        $data["this_week_data"] = User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["customer"]);
        })->whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->select("id", "created_at", "updated_at")
            ->get();

        $data["previous_week_data"] = User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["customer"]);
        })->whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("id", "created_at", "updated_at")
            ->get();



        $data["this_month_data"] = User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["customer"]);
        })->whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("id", "created_at", "updated_at")
            ->get();
        $data["previous_month_data"] = User::with("roles")->whereHas("roles", function ($q) {
            $q->whereIn("name", ["customer"]);
        })->whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("id", "created_at", "updated_at")
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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);



        $data["total_data_count"] = Candidate::count();


        $data["this_week_data"] = Candidate::whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->select("id", "created_at", "updated_at")
            ->get();

        $data["previous_week_data"] = Candidate::whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("id", "created_at", "updated_at")
            ->get();



        $data["this_month_data"] = Candidate::whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("id", "created_at", "updated_at")
            ->get();

        $data["previous_month_data"] = Candidate::whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("id", "created_at", "updated_at")
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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);


        $total_data_count_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id');
        if ($created_by_filter) {
            $total_data_count_query =  $total_data_count_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["total_data_count"] = $total_data_count_query->count();



        $this_week_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id')
            ->whereBetween('bookings.created_at', [$startDateOfThisWeek, $endDateOfThisWeek]);
        if ($created_by_filter) {
            $this_week_data_query =  $this_week_data_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["this_week_data"] = $this_week_data_query->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();




        $previous_week_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id')
            ->whereBetween('bookings.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek]);
        if ($created_by_filter) {
            $previous_week_data_query =  $previous_week_data_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["previous_week_data"] = $previous_week_data_query->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();






        $this_month_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id')
            ->whereBetween('bookings.created_at', [$startDateOfThisMonth, $endDateOfThisMonth]);
        if ($created_by_filter) {
            $this_month_data_query =  $this_month_data_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["this_month_data"] = $this_month_data_query->select("bookings.id", "bookings.created_at", "bookings.updated_at")
            ->get();


        $previous_month_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'bookings.business_id')
            ->whereBetween('bookings.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth]);
        if ($created_by_filter) {
            $previous_month_data_query =  $previous_month_data_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["previous_month_data"] = $previous_month_data_query->select("bookings.id", "bookings.created_at", "bookings.updated_at")
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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);


        $total_data_count_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id');
        if ($created_by_filter) {
            $total_data_count_query =  $total_data_count_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["total_data_count"] = $total_data_count_query->count();





        $this_week_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id')
            ->whereBetween('jobs.created_at', [$startDateOfThisWeek, $endDateOfThisWeek]);
        if ($created_by_filter) {
            $this_week_data_query =  $this_week_data_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["this_week_data"] = $this_week_data_query
            ->select("jobs.id", "jobs.created_at", "jobs.updated_at")
            ->get();




        $previous_week_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id')
            ->whereBetween('jobs.created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek]);
        if ($created_by_filter) {
            $previous_week_data_query =  $previous_week_data_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["previous_week_data"] = $previous_week_data_query
            ->select("jobs.id", "jobs.created_at", "jobs.updated_at")
            ->get();





        $this_month_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id')
            ->whereBetween('jobs.created_at', [$startDateOfThisMonth, $endDateOfThisMonth]);
        if ($created_by_filter) {
            $this_month_data_query =  $this_month_data_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["this_month_data"] = $this_month_data_query
            ->select("jobs.id", "jobs.created_at", "jobs.updated_at")
            ->get();



        $previous_month_data_query =  Candidate::leftJoin('businesses', 'businesses.id', '=', 'jobs.business_id')
            ->whereBetween('jobs.created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth]);
        if ($created_by_filter) {
            $previous_month_data_query =  $previous_month_data_query->where([
                "businesses.created_by" => auth()->user()->id
            ]);
        }
        $data["previous_month_data"] = $previous_month_data_query
            ->select("jobs.id", "jobs.created_at", "jobs.updated_at")
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
        $endDateOfPreviousMonth = Carbon::now()->startOfMonth()->subDay(1);

        $startDateOfThisWeek = Carbon::now()->startOfWeek();
        $endDateOfThisWeek = Carbon::now()->endOfWeek();
        $startDateOfPreviousWeek = Carbon::now()->startOfWeek()->subWeek(1);
        $endDateOfPreviousWeek = Carbon::now()->endOfWeek()->subWeek(1);



        $data["total_data_count"] = Candidate::count();


        $data["this_week_data"] = Candidate::whereBetween('created_at', [$startDateOfThisWeek, $endDateOfThisWeek])
            ->select("id", "created_at", "updated_at")
            ->get();

        $data["previous_week_data"] = Candidate::whereBetween('created_at', [$startDateOfPreviousWeek, $endDateOfPreviousWeek])
            ->select("id", "created_at", "updated_at")
            ->get();



        $data["this_month_data"] = Candidate::whereBetween('created_at', [$startDateOfThisMonth, $endDateOfThisMonth])
            ->select("id", "created_at", "updated_at")
            ->get();
        $data["previous_month_data"] = Candidate::whereBetween('created_at', [$startDateOfPreviousMonth, $endDateOfPreviousMonth])
            ->select("id", "created_at", "updated_at")
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

    public function getSuperAdminDashboardData(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
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
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
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

    public function getDataCollectorDashboardData(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
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
        } catch (Exception $e) {
            return $this->sendError($e, 500, $request);
        }
    }
}
