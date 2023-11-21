<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Http\Requests\RoleUpdateRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use Exception;
use Illuminate\Http\Request;
use App\Models\Role;

class RolesController extends Controller
{
    use ErrorUtil,UserActivityUtil;
     /**
        *
     * @OA\Post(
     *      path="/v1.0/roles",
     *      operationId="createRole",
     *      tags={"user_management.role"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store role",
     *      description="This method is to store role",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"name","permissions"},
     *             @OA\Property(property="name", type="string", format="string",example="Rifat"),
     *            @OA\Property(property="permissions", type="string", format="array",example={"user_create","user_update"}),
     * *            @OA\Property(property="is_default_for_business", type="boolean", format="boolean",example="1"),

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
    public function createRole(RoleRequest $request)
    {
        try{
            $this->storeActivity($request,"");
            if( !$request->user()->hasPermissionTo('role_create'))
            {

               return response()->json([
                  "message" => "You can not perform this action"
               ],401);
          }
           $insertableData = $request->validated();
           $insertableRole = [
            "name" => $insertableData["name"],
            "guard_name" => "api",
           ];

           if($request->user()->hasRole('superadmin') || $request->user()->hasRole('reseller') )
           {
            $insertableRole["business_id"] = NULL;
            $insertableRole["is_default"] = 1;
         } else {
            $insertableRole["business_id"] = $request->user()->business_id;
            $insertableRole["is_default"] = 0;
            $insertableRole["is_default_for_business"] = 0;

         }
           $role = Role::create($insertableRole);
           $role->syncPermissions($insertableData["permissions"]);



           return response()->json([
               "role" =>  $role,
           ], 201);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }





    }
  /**
        *
     * @OA\Put(
     *      path="/v1.0/roles",
     *      operationId="updateRole",
     *      tags={"user_management.role"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update role",
     *      description="This method is to update role",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","permissions"},
     *             @OA\Property(property="id", type="number", format="number",example="1"),
     *            @OA\Property(property="permissions", type="string", format="array",example={"user_create","user_update"}),
     *  *            @OA\Property(property="description", type="string", format="string", example="description"),
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
    public function updateRole(RoleUpdateRequest $request) {
        try{
            $this->storeActivity($request,"");
        if( !$request->user()->hasPermissionTo('role_update') )
        {

           return response()->json([
              "message" => "You can not perform this action"
           ],401);
      }
        $request_data = $request->validated();

        $role = Role::where(["id" => $request_data["id"]])
        ->when(($request->user()->hasRole('superadmin') || $request->user()->hasRole('reseller')), function ($query) use ($request) {
            return $query->where('business_id', NULL)->where('is_default', 1);
        })
        ->when(!($request->user()->hasRole('superadmin') || $request->user()->hasRole('reseller')), function ($query) use ($request) {
            // return $query->where('business_id', $request->user()->business_id)->where('is_default', 0);
            return $query->where('business_id', $request->user()->business_id);
        })
        ->first();

        if(!$role)
        {
           return response()->json([
              "message" => "No role found"
           ],404);
      }
        if($role->name == "superadmin" )
        {
           return response()->json([
              "message" => "You can not perform this action"
           ],401);
      }

        $role->description = $request_data['description'];
        $role->save();
        $role->syncPermissions($request_data["permissions"]);


        return response()->json([
            "role" =>  $role,
        ], 201);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }


    }
    /**
        *
     * @OA\Get(
     *      path="/v1.0/roles",
     *      operationId="getRoles",
     *      tags={"user_management.role"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get roles",
     *      description="This method is to get roles",
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
    public function getRoles(Request $request)
    {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('role_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }

           $roles = Role::with('permissions:name,id')
           ->when(($request->user()->hasRole('superadmin') || $request->user()->hasRole('reseller')), function ($query) use ($request) {
            return $query->where('business_id', NULL)->where('is_default', 1);
        })
        ->when(!($request->user()->hasRole('superadmin') || $request->user()->hasRole('reseller')), function ($query) use ($request) {
            return $query->where('business_id', $request->user()->business_id);
        })
           ->when(!empty($request->search_key), function ($query) use ($request) {
               $term = $request->search_key;
               $query->where("name", "like", "%" . $term . "%");
           })
           ->when(!empty($request->start_date), function ($query) use ($request) {
            return $query->where('created_at', ">=", $request->start_date);
        })
        ->when(!empty($request->end_date), function ($query) use ($request) {
            return $query->where('created_at', "<=", $request->end_date);
        })
        ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
            return $query->orderBy("id", $request->order_by);
        }, function ($query) {
            return $query->orderBy("id", "DESC");
        })
        ->when(!empty($request->per_page), function ($query) use ($request) {
            return $query->paginate($request->per_page);
        }, function ($query) {
            return $query->get();
        });
            return response()->json($roles, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }


    }


        /**
        *
     * @OA\Get(
     *      path="/v1.0/roles/{id}",
     *      operationId="getRoleById",
     *      tags={"user_management.role"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get role by id",
     *      description="This method is to get role by id",
     *
    *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
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
    public function getRoleById($id,Request $request) {

        try{
            $this->storeActivity($request,"");
            $role = Role::with('permissions:name,id')
            ->where(["id" => $id])
            ->select("name", "id")->get();
            return response()->json($role, 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }

    }

    /**
    *
     * @OA\Delete(
     *      path="/v1.0/roles/{ids}",
     *      operationId="deleteRolesByIds",
     *      tags={"user_management.role"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to delete role by id",
     *      description="This method is to delete role by id",
     *
    *              @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         description="ids",
     *         required=true,
     *  example="1,2,3"
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
    public function deleteRolesByIds($ids,Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('role_delete'))
            {

            return response()->json([
               "message" => "You can not perform this action"
            ],401);
       }

            $idsArray = explode(',', $ids);
            $existingIds = Role::whereIn('id', $idsArray)
            ->where("is_system_default", "!=", 1)
            ->when(($request->user()->hasRole('superadmin') || $request->user()->hasRole('reseller')), function ($query) use ($request) {
                return $query->where('business_id', NULL)->where('is_default', 1);
            })
            ->when(!($request->user()->hasRole('superadmin') || $request->user()->hasRole('reseller')), function ($query) use ($request) {
                return $query->where('business_id', $request->user()->business_id)->where('is_default', 0);
            })


                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {
                return response()->json([
                    "message" => "Some or all of the data they can not be deleted or not exists."
                ], 404);
            }




            Role::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);









             return response()->json(["ok" => true], 200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }




    }


   /**
    *
     * @OA\Get(
     *      path="/v1.0/initial-role-permissions",
     *      operationId="getInitialRolePermissions",
     *      tags={"user_management.role"},
    *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to get initioal role permissions",
     *      description="This method is to get initioal role permissions",
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

    public function getInitialRolePermissions (Request $request) {

        try{
            $this->storeActivity($request,"");
            if(!$request->user()->hasPermissionTo('role_view')){
                return response()->json([
                   "message" => "You can not perform this action"
                ],401);
           }
           $role_permissions_main = config("setup-config.roles_permission");
           $role_permissions_json = json_decode(json_encode($role_permissions_main),true);
           $unchangeable_roles = config("setup-config.unchangeable_roles");
           $unchangeable_permissions = config("setup-config.unchangeable_permissions");
           $permissions_titles = config("setup-config.permissions_titles");

           foreach ($role_permissions_json as $key => $roleAndPermissions) {
            if(in_array($roleAndPermissions["role"], $unchangeable_roles)){
                array_splice($role_permissions_main, $key, 1);
            }

            if(!($request->user()->hasRole('superadmin') || $request->user()->hasRole('reseller')) )
            {
               if($roleAndPermissions["role"] == "superadmin" || $roleAndPermissions["role"] == "reseller") {
                array_splice($role_permissions_main, $key, 1);
               }
           }
           if(!($request->user()->hasRole('superadmin')) )
            {
               if($roleAndPermissions["role"] == "superadmin") {
                array_splice($role_permissions_main, $key, 1);
               }
           }

                foreach ($roleAndPermissions["permissions"] as $key2 => $permission) {
                    if(in_array($permission, $unchangeable_permissions)){
                        if(!empty($role_permissions_main[$key]["permissions"])) {
                            array_splice($role_permissions_main[$key]["permissions"], $key2, 1);
                        }

                    }else {
                        $role_permissions_main[$key]["permissions"][$key2] = [] ;
                        $role_permissions_main[$key]["permissions"][$key2]["name"] = $permission;
                        $role_permissions_main[$key]["permissions"][$key2]["title"] = $permissions_titles[$permission];
                    }


                }





        }

           return response()->json($role_permissions_main,200);
        } catch(Exception $e){

        return $this->sendError($e,500,$request);
        }



    }

}
