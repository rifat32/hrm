<?php

namespace App\Http\Controllers;

use App\Http\Requests\CandidateCreateRequest;
use App\Http\Requests\CandidateUpdateRequest;
use App\Http\Requests\MultipleFileUploadRequest;
use App\Http\Utils\BasicUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Candidate;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CandidateController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, BasicUtil;

    /**
        *
     * @OA\Post(
     *      path="/v1.0/candidates/multiple-file-upload",
     *      operationId="createCandidateFileMultiple",
     *      tags={"candidates"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store multiple candidate files",
     *      description="This method is to store multiple candidate files",
     *
   *  @OA\RequestBody(
        *   * @OA\MediaType(
*     mediaType="multipart/form-data",
*     @OA\Schema(
*         required={"files[]"},
*         @OA\Property(
*             description="array of files to upload",
*             property="files[]",
*             type="array",
*             @OA\Items(
*                 type="file"
*             ),
*             collectionFormat="multi",
*         )
*     )
* )



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

     public function createCandidateFileMultiple(MultipleFileUploadRequest $request)
     {
         try{
             $this->storeActivity($request, "DUMMY activity","DUMMY description");

             $insertableData = $request->validated();

             $location =  config("setup-config.candidate_files_location");

             $files = [];
             if(!empty($insertableData["files"])) {
                 foreach($insertableData["files"] as $file){
                     $new_file_name = time() . '_' . $file->getClientOriginalName();
                     $new_file_name = time() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                     $file->move(public_path($location), $new_file_name);

                     array_push($files,("/".$location."/".$new_file_name));


                 }
             }


             return response()->json(["files" => $files], 201);


         } catch(Exception $e){
             error_log($e->getMessage());
         return $this->sendError($e,500,$request);
         }
     }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/candidates",
     *      operationId="createCandidate",
     *      tags={"candidates"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store candidate",
     *      description="This method is to store candidate",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*     @OA\Property(property="name", type="string", format="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", format="string", example="123-456-7890"),
 *     @OA\Property(property="experience_years", type="integer", format="int", example=3),
 *     @OA\Property(property="education_level", type="string", format="string", example="Bachelor's Degree"),
 *     @OA\Property(property="job_platforms", type="string", format="array", example={1,2,3}),
 *     @OA\Property(property="cover_letter", type="string", format="string", example="Cover letter content..."),
 *     @OA\Property(property="application_date", type="string", format="date", example="2023-11-01"),
 *     @OA\Property(property="interview_date", type="string", format="date", example="2023-11-10"),
 *     @OA\Property(property="feedback", type="string", format="string", example="Positive feedback..."),
 *     @OA\Property(property="status", type="string", format="string", example="review"),
 *     @OA\Property(property="job_listing_id", type="integer", format="int", example=1),
 *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})
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

    public function createCandidate(CandidateCreateRequest $request)
    {

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");

                if (!$request->user()->hasPermissionTo('candidate_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"],"","candidate_files");


                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $candidate =  Candidate::create($request_data);




                $candidate->job_platforms()->sync($request_data['job_platforms']);




                // $this->moveUploadedFiles($request_data["attachments"],"candidate_files");


                DB::commit();

                return response($candidate, 201);

        } catch (Exception $e) {
            DB::rollBack();

            $this->moveUploadedFilesBack($request_data["attachments"],"","candidate_files");



            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Post(
     *      path="/v1.0/client/candidates",
     *      operationId="createCandidateClient",
     *      tags={"candidates"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store candidate",
     *      description="This method is to store candidate",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*     @OA\Property(property="name", type="string", format="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", format="string", example="123-456-7890"),
 *     @OA\Property(property="experience_years", type="integer", format="int", example=3),
 *     @OA\Property(property="education_level", type="string", format="string", example="Bachelor's Degree"),
 *     @OA\Property(property="job_platforms", type="string", format="array", example={1,2,3}),
 *     @OA\Property(property="cover_letter", type="string", format="string", example="Cover letter content..."),
 *     @OA\Property(property="application_date", type="string", format="date", example="2023-11-01"),
 *     @OA\Property(property="interview_date", type="string", format="date", example="2023-11-10"),
 *     @OA\Property(property="feedback", type="string", format="string", example="Positive feedback..."),
 *     @OA\Property(property="status", type="string", format="string", example="review"),
 *     @OA\Property(property="job_listing_id", type="integer", format="int", example=1),
 *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})
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

     public function createCandidateClient(CandidateCreateRequest $request)
     {
        DB::beginTransaction();
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");

                 if (!$request->user()->hasPermissionTo('candidate_create')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }

                 $request_data = $request->validated();
       $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"],"","candidate_files");




                 $request_data["business_id"] = $request->user()->business_id;
                 $request_data["is_active"] = true;
                 $request_data["created_by"] = $request->user()->id;

                 $candidate =  Candidate::create($request_data);

                 $candidate->job_platforms()->sync($request_data['job_platforms']);

                //  $this->moveUploadedFiles($request_data["attachments"],"candidate_files");

                DB::commit();
                 return response($candidate, 201);

         } catch (Exception $e) {
            DB::rollBack();
            $this->moveUploadedFilesBack($request_data["attachments"],"","candidate_files");
             return $this->sendError($e, 500, $request);
         }
     }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/candidates",
     *      operationId="updateCandidate",
     *      tags={"candidates"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update candidate ",
     *      description="This method is to update candidate",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
*     @OA\Property(property="name", type="string", format="string", example="John Doe"),
 *     @OA\Property(property="email", type="string", format="email", example="john.doe@example.com"),
 *     @OA\Property(property="phone", type="string", format="string", example="123-456-7890"),
 *     @OA\Property(property="experience_years", type="integer", format="int", example=3),
 *     @OA\Property(property="education_level", type="string", format="string", example="Bachelor's Degree"),
 *  *     @OA\Property(property="job_platform", type="string", format="string", example="facebook"),
 *
 *     @OA\Property(property="cover_letter", type="string", format="string", example="Cover letter content..."),
 *     @OA\Property(property="application_date", type="string", format="date", example="2023-11-01"),
 *     @OA\Property(property="interview_date", type="string", format="date", example="2023-11-10"),
 *     @OA\Property(property="feedback", type="string", format="string", example="Positive feedback..."),
 *     @OA\Property(property="status", type="string", format="string", example="review"),
 *     @OA\Property(property="job_listing_id", type="integer", format="int", example=1),
 *   @OA\Property(property="attachments", type="string", format="array", example={"/abcd.jpg","/efgh.jpg"})

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

    public function updateCandidate(CandidateUpdateRequest $request)
    {

        DB::beginTransaction();
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");



                if (!$request->user()->hasPermissionTo('candidate_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();

                $candidate_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => auth()->user()->business_id
                ];

                $candidate  =     Candidate::where($candidate_query_params)->first();
                $this->moveUploadedFilesBack($candidate->attachments,"","candidate_files");
                $request_data["attachments"] = $this->storeUploadedFiles($request_data["attachments"],"","candidate_files");



                // $candidate_prev = Candidate::where($candidate_query_params)
                //     ->first();
                // if (!$candidate_prev) {
                //     return response()->json([
                //         "message" => "no candidate found"
                //     ], 404);
                // }

                $candidate  =  tap(Candidate::where($candidate_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'email',
                        'phone',
                        'experience_years',
                        'education_level',

                        'cover_letter',
                        'application_date',
                        'interview_date',
                        'feedback',
                        'status',
                        'job_listing_id',
                        'attachments',

                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$candidate) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }






                $candidate->job_platforms()->sync($request_data['job_platforms']);



                DB::commit();
                return response($candidate, 201);





        } catch (Exception $e) {
            $this->moveUploadedFilesBack($request_data["attachments"],"","candidate_files");
            DB::rollBack();
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/candidates",
     *      operationId="getCandidates",
     *      tags={"candidates"},
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
     *    *      * *  @OA\Parameter(
     * name="job_listing_id",
     * in="query",
     * description="job_listing_id",
     * required=true,
     * example="1"
     * ),

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
     *
     *     * *  @OA\Parameter(
     * name="name",
     * in="query",
     * description="name",
     * required=true,
     * example="name"
     * ),
     *

     *
     *  @OA\Parameter(
     * name="job_platform_id",
     * in="query",
     * description="job_platform",
     * required=true,
     * example="job_platform_id"
     * ),
     *
     *  *  @OA\Parameter(
     * name="interview_date",
     * in="query",
     * description="interview_date",
     * required=true,
     * example="interview_date"
     * ),
     *     *  *  @OA\Parameter(
     * name="status",
     * in="query",
     * description="status",
     * required=true,
     * example="status"
     * ),
     *
     *
     *
     *
     *
     *
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get candidates  ",
     *      description="This method is to get candidates ",
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

    public function getCandidates(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('candidate_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $candidates = Candidate::
            with("job_listing","job_platforms")

            ->where(
                [
                    "candidates.business_id" => $business_id
                ]
            )

                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        // $query->where("candidates.name", "like", "%" . $term . "%")
                        //     ->orWhere("candidates.description", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->name), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->name;
                        $query->where("candidates.name", "like", "%" . $term . "%");
                        //     ->orWhere("candidates.description", "like", "%" . $term . "%");
                    });
                })



                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('candidates.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('candidates.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })


                ->when(!empty($request->job_listing_id), function ($query) use ($request) {
                    $idsArray = explode(',', $request->job_listing_id);
                    return $query->whereIn('candidates.job_listing_id',$idsArray);
                })


                ->when(!empty($request->job_platform_id), function ($query) use ($request) {
                    $job_platform_ids = explode(',', $request->job_platform_id);
                    $query->whereHas("job_platforms",function($query) use($job_platform_ids){
                        $query->whereIn("job_platforms.id", $job_platform_ids);
                    });
                })




                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("candidates.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("candidates.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($candidates, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/candidates/{id}",
     *      operationId="getCandidateById",
     *      tags={"candidates"},
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
     *      summary="This method is to get candidate by id",
     *      description="This method is to get candidate by id",
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


    public function getCandidateById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('candidate_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $candidate =  Candidate:: with("job_listing","job_platforms")
            ->where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->first();
            if (!$candidate) {

                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($candidate, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/candidates/{ids}",
     *      operationId="deleteCandidatesByIds",
     *      tags={"candidates"},
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
     *      summary="This method is to delete candidate by id",
     *      description="This method is to delete candidate by id",
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

    public function deleteCandidatesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('candidate_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Candidate::where([
                "business_id" => $business_id
            ])
                ->whereIn('id', $idsArray)
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {

                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }
            Candidate::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
