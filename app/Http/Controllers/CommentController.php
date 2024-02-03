<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentCreateRequest;
use App\Http\Requests\CommentUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\ModuleUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Comment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil, ModuleUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/comments",
     *      operationId="createComment",
     *      tags={"comment"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store comment listing",
     *      description="This method is to store comment listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(

 *     @OA\Property(property="name", type="string", format="string", example="Comment X"),
 *     @OA\Property(property="description", type="string", format="string", example="A brief overview of Comment X's objectives and scope."),
 *     @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2023-06-30"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2023-12-31"),
 *     @OA\Property(property="status", type="string", format="string", example="progress"),
 *     @OA\Property(property="project_id", type="integer", format="integer", example="1"),
 *     @OA\Property(property="parent_comment_id", type="integer", format="integer", example="2"),
 *  *     @OA\Property(property="assignees", type="string", format="array", example={1,2,3})

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

    public function createComment(CommentCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if(!$this->isModuleEnabled("project_and_comment_management")) {
                $this->storeError(
                    'Module is not enabled',
                    403,
                    "front end error",
                    "front end error"
                   );
                return response()->json(['error' => 'Module is not enabled'], 403);
             }

            return DB::transaction(function () use ($request) {

                if (!$request->user()->hasPermissionTo('comment_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["assigned_by"] = $request->user()->id;
                $comment =  Comment::create($request_data);
                $comment->assignees()->sync($request_data['assignees'],[]);
                return response($comment, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/comments",
     *      operationId="updateComment",
     *      tags={"comment"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update comment listing ",
     *      description="This method is to update comment listing",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *    @OA\Property(property="id", type="number", format="number",example="1"),
 *     @OA\Property(property="name", type="string", format="string", example="Comment X"),
 *     @OA\Property(property="description", type="string", format="string", example="A brief overview of Comment X's objectives and scope."),
 *     @OA\Property(property="start_date", type="string", format="date", example="2023-01-01"),
 *     @OA\Property(property="due_date", type="string", format="date", example="2023-06-30"),
 *     @OA\Property(property="end_date", type="string", format="date", example="2023-12-31"),
 *     @OA\Property(property="status", type="string", format="string", example="progress"),
 *     @OA\Property(property="project_id", type="integer", format="integer", example="1"),
 *     @OA\Property(property="parent_comment_id", type="integer", format="integer", example="2")
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

    public function updateComment(CommentUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if(!$this->isModuleEnabled("project_and_comment_management")) {
                $this->storeError(
                    'Module is not enabled',
                    403,
                    "front end error",
                    "front end error"
                   );
                return response()->json(['error' => 'Module is not enabled'], 403);
             }
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('comment_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();




                $comment_query_params = [
                    "id" => $request_data["id"],
                    "business_id" => $business_id
                ];
                // $comment_prev = Comment::where($comment_query_params)
                //     ->first();
                // if (!$comment_prev) {
                //     return response()->json([
                //         "message" => "no comment listing found"
                //     ], 404);
                // }

                $comment  =  tap(Comment::where($comment_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'description',
                        'start_date',
                        'due_date',
                        'end_date',
                        'status',
                        'project_id',
                        'parent_comment_id',
                        'assigned_by',

                        // "is_active",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$comment) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }
                $comment->assignees()->sync($request_data['assignees'],[]);
                return response($comment, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/comments",
     *      operationId="getComments",
     *      tags={"comment"},
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
     *    @OA\Parameter(
     *         name="project_id",
     *         in="query",
     *         description="project_id",
     *         required=true,
     *  example="1"
     *      ),
     *      *    @OA\Parameter(
     *         name="status",
     *         in="query",
     *         description="status",
     *         required=true,
     *  example="pending"
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

     *      summary="This method is to get comment listings  ",
     *      description="This method is to get comment listings ",
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

    public function getComments(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if(!$this->isModuleEnabled("project_and_comment_management")) {
                $this->storeError(
                    'Module is not enabled',
                    403,
                    "front end error",
                    "front end error"
                   );
                return response()->json(['error' => 'Module is not enabled'], 403);
             }
            if (!$request->user()->hasPermissionTo('comment_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $comments = Comment::with("assigned_by","assignees")

            ->where(
                [
                    "business_id" => $business_id
                ]
            )
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("name", "like", "%" . $term . "%")
                            ->orWhere("location", "like", "%" . $term . "%")
                            ->orWhere("description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })

                ->when(!empty($request->project_id), function ($query) use ($request) {
                    return $query->where('project_id' , $request->project_id);
                })
                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('status' , $request->status);
                })

                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("comments.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("comments.id", "DESC");
                })
                ->select('comments.*',

                 )
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });



            return response()->json($comments, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/comments/{id}",
     *      operationId="getCommentById",
     *      tags={"comment"},
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
     *      summary="This method is to get comment listing by id",
     *      description="This method is to get comment listing by id",
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


    public function getCommentById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if(!$this->isModuleEnabled("project_and_comment_management")) {
                $this->storeError(
                    'Module is not enabled',
                    403,
                    "front end error",
                    "front end error"
                   );
                return response()->json(['error' => 'Module is not enabled'], 403);
             }
            if (!$request->user()->hasPermissionTo('comment_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $comment =  Comment::with("assigned_by","assignees")
            ->where([
                "id" => $id,
                "business_id" => $business_id
            ])
            ->select('comments.*'
             )
                ->first();
            if (!$comment) {
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "no comment listing found"
                ], 404);
            }

            return response()->json($comment, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/comments/{ids}",
     *      operationId="deleteCommentsByIds",
     *      tags={"comment"},
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
     *      summary="This method is to delete comment listing by id",
     *      description="This method is to delete comment listing by id",
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

    public function deleteCommentsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if(!$this->isModuleEnabled("project_and_comment_management")) {
                $this->storeError(
                    'Module is not enabled',
                    403,
                    "front end error",
                    "front end error"
                   );
                return response()->json(['error' => 'Module is not enabled'], 403);
             }
            if (!$request->user()->hasPermissionTo('comment_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Comment::where([
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

            Comment::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
