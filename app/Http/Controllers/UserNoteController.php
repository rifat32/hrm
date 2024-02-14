<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserNoteCreateRequest;
use App\Http\Requests\UserNoteUpdateByBusinessOwnerRequest;
use App\Http\Requests\UserNoteUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\User;
use App\Models\UserNote;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserNoteController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;






    /**
     *
     * @OA\Post(
     *      path="/v1.0/user-notes",
     *      operationId="createUserNote",
     *      tags={"user_notes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user note",
     *      description="This method is to store user note",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 * @OA\Property(property="user_id", type="integer", format="int", example=1),
 * @OA\Property(property="title", type="string", format="string", example="Your title"),
 * @OA\Property(property="description", type="string", format="string", example="Your Major")
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

    public function createUserNote(UserNoteCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_note_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();


                $comment_text = $request_data["description"];

                // Parse comment for mentions
                preg_match_all('/@(\w+)/', $comment_text, $mentions);
                $mentioned_users = $mentions[1];
                $mentioned_users = User::where('business_id', $request->user()->business_id)
                ->whereIn('user_name', $mentioned_users)
                ->get();


                $request_data["created_by"] = $request->user()->id;

                $user_note =  UserNote::create($request_data);

// Store mentions in user_note_mentions table using createMany
$mentions_data = $mentioned_users->map(function ($mentioned_user) {
    return [
        'user_id' => $mentioned_user->id,
    ];
});

$user_note->mentions()->createMany($mentions_data);




                return response($user_note, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/user-notes",
     *      operationId="updateUserNote",
     *      tags={"user_notes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user note ",
     *      description="This method is to update user note",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="user_id", type="integer", format="int", example=1),
  * @OA\Property(property="title", type="string", format="string", example="Your title"),
 * @OA\Property(property="description", type="string", format="string", example="Your Major")
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

    public function updateUserNote(UserNoteUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('employee_note_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $business_id =  $request->user()->business_id;
                $request_data = $request->validated();

                $request_data["updated_by"] = $request->user()->id;

                $comment_text = $request_data["description"];

                // Parse comment for mentions
                preg_match_all('/@(\w+)/', $comment_text, $mentions);
                $mentioned_users = $mentions[1];
                $mentioned_users = User::where('business_id', $request->user()->business_id)
                ->whereIn('user_name', $mentioned_users)
                ->get();



                $user_note_query_params = [
                    "id" => $request_data["id"],
                ];
                // $user_note_prev = UserNote::where($user_note_query_params)
                //     ->first();
                // if (!$user_note_prev) {
                //     return response()->json([
                //         "message" => "no user note found"
                //     ], 404);
                // }

                $user_note  =  tap(UserNote::where($user_note_query_params))->update(
                    collect($request_data)->only([
                        'user_id',
                        'title',
                        'description',
                        // "hidden_note",
                        'updated_by'
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();

                if (!$user_note) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }
                // if($user_note->created_by == auth()->user()->created_by) {
                //     $user_note->hidden_note = $request_data["hidden_note"];
                //     $user_note->save();
                //  }
                $user_note->mentions()->delete();
// Store mentions in user_note_mentions table using createMany
$mentions_data = $mentioned_users->map(function ($mentioned_user) {
    return [
        'user_id' => $mentioned_user->id,
    ];
});

$user_note->mentions()->createMany($mentions_data);
                return response($user_note, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

      /**
     *
     * @OA\Put(
     *      path="/v1.0/user-notes/by-business-owner",
     *      operationId="updateUserNoteByBusinessOwner",
     *      tags={"user_notes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update  user note ",
     *      description="This method is to update user note",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="user_id", type="integer", format="int", example=1),
  * @OA\Property(property="title", type="string", format="string", example="Your title"),
 * @OA\Property(property="description", type="string", format="string", example="Your Major"),
 *  * @OA\Property(property="created_at", type="string", format="string", example="your date"),
 *  * @OA\Property(property="updated_at", type="string", format="string", example="your date")
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

     public function updateUserNoteByBusinessOwner(UserNoteUpdateByBusinessOwnerRequest $request)
     {

         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             return DB::transaction(function () use ($request) {
                 if (!$request->user()->hasPermissionTo('business_owner')) {
                     return response()->json([
                         "message" => "You can not perform this action"
                     ], 401);
                 }
                 $business_id =  $request->user()->business_id;
                 $request_data = $request->validated();
                 $request_data["updated_by"] = $request->user()->id;


                 $comment_text = $request_data["description"];

                 // Parse comment for mentions
                 preg_match_all('/@(\w+)/', $comment_text, $mentions);
                 $mentioned_users = $mentions[1];
                 $mentioned_users = User::where('business_id', $request->user()->business_id)
                 ->whereIn('user_name', $mentioned_users)
                 ->get();

                 $user_note_query_params = [
                     "id" => $request_data["id"],
                 ];
                 // $user_note_prev = UserNote::where($user_note_query_params)
                 //     ->first();
                 // if (!$user_note_prev) {
                 //     return response()->json([
                 //         "message" => "no user note found"
                 //     ], 404);
                 // }

                 UserNote::disableTimestamps();



                 // Update the record
                 UserNote::where($user_note_query_params)->update(
                     collect($request_data)->only([
                         'user_id',
                         'title',

                         'description',
                         'created_at', // If you need to update created_at manually
                         'updated_at', // If you need to update updated_at manually
                         'updated_by'
                     ])->toArray()
                 );

                 // Enable timestamp updates
                 UserNote::enableTimestamps();

                 // Retrieve the updated record
                 $user_note = UserNote::where($user_note_query_params)->first();

                 if (!$user_note) {
                     return response()->json([
                         "message" => "something went wrong."
                     ], 500);
                 }

                //  if($user_note->created_by == auth()->user()->created_by) {
                //     $user_note->hidden_note = $request_data["hidden_note"];
                //     $user_note->save();
                //  }
                 $user_note->mentions()->delete();
                 // Store mentions in user_note_mentions table using createMany
                 $mentions_data = $mentioned_users->map(function ($mentioned_user) {
                     return [
                         'user_id' => $mentioned_user->id,
                     ];
                 });

                 $user_note->mentions()->createMany($mentions_data);
                 return response($user_note, 201);
             });
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-notes",
     *      operationId="getUserNotes",
     *      tags={"user_notes"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="query",
     *         description="user_id",
     *         required=true,
     *  example="1"
     *      ),
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

     *      summary="This method is to get user notes  ",
     *      description="This method is to get user notes ",
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

    public function getUserNotes(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_note_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $user_notes = UserNote::with([
                "creator" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },
                "updater" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },


            ])

            ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("user_notes.name", "like", "%" . $term . "%");
                        //     ->orWhere("user_notes.description", "like", "%" . $term . "%");
                    });
                })
                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })

                ->when(!empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_notes.user_id', $request->user_id);
                })
                ->when(empty($request->user_id), function ($query) use ($request) {
                    return $query->where('user_notes.user_id', $request->user()->id);
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('user_notes.created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('user_notes.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("user_notes.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("user_notes.id", "DESC");
                })
                ->where(function($query) use($all_manager_department_ids) {
                    $query->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                        $query->whereIn("departments.id",$all_manager_department_ids);
                     });
                    //  ->orWhereHas("mentions", function($query) {
                    //     $query->where("user_note_mentions.user_id",auth()->user()->id);
                    //  });
                })

                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });



            return response()->json($user_notes, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/user-notes/{id}",
     *      operationId="getUserNoteById",
     *      tags={"user_notes"},
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
     *      summary="This method is to get user note by id",
     *      description="This method is to get user note by id",
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


    public function getUserNoteById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_note_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $user_note =  UserNote::
            with([
                "creator" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },
                "updater" => function ($query) {
                    $query->select('users.id', 'users.first_Name','users.middle_Name',
                    'users.last_Name');
                },

            ])
            ->where([
                "id" => $id,
            ])
            ->where(function($query) use($all_manager_department_ids) {
                $query->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 });
                //  ->orWhereHas("mentions", function($query) {
                //     $query->where("user_note_mentions.user_id",auth()->user()->id);
                //  });
            })
                ->first();
            if (!$user_note) {
                $this->storeError(
                    "no data found"
                    ,
                    404,
                    "front end error",
                    "front end error"
                   );
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($user_note, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/user-notes/{ids}",
     *      operationId="deleteUserNotesByIds",
     *      tags={"user_notes"},
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
     *      summary="This method is to delete user note by id",
     *      description="This method is to delete user note by id",
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

    public function deleteUserNotesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('employee_note_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $all_manager_department_ids = [];
            $manager_departments = Department::where("manager_id", $request->user()->id)->get();
            foreach ($manager_departments as $manager_department) {
                $all_manager_department_ids[] = $manager_department->id;
                $all_manager_department_ids = array_merge($all_manager_department_ids, $manager_department->getAllDescendantIds());
            }
            $idsArray = explode(',', $ids);
            $existingIds = UserNote::
            whereIn('id', $idsArray)
            ->when( !auth()->user()->hasPermissionTo('business_owner'), function($query) {
                $query->where('user_notes.created_by', '=', auth()->user()->id);
            })
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
            UserNote::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
