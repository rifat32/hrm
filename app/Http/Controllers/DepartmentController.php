<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentCreateRequest;
use App\Http\Requests\DepartmentUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Department;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentController extends Controller
{
    use ErrorUtil,UserActivityUtil;
     /**
     *
  * @OA\Post(
  *      path="/v1.0/departments",
  *      operationId="createDepartment",
  *      tags={"administrator.department"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to store department",
  *      description="This method is to store department",
  *
  *  @OA\RequestBody(
  *           description=" product type be single or variable",
  *         required=true,
  *         @OA\JsonContent(
  *            required={},
  *    @OA\Property(property="type", type="string", format="string",example="single"),
  *  *    @OA\Property(property="name", type="string", format="string",example="gear"),
  *    @OA\Property(property="description", type="string", format="string",example="car description"),
   *    @OA\Property(property="shop_id", type="number", format="number",example="1"),
   *   *    @OA\Property(property="product_category_id", type="number", format="number",example="1"),
   *
   *    *   *    @OA\Property(property="sku", type="string", format="string",example="car 123"),
   *  *    @OA\Property(property="image", type="string", format="string",example="/abcd/efgh"),
   *  *    @OA\Property(property="images", type="string", format="array",example={"/f.png","/g.jpeg"}),
   *  *    @OA\Property(property="price", type="number", format="number",example="10"),
   *  *    @OA\Property(property="quantity", type="number", format="number",example="20"),
   *
   *    *  *    @OA\Property(property="product_variations", type="string", format="array",example={
   *
   * {
   * "automobile_make_id":1,
   * "price":10,
   * "quantity":30
   * },
   *  * {
   * "automobile_make_id":2,
   * "price":20,
   * "quantity":30
   * },
   *
   *
   *
   * }),


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

 public function createDepartment(DepartmentCreateRequest $request)
 {
     try{
        $this->storeActivity($request,"");
        return DB::transaction(function () use ($request) {
            if(!$request->user()->hasPermissionTo('department_create')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           $insertableData = $request->validated();
           $department =  Department::create($insertableData);
           return response($department, 201);
        });


     } catch(Exception $e){
         error_log($e->getMessage());
     return $this->sendError($e,500,$request);
     }
 }

/**
     *
  * @OA\Put(
  *      path="/v1.0/departments",
  *      operationId="updateDepartment",
  *      tags={"administrator.department"},
 *       security={
  *           {"bearerAuth": {}}
  *       },
  *      summary="This method is to update department ",
  *      description="This method is to update department",
  *
  *  @OA\RequestBody(
  *         required=true,
  *         @OA\JsonContent(
  *            required={},
    *    @OA\Property(property="id", type="number", format="number",example="1"),
    *    @OA\Property(property="type", type="string", format="string",example="single"),
  *  *    @OA\Property(property="name", type="string", format="string",example="gear"),
  *    @OA\Property(property="description", type="string", format="string",example="car description"),
   *    @OA\Property(property="shop_id", type="number", format="number",example="1"),
   * *   *    @OA\Property(property="product_category_id", type="number", format="number",example="1"),
   *   *    @OA\Property(property="sku", type="string", format="string",example="car 123"),
   *  *    @OA\Property(property="image", type="string", format="string",example="/abcd/efgh"),
   *  *    @OA\Property(property="images", type="string", format="array",example={"/f.png","/g.jpeg"}),
   *  *    @OA\Property(property="price", type="number", format="number",example="10"),
   *  *    @OA\Property(property="quantity", type="number", format="number",example="20"),
   *
   *    *  *    @OA\Property(property="product_variations", type="string", format="array",example={
   *
   * {
   * "id":1,
   * "automobile_make_id":1,
   * "price":10,
   * "quantity":30
   * },
   *  * {
   * * "id":2,
   * "automobile_make_id":2,
   * "price":20,
   * "quantity":30
   * },
   **  * {
   * *
   * "automobile_make_id":3,
   * "price":30,
   * "quantity":30
   * }
   *
   *
   * }),

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

  public function updateDepartment(DepartmentUpdateRequest $request)
  {

      try{
        $this->storeActivity($request,"");
        return DB::transaction(function () use ($request) {
            if(!$request->user()->hasPermissionTo('department_update')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           $business_id =  $request->user()->business_id;
            $updatableData = $request->validated();

        $department_query_params =   [
            "id" => $updatableData["id"],
            "business_id" => $business_id
        ];

        $department_prev = Department::where($department_query_params)
        ->first();
        if(!$department_prev) {
            return response()->json([
                "message" => "no department found"
            ],404);
        }

                $department  =  tap(Department::where($department_query_params))->update(collect($updatableData)->only([
                  "name",
                  "sku",
                  "description",
                  "image",
                  // "is_active",
                  "is_default",
                  "product_category_id",
                //   "shop_id"

                ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                    if(!$department) {
                       return response()->json([
                           "message" => "something went wrong."
                       ],500);
                   }

            return response($department, 201);
        });


      } catch(Exception $e){
          error_log($e->getMessage());
      return $this->sendError($e,500,$request);
      }
  }


/**
     *
  * @OA\Get(
  *      path="/v1.0/departments",
  *      operationId="getDepartments",
  *      tags={"administrator.department"},
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
* name="product_category_id",
* in="query",
* description="product_category_id",
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


  *      summary="This method is to get departments  ",
  *      description="This method is to get departments ",
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

  public function getDepartments(Request $request) {
    try{
        $this->storeActivity($request,"");
        if(!$request->user()->hasPermissionTo('department_view')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }
       $business_id =  $request->user()->business_id;
       $departmentsQuery = Department::where(
        [
            "business_id" => $business_id
        ]
       )
       ->when(!empty($request->search_key), function ($query) use ($request) {
        return $query->where(function($query) use ($request) {
            $term = $request->search_key;
            $query->where("name", "like", "%" . $term . "%")
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
           return $query->where('created_at', "<=", $request->end_date);
       });

   if (!empty($request->per_page)) {
       $departments = $departmentsQuery->paginate($request->per_page);
   } else {
       $departments = $departmentsQuery->get();
   }

        return response()->json($departments, 200);
    } catch(Exception $e){

    return $this->sendError($e,500,$request);
    }
}

 /**
     *
  * @OA\Get(
  *      path="/v1.0/products/single/get/{id}",
  *      operationId="getProductById",
  *      tags={"administrator.department"},
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
  *      summary="This method is to get Product by id",
  *      description="This method is to get Product by id",
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


  public function getProductById($id,Request $request) {
    try{
        $this->storeActivity($request,"");
        if(!$request->user()->hasPermissionTo('product_view')){
            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }

        $product =  Product::where([
            "id" => $id
        ])
        ->first()
        ;
        if(!$product) {
return response()->json([
   "message" => "no product found"
],404);
        }

        return response()->json($product, 200);
    } catch(Exception $e){

    return $this->sendError($e,500,$request);
    }
}



/**
        *
     *     @OA\Delete(
     *      path="/v1.0/products/{id}",
     *      operationId="deleteProductById",
     *      tags={"administrator.department"},
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
     *      summary="This method is to delete product by id",
     *      description="This method is to delete product by id",
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

    public function deleteProductById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('product_delete')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           Product::where([
            "id" => $id
           ])
           ->delete();

            return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }




}
