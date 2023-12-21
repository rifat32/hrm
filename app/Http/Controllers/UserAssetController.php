<?php

namespace App\Http\Controllers;

use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Requests\UserAssetCreateRequest;
use App\Http\Requests\UserAssetUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\UserAsset;
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

                  $user_asset =  UserAsset::create($request_data);



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
                  // $user_asset_prev = UserAsset::where($user_asset_query_params)
                  //     ->first();
                  // if (!$user_asset_prev) {
                  //     return response()->json([
                  //         "message" => "no user document found"
                  //     ], 404);
                  // }

                  $user_asset  =  tap(UserAsset::where($user_asset_query_params))->update(
                      collect($request_data)->only([
                           'user_id',
                          'name',
                          'code',
                          'serial_number',
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
              $business_id =  $request->user()->business_id;
              $user_assets = UserAsset::with([
                  "creator" => function ($query) {
                      $query->select('users.id', 'users.first_Name','users.middle_Name',
                      'users.last_Name');
                  },

              ])
              ->when(!empty($request->search_key), function ($query) use ($request) {
                      return $query->where(function ($query) use ($request) {
                          $term = $request->search_key;
                          $query->where("user_assets.name", "like", "%" . $term . "%");
                          //     ->orWhere("user_assets.description", "like", "%" . $term . "%");
                      });
                  })
                  //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                  //        return $query->where('product_category_id', $request->product_category_id);
                  //    })

                  ->when(!empty($request->user_id), function ($query) use ($request) {
                      return $query->where('user_assets.user_id', $request->user_id);
                  })
                  ->when(empty($request->user_id), function ($query) use ($request) {
                      return $query->where('user_assets.user_id', $request->user()->id);
                  })
                  ->when(!empty($request->start_date), function ($query) use ($request) {
                      return $query->where('user_assets.created_at', ">=", Carbon::createFromFormat('d-m-Y', trim(($request->start_date)))->format('Y-m-d'));
                  })
                  ->when(!empty($request->end_date), function ($query) use ($request) {
                      return $query->where('user_assets.created_at', "<=", Carbon::createFromFormat('d-m-Y H:i:s', trim($request->end_date . ' 23:59:59'))->format('Y-m-d'));
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
              $business_id =  $request->user()->business_id;
              $user_asset =  UserAsset::where([
                  "id" => $id,

              ])
                  ->first();
              if (!$user_asset) {
                  return response()->json([
                      "message" => "no data found"
                  ], 404);
              }

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
              $business_id =  $request->user()->business_id;
              $idsArray = explode(',', $ids);
              $existingIds = UserAsset::whereIn('id', $idsArray)
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
              UserAsset::destroy($existingIds);


              return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
          } catch (Exception $e) {

              return $this->sendError($e, 500, $request);
          }
      }
}
