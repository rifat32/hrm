<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobListingCreateRequest;
use App\Http\Requests\JobListingUpdateeRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\JobListing;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JobListingController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/job-listings",
     *      operationId="createJobListing",
     *      tags={"job_listing"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store job listing",
     *      description="This method is to store job listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

*     @OA\Property(property="title", type="string", format="string", example="Software Engineer"),
 *     @OA\Property(property="description", type="string", format="string", example="Exciting opportunity for a skilled developer..."),
 *     @OA\Property(property="required_skills", type="string", format="string", example="Java, Python, SQL"),
 *     @OA\Property(property="application_deadline", type="string", format="date", example="2023-12-01"),
 *     @OA\Property(property="posted_on", type="string", format="date", example="2023-11-20"),
 *     @OA\Property(property="job_platforms", type="string", format="{}", example={1,2}),
 *     @OA\Property(property="department_id", type="number", format="number", example="1"),
 * *     @OA\Property(property="minimum_salary", type="number", format="number", example="80000"),
 *     @OA\Property(property="maximum_salary", type="number", format="number", example="100000"),
 *     @OA\Property(property="experience_level", type="string", format="string", example="Mid-level"),
 *     @OA\Property(property="job_type_id", type="number", format="number", example="123"),
 *     @OA\Property(property="work_location_id", type="integer", format="int", example="1")

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

    public function createJobListing(JobListingCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('job_listing_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();




                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;

                $job_listing =  JobListing::create($request_data);
                $job_listing->job_platforms()->sync($request_data['job_platforms']);

                return response($job_listing, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/job-listings",
     *      operationId="updateJobListing",
     *      tags={"job_listing"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update job listing ",
     *      description="This method is to update job listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
 *     @OA\Property(property="title", type="string", format="string", example="Software Engineer"),
 *     @OA\Property(property="description", type="string", format="string", example="Exciting opportunity for a skilled developer..."),
 *     @OA\Property(property="required_skills", type="string", format="string", example="Java, Python, SQL"),
 *     @OA\Property(property="application_deadline", type="string", format="date", example="2023-12-01"),
 *     @OA\Property(property="posted_on", type="string", format="date", example="2023-11-20"),
 *     @OA\Property(property="job_platforms", type="string", format="array", example={1,2,3}),
 *     @OA\Property(property="department_id", type="number", format="number", example="1"),
 *  * *     @OA\Property(property="minimum_salary", type="number", format="number", example="80000"),
 *     @OA\Property(property="maximum_salary", type="number", format="number", example="100000"),
 *     @OA\Property(property="experience_level", type="string", format="string", example="Mid-level"),
 *     @OA\Property(property="job_type_id", type="number", format="number", example="123"),
 *     @OA\Property(property="work_location_id", type="integer", format="int", example="456")
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

    public function updateJobListing(JobListingUpdateeRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('job_listing_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();




                $job_listing_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];

                // $job_listing_prev = JobListing::where($job_listing_query_params)
                //     ->first();
                // if (!$job_listing_prev) {
                //     return response()->json([
                //         "message" => "no job listing found"
                //     ], 404);
                // }

                $job_listing  =  tap(JobListing::where($job_listing_query_params))->update(
                    collect($request_data)->only([
        'title',
        'description',
        'required_skills',
        'application_deadline',
        'posted_on',
        'department_id',
        'minimum_salary',
        'maximum_salary',
        'experience_level',
        'job_type_id',
        'work_location_id',
                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$job_listing) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }
                $job_listing->job_platforms()->sync($request_data['job_platforms']);

                return response($job_listing, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/job-listings",
     *      operationId="getJobListings",
     *      tags={"job_listing"},
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
     *     * *  @OA\Parameter(
     * name="is_open_roles",
     * in="query",
     * description="is_open_roles",
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
     *

     *      summary="This method is to get job listings  ",
     *      description="This method is to get job listings ",
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

    public function getJobListings(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('job_listing_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $job_listings = JobListing::with("job_platforms","job_type","work_location","department")
            ->where(
                [
                    "business_id" => $business_id
                ]
            )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("title", "like", "%" . $term . "%")
                            ->orWhere("description", "like", "%" . $term . "%");


                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(isset($request->is_open_roles), function ($query) use ($request) {
                    if( intval($request->is_open_roles) == 1) {
                        $query->where("application_deadline",">=", today());
                    } else {
                        $query->where("application_deadline","<", today());
                    }

                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("job_listings.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("job_listings.id", "DESC");
                })
                ->select('job_listings.*',

                 )
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });



            return response()->json($job_listings, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

        /**
     *
     * @OA\Get(
     *      path="/v1.0/client/job-listings",
     *      operationId="getJobListingsClient",
     *      tags={"job_listing"},
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
     *
     *      *              @OA\Parameter(
     *         name="business_id",
     *         in="query",
     *         description="business_id",
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

     *      summary="This method is to get job listings  ",
     *      description="This method is to get job listings ",
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

     public function getJobListingsClient(Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");


             $business_id =  $request->business_id;
             if(!$business_id) {
                $error = [ "message" => "The given data was invalid.",
                "errors" => ["business_id"=>["The business id field is required."]]
                ];
                    throw new Exception(json_encode($error),422);
             }

             $job_listings = JobListing::with("job_platforms","job_type","work_location","department")
             ->where(
                 [
                     "business_id" => $business_id
                 ]
             )
                 ->when(!empty($request->search_key), function ($query) use ($request) {
                     return $query->where(function ($query) use ($request) {
                         $term = $request->search_key;
                         $query->where("title", "like", "%" . $term . "%")
                             ->orWhere("description", "like", "%" . $term . "%");


                     });
                 })
                 //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                 //        return $query->where('product_category_id', $request->product_category_id);
                 //    })
                 ->when(!empty($request->start_date), function ($query) use ($request) {
                     return $query->where('created_at', ">=", $request->start_date);
                 })
                 ->when(!empty($request->end_date), function ($query) use ($request) {
                     return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                 })
                 ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                     return $query->orderBy("job_listings.id", $request->order_by);
                 }, function ($query) {
                     return $query->orderBy("job_listings.id", "DESC");
                 })
                 ->select('job_listings.*',

                  )
                 ->when(!empty($request->per_page), function ($query) use ($request) {
                     return $query->paginate($request->per_page);
                 }, function ($query) {
                     return $query->get();
                 });



             return response()->json($job_listings, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/job-listings/{id}",
     *      operationId="getJobListingById",
     *      tags={"job_listing"},
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
     *      summary="This method is to get job listing by id",
     *      description="This method is to get job listing by id",
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


    public function getJobListingById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('job_listing_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $job_listing =  JobListing::with("job_platforms","job_type","work_location","department")
            ->where([
                "id" => $id,
                "business_id" => $business_id
            ])
            ->select('job_listings.*'
             )
                ->first();
            if (!$job_listing) {
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "no job listing found"
                ], 404);
            }

            return response()->json($job_listing, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


 /**
     *
     * @OA\Get(
     *      path="/v1.0/client/job-listings/{id}",
     *      operationId="getJobListingByIdClient",
     *      tags={"job_listing"},
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
     *      summary="This method is to get job listing by id",
     *      description="This method is to get job listing by id",
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


     public function getJobListingByIdClient($id, Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");


             $job_listing =  JobListing::with("job_platforms","job_type","work_location","department")
             ->where([
                 "id" => $id,
             ])
             ->select('job_listings.*')
             ->first();
             if (!$job_listing) {
                 $this->storeError(
                     "no data found"
                     ,
                     404,
                     "front end error",
                     "front end error"
                    );
                 return response()->json([
                     "message" => "no job listing found"
                 ], 404);
             }

             return response()->json($job_listing, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/job-listings/{ids}",
     *      operationId="deleteJobListingsByIds",
     *      tags={"job_listing"},
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
     *      summary="This method is to delete job listing by id",
     *      description="This method is to delete job listing by id",
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

    public function deleteJobListingsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('job_listing_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = JobListing::where([
                "business_id" => $business_id
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
            JobListing::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
