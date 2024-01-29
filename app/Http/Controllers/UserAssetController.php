<?php

namespace App\Http\Controllers;

use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\UserAssetAddExistingRequest;
use App\Http\Requests\UserAssetCreateRequest;
use App\Http\Requests\UserAssetUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use App\Models\UserAsset;
use App\Models\UserAssetHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserAssetController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;


    /**
          *
       * @OA\Post(
       *      path="/v1.0/user-assets/single-file-upload",
       *      operationId="createUserAssetFileSingle",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to store user asset file ",
       *      description="This method is to store user asset file",
       *
     *  @OA\RequestBody(
          *   * @OA\MediaType(
  *     mediaType="multipart/form-data",
  *     @OA\Schema(
  *         required={"file"},
  *         @OA\Property(
  *             description="file to upload",
  *             property="file",
  *             type="file",
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

       public function createUserAssetFileSingle(SingleFileUploadRequest $request)
       {
           try{
               $this->storeActivity($request, "DUMMY activity","DUMMY description");
               // if(!$request->user()->hasPermissionTo('business_create')){
               //      return response()->json([
               //         "message" => "You can not perform this action"
               //      ],401);
               // }

               $request_data = $request->validated();

               $location =  config("setup-config.user_assets_location");

               $new_file_name = time() . '_' . str_replace(' ', '_', $request_data["file"]->getClientOriginalName());

               $request_data["file"]->move(public_path($location), $new_file_name);


               return response()->json(["file" => $new_file_name,"location" => $location,"full_location"=>("/".$location."/".$new_file_name)], 200);


           } catch(Exception $e){
               error_log($e->getMessage());
           return $this->sendError($e,500,$request);
           }
       }



      /**
       *
       * @OA\Post(
       *      path="/v1.0/user-assets",
       *      operationId="createUserAsset",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to store user document",
       *      description="This method is to store user document",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
*     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="name", type="string", format="string", example="Your Name"),
 *     @OA\Property(property="code", type="string", format="string", example="Your Code"),
 *     @OA\Property(property="is_working", type="boolean", format="boolean", example="1"),
 *  *     @OA\Property(property="status", type="string", format="string", example="status"),
 *
 *     @OA\Property(property="serial_number", type="string", format="string", example="Your Serial Number"),
 *     @OA\Property(property="type", type="string", format="string", example="Your Type"),
 *     @OA\Property(property="image", type="string", format="string", example="Your Image URL"),
 *     @OA\Property(property="date", type="string", format="string", example="Your Date"),
 *     @OA\Property(property="note", type="string", format="string", example="Your Note"),
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

      public function createUserAsset(UserAssetCreateRequest $request)
      {
          try {
              $this->storeActivity($request, "DUMMY activity","DUMMY description");
              return DB::transaction(function () use ($request) {
                  if (!$request->user()->hasPermissionTo('employee_asset_create')) {
                      return response()->json([
                          "message" => "You can not perform this action"
                      ], 401);
                  }

                  $request_data = $request->validated();


                  $request_data["created_by"] = $request->user()->id;
                  $request_data["business_id"] = $request->user()->business_id;

                  $user_asset =  UserAsset::create($request_data);



                  $user_asset_history  =  UserAssetHistory::create([
                    'user_id' => $user_asset->user_id,
                    "user_asset_id" => $user_asset->id,


        'name' => $user_asset->name,
        'code' => $user_asset->code,
        'serial_number' => $user_asset->serial_number,
        'type' => $user_asset->type,
        "is_working" => $user_asset->is_working,
        "status" => $user_asset->status,
        'image' => $user_asset->image,
        'date' => $user_asset->date,
        'note' => $user_asset->note,
        "business_id" => $user_asset->business_id,


                    "from_date" => now(),
                    "to_date" => NULL,
                    'created_by' => $request_data["created_by"]

                  ]
                  );


                  return response($user_asset, 201);
              });
          } catch (Exception $e) {
              error_log($e->getMessage());
              return $this->sendError($e, 500, $request);
          }
      }

       /**
       *
       * @OA\Put(
       *      path="/v1.0/user-assets/add-existing",
       *      operationId="addExistingUserAsset",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to add existing  user asset ",
       *      description="This method is to add existing  user asset",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  *      @OA\Property(property="id", type="number", format="number", example="1"),
*     @OA\Property(property="user_id", type="integer", format="int", example=1)
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

       public function addExistingUserAsset(UserAssetAddExistingRequest $request)
       {

           try {
               $this->storeActivity($request, "DUMMY activity","DUMMY description");
               return DB::transaction(function () use ($request) {
                   if (!$request->user()->hasPermissionTo('employee_asset_update')) {
                       return response()->json([
                           "message" => "You can not perform this action"
                       ], 401);
                   }

                   $request_data = $request->validated();




                   $user_asset_query_params = [
                       "id" => $request_data["id"],
                   ];
                   $user_asset_prev = UserAsset::where($user_asset_query_params)
                       ->first();
                   if (!$user_asset_prev) {
                    $this->storeError(
                        "no data found"
                        ,
                        404,
                        "front end error",
                        "front end error"
                       );
                       return response()->json([
                           "message" => "no user document found"
                       ], 404);
                   }


                   $user_asset  =  tap(UserAsset::where($user_asset_query_params))->update(
                       collect($request_data)->only([
                            'user_id',


                       ])->toArray()
                   )
                       // ->with("somthing")

                       ->first();
                   if (!$user_asset) {
                       return response()->json([
                           "message" => "something went wrong."
                       ], 500);
                   }

                   if($user_asset_prev->user_id != $user_asset->user_id) {
                    UserAssetHistory::where([
                        'user_id' => $user_asset_prev->user_id,
                        "user_asset_id" => $user_asset_prev->id,
                        "to_date" => NULL
                    ])
                    ->update([
                        "to_date" => now(),
                    ]);
                    $user_asset_history  =  UserAssetHistory::create([
                        'user_id' => $user_asset->user_id,
                        "user_asset_id" => $user_asset->id,

                        'name' => $user_asset->name,
                        'code' => $user_asset->code,
                        'serial_number' => $user_asset->serial_number,
                        'type' => $user_asset->type,
                        "is_working" => $user_asset->is_working,
                        "status" => $user_asset->status,
                        'image' => $user_asset->image,
                        'date' => $user_asset->date,
                        'note' => $user_asset->note,
                        "business_id" => $user_asset->business_id,

                        "from_date" => now(),
                        "to_date" => NULL,
                        'created_by' => $user_asset->created_by

                      ]
                      );
                   }

                   return response($user_asset, 201);
               });
           } catch (Exception $e) {
               error_log($e->getMessage());
               return $this->sendError($e, 500, $request);
           }
       }


      /**
       *
       * @OA\Put(
       *      path="/v1.0/user-assets",
       *      operationId="updateUserAsset",
       *      tags={"user_assets"},
       *       security={
       *           {"bearerAuth": {}}
       *       },
       *      summary="This method is to update  user document ",
       *      description="This method is to update user document",
       *
       *  @OA\RequestBody(
       *         required=true,
       *         @OA\JsonContent(
  *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
*     @OA\Property(property="user_id", type="integer", format="int", example=1),
 *     @OA\Property(property="name", type="string", format="string", example="Your Name"),
 *     @OA\Property(property="code", type="string", format="string", example="Your Code"),
 *
 *  *     @OA\Property(property="is_working", type="boolean", format="boolean", example="1"),
 *  *  *     @OA\Property(property="status", type="string", format="string", example="status"),
 *
 *     @OA\Property(property="serial_number", type="string", format="string", example="Your Serial Number"),
 *     @OA\Property(property="type", type="string", format="string", example="Your Type"),
 *     @OA\Property(property="image", type="string", format="string", example="Your Image URL"),
 *     @OA\Property(property="date", type="string", format="string", example="Your Date"),
 *     @OA\Property(property="note", type="string", format="string", example="Your Note"),
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

      public function updateUserAsset(UserAssetUpdateRequest $request)
      {

          try {
              $this->storeActivity($request, "DUMMY activity","DUMMY description");
              return DB::transaction(function () use ($request) {
                  if (!$request->user()->hasPermissionTo('employee_asset_update')) {
                      return response()->json([
                          "message" => "You can not perform this action"
                      ], 401);
                  }
                  $business_id =  $request->user()->business_id;
                  $request_data = $request->validated();




                  $user_asset_query_params = [
                      "id" => $request_data["id"],
                  ];
                  $user_asset_prev = UserAsset::where($user_asset_query_params)
                      ->first();
                  if (!$user_asset_prev) {
                    $this->storeError(
                        "no data found"
                        ,
                        404,
                        "front end error",
                        "front end error"
                       );
                      return response()->json([
                          "message" => "no user document found"
                      ], 404);
                  }

                  $user_asset  =  tap(UserAsset::where($user_asset_query_params))->update(
                      collect($request_data)->only([
                           'user_id',
                          'name',
                          'code',
                          'serial_number',
                          'is_working',
                          "status",
                          'type',
                          'image',
                          'date',
                          'note',
                          // 'created_by',

                      ])->toArray()
                  )
                      // ->with("somthing")

                      ->first();
                  if (!$user_asset) {
                      return response()->json([
                          "message" => "something went wrong."
                      ], 500);
                  }
                  if($user_asset_prev->user_id != $user_asset->user_id) {
                    UserAssetHistory::where([
                        'user_id' => $user_asset_prev->user_id,
                        "user_asset_id" => $user_asset_prev->id,
                        "to_date" => NULL
                    ])
                    ->update([
                        "to_date" => now(),
                    ]);
                    $user_asset_history  =  UserAssetHistory::create([
                        'user_id' => $user_asset->user_id,
                        "user_asset_id" => $user_asset->id,

                        'name' => $user_asset->name,
                        'code' => $user_asset->code,
                        'serial_number' => $user_asset->serial_number,
                        'type' => $user_asset->type,
                        "is_working" => $user_asset->is_working,
                        "status" => $user_asset->status,
                        'image' => $user_asset->image,
                        'date' => $user_asset->date,
                        'note' => $user_asset->note,
                        "business_id" => $user_asset->business_id,

                        "from_date" => now(),
                        "to_date" => NULL,
                        'created_by' => $user_asset->created_by

                      ]
                      );
                   }

                  return response($user_asset, 201);
              });
          } catch (Exception $e) {
              error_log($e->getMessage());
              return $this->sendError($e, 500, $request);
          }
      }


      /**
       *
       * @OA\Get(
       *      path="/v1.0/user-assets",
       *      operationId="get",
       *      tags={"user_assets"},
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
       *
       *    @OA\Parameter(
       *         name="type",
       *         in="query",
       *         description="type",
       *         required=true,
       *  example="1"
       *      ),
       *    @OA\Parameter(
       *         name="status",
       *         in="query",
       *         description="status",
       *         required=true,
       *         example="status"
       *      ),
       *
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

       *      summary="This method is to get user assets  ",
       *      description="This method is to get user assets ",
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

      public function getUserAssets(Request $request)
      {
          try {
              $this->storeActivity($request, "DUMMY activity","DUMMY description");
              if (!$request->user()->hasPermissionTo('employee_asset_view')) {
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
              $user_assets = UserAsset::with([
                  "creator" => function ($query) {
                      $query->select('users.id', 'users.first_Name','users.middle_Name',
                      'users.last_Name');
                  },

              ])
              ->where([
                "business_id" => auth()->user()->business_id
              ])


              ->where(function($query) use($all_manager_department_ids) {
                $query->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                    $query->whereIn("departments.id",$all_manager_department_ids);
                 })
                 ->orWhere('user_assets.user_id', NULL)
                 ;

              })


              ->when(!empty($request->search_key), function ($query) use ($request) {
                      return $query->where(function ($query) use ($request) {
                          $term = $request->search_key;
                          $query->where("user_assets.name", "like", "%" . $term . "%");
                          $query->orWhere("user_assets.code", "like", "%" . $term . "%");
                          $query->orWhere("user_assets.serial_number", "like", "%" . $term . "%");

                          //     ->orWhere("user_assets.description", "like", "%" . $term . "%");
                      });
                  })
                  //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                  //        return $query->where('product_category_id', $request->product_category_id);
                  //    })

                  ->when(!empty($request->user_id), function ($query) use ($request) {
                      return $query->where('user_assets.user_id', $request->user_id);
                  })
                  ->when(!empty($request->type), function ($query) use ($request) {
                    return $query->where('user_assets.type', $request->type);
                })

                ->when(!empty($request->status), function ($query) use ($request) {
                    return $query->where('user_assets.status', $request->status);
                })


                //   ->when(empty($request->user_id), function ($query) use ($request) {
                //       return $query->where('user_assets.user_id', $request->user()->id);
                //   })
                  ->when(!empty($request->start_date), function ($query) use ($request) {
                      return $query->where('user_assets.date', ">=", $request->start_date);
                  })
                  ->when(!empty($request->end_date), function ($query) use ($request) {
                      return $query->where('user_assets.date', "<=", ($request->end_date . ' 23:59:59'));
                  })
                  ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                      return $query->orderBy("user_assets.id", $request->order_by);
                  }, function ($query) {
                      return $query->orderBy("user_assets.id", "DESC");
                  })
                  ->when(!empty($request->per_page), function ($query) use ($request) {
                      return $query->paginate($request->per_page);
                  }, function ($query) {
                      return $query->get();
                  });;



              return response()->json($user_assets, 200);
          } catch (Exception $e) {

              return $this->sendError($e, 500, $request);
          }
      }

      /**
       *
       * @OA\Get(
       *      path="/v1.0/user-assets/{id}",
       *      operationId="getUserAssetById",
       *      tags={"user_assets"},
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
       *      summary="This method is to get user document by id",
       *      description="This method is to get user document by id",
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


      public function getUserAssetById($id, Request $request)
      {
          try {
              $this->storeActivity($request, "DUMMY activity","DUMMY description");
              if (!$request->user()->hasPermissionTo('employee_asset_view')) {
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
              $user_asset =  UserAsset::where([
                  "id" => $id,
                  "business_id" => auth()->user()->business_id
              ])
              ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
                  ->first();
              if (!$user_asset) {
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



                // if(!empty($user_asset->user->departments[0])){
                //     if(!in_array($user_asset->user->departments[0]->id,$all_manager_department_ids)){
                //         return response()->json([
                //             "message" => "The use assigned is not in your department"
                //         ], 409);
                //     }
                // } else {
                //     return response()->json([
                //         "message" => "The use assigned don't have a department"
                //     ], 409);
                // }






              return response()->json($user_asset, 200);
          } catch (Exception $e) {

              return $this->sendError($e, 500, $request);
          }
      }



      /**
       *
       *     @OA\Delete(
       *      path="/v1.0/user-assets/{ids}",
       *      operationId="deleteUserAssetsByIds",
       *      tags={"user_assets"},
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
       *      summary="This method is to delete user document by id",
       *      description="This method is to delete user document by id",
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

      public function deleteUserAssetsByIds(Request $request, $ids)
      {

          try {
              $this->storeActivity($request, "DUMMY activity","DUMMY description");
              if (!$request->user()->hasPermissionTo('employee_asset_delete')) {
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
              $idsArray = explode(',', $ids);
              $existingIds = UserAsset::whereIn('id', $idsArray)
              ->whereHas("user.departments", function($query) use($all_manager_department_ids) {
                $query->whereIn("departments.id",$all_manager_department_ids);
             })
             ->where([
                "business_id" => auth()->user()->business_id
              ])
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
              UserAsset::destroy($existingIds);


              return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
          } catch (Exception $e) {

              return $this->sendError($e, 500, $request);
          }
      }
}
