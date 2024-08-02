<?php











namespace App\Http\Controllers;

use App\Http\Requests\UserLetterCreateRequest;
use App\Http\Requests\UserLetterUpdateRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\UserLetter;
use App\Models\DisabledUserLetter;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserLetterController extends Controller
{

    use ErrorUtil, UserActivityUtil, BusinessUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-letters",
     *      operationId="createUserLetter",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user letters",
     *      description="This method is to store user letters",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     * @OA\Property(property="issue_date", type="string", format="string", example="issue_date"),
     * @OA\Property(property="letter_content", type="string", format="string", example="letter_content"),
     * @OA\Property(property="status", type="string", format="string", example="status"),
     * @OA\Property(property="sign_required", type="string", format="string", example="sign_required"),
     * @OA\Property(property="user_id", type="string", format="string", example="user_id"),
     * @OA\Property(property="attachments", type="string", format="string", example="attachments"),
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

    public function createUserLetter(UserLetterCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('user_letter_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();



                $request_data["created_by"] = $request->user()->id;
                $request_data["business_id"] = $request->user()->business_id;

                if (empty($request->user()->business_id)) {
                    $request_data["business_id"] = NULL;
                    if ($request->user()->hasRole('superadmin')) {
                        $request_data["is_default"] = 1;
                    }
                }




                $user_letter =  UserLetter::create($request_data);




                return response($user_letter, 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-letters",
     *      operationId="updateUserLetter",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user letters ",
     *      description="This method is to update user letters ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="1"),
     * @OA\Property(property="issue_date", type="string", format="string", example="issue_date"),
     * @OA\Property(property="letter_content", type="string", format="string", example="letter_content"),
     * @OA\Property(property="status", type="string", format="string", example="status"),
     * @OA\Property(property="sign_required", type="string", format="string", example="sign_required"),
     * @OA\Property(property="user_id", type="string", format="string", example="user_id"),
     * @OA\Property(property="attachments", type="string", format="string", example="attachments"),
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

    public function updateUserLetter(UserLetterUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('user_letter_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                $user_letter_query_params = [
                    "id" => $request_data["id"],
                ];

                $user_letter = UserLetter::where($user_letter_query_params)->first();

                if ($user_letter) {
                    $user_letter->fill(collect($request_data)->only([

                        "issue_date",
                        "letter_content",
                        "status",
                        "sign_required",
                        "user_id",
                        "attachments",
                        // "is_default",
                        // "is_active",
                        // "business_id",
                        // "created_by"
                    ])->toArray());
                    $user_letter->save();
                } else {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }




                return response($user_letter, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-letters",
     *      operationId="getUserLetters",
     *      tags={"user_letters"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *         @OA\Parameter(
     *         name="start_issue_date",
     *         in="query",
     *         description="start_issue_date",
     *         required=true,
     *  example="6"
     *      ),
     *         @OA\Parameter(
     *         name="end_issue_date",
     *         in="query",
     *         description="end_issue_date",
     *         required=true,
     *  example="6"
     *      ),



     *         @OA\Parameter(
     *         name="letter_content",
     *         in="query",
     *         description="letter_content",
     *         required=true,
     *  example="6"
     *      ),



     *         @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="status",
     *         required=true,
     *  example="6"
     *      ),






     *         @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         description="per_page",
     *         required=true,
     *  example="6"
     *      ),

     *     @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     *     @OA\Parameter(
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
     * *  @OA\Parameter(
     * name="id",
     * in="query",
     * description="id",
     * required=true,
     * example="ASC"
     * ),
     * *  @OA\Parameter(
     * name="is_single_search",
     * in="query",
     * description="is_single_search",
     * required=true,
     * example="ASC"
     * ),




     *      summary="This method is to get user letters  ",
     *      description="This method is to get user letters ",
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

    public function getUserLetters(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_letter_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $created_by  = NULL;
            if (auth()->user()->business) {
                $created_by = auth()->user()->business->created_by;
            }



            $user_letters = UserLetter::where('user_letters.business_id', $request->business_id)



                ->when(!empty($request->id), function ($query) use ($request) {
                    return $query->where('user_letters.id', $request->id);
                })

                ->when(!empty($request->start_issue_date), function ($query) use ($request) {
                    return $query->where('user_letters.issue_date', ">=", $request->start_issue_date);
                })
                ->when(!empty($request->end_issue_date), function ($query) use ($request) {
                    return $query->where('user_letters.issue_date', "<=", ($request->end_issue_date . ' 23:59:59'));
                })





                ->when(!empty($request->letter_content), function ($query) use ($request) {
                    return $query->where('user_letters.id', $request->string);
                })





                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('user_letters.id', $request->string);
                })








                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query

                            ->where("user_letters.letter_content", "like", "%" . $term . "%")
                            ->orWhere("user_letters.status", "like", "%" . $term . "%");
                    });
                })


                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('user_letters.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('user_letters.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("user_letters.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("user_letters.id", "DESC");
                })
                ->when($request->filled("is_single_search") && $request->boolean("is_single_search"), function ($query) use ($request) {
                    return $query->first();
                }, function ($query) {
                    return $query->when(!empty(request()->per_page), function ($query) {
                        return $query->paginate(request()->per_page);
                    }, function ($query) {
                        return $query->get();
                    });
                });

            if ($request->filled("is_single_search") && empty($user_letters)) {
                throw new Exception("No data found", 404);
            }


            return response()->json($user_letters, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-letters/{ids}",
     *      operationId="deleteUserLettersByIds",
     *      tags={"user_letters"},
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
     *      summary="This method is to delete user letter by id",
     *      description="This method is to delete user letter by id",
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

    public function deleteUserLettersByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('user_letter_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = UserLetter::whereIn('id', $idsArray)
                ->where('user_letters.business_id', auth()->user()->business_id)

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





            UserLetter::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
