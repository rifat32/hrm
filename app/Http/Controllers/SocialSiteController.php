<?php

namespace App\Http\Controllers;

use App\Http\Requests\SocialSiteCreateRequest;
use App\Http\Requests\SocialSiteUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\SocialSite;
use App\Models\UserSocialSite;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocialSiteController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/social-sites",
     *      operationId="createSocialSite",
     *      tags={"social_sites"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store social site",
     *      description="This method is to store social site ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
 * @OA\Property(property="name", type="string", format="string", example="tttttt"),
 * @OA\Property(property="icon", type="string", format="string", example="erg ear ga&nbsp;"),
 *  * @OA\Property(property="link", type="string", format="string", example="erg ear ga&nbsp;")
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

    public function createSocialSite(SocialSiteCreateRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('social_site_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                $request_data = $request->validated();



                $request_data["business_id"] = NULL;
                $request_data["is_active"] = 1;
                $request_data["is_default"] = 1;

                $request_data["created_by"] = $request->user()->id;

                // if ($request->user()->hasRole('superadmin')) {
                //     $request_data["business_id"] = NULL;
                // $request_data["is_active"] = 1;
                // $request_data["is_default"] = 1;
                // $request_data["created_by"] = $request->user()->id;
                // }
                // else {
                //     $request_data["business_id"] = $request->user()->business_id;
                //     $request_data["is_active"] = 1;
                //     $request_data["is_default"] = 0;
                //     // $request_data["created_by"] = $request->user()->id;
                // }




                $social_site =  SocialSite::create($request_data);




                return response($social_site, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/social-sites",
     *      operationId="updateSocialSite",
     *      tags={"social_sites"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update social site  ",
     *      description="This method is to update social site ",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
*      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
 * @OA\Property(property="name", type="string", format="string", example="tttttt"),
 * @OA\Property(property="icon", type="string", format="string", example="erg ear ga&nbsp;"),
 *  * @OA\Property(property="link", type="string", format="string", example="erg ear ga&nbsp;")


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

    public function updateSocialSite(SocialSiteUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('social_site_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                // $business_id =  $request->user()->business_id;
                $request_data = $request->validated();



                $social_site_query_params = [
                    "id" => $request_data["id"],
                    // "business_id" => $business_id
                ];
                $social_site_prev = SocialSite::where($social_site_query_params)
                    ->first();
                if (!$social_site_prev) {
                    return response()->json([
                        "message" => "no social site  found"
                    ], 404);
                }

                // if ($request->user()->hasRole('superadmin')) {
                //     if(!($social_site_prev->business_id == NULL && $social_site_prev->is_default == 1)) {
                //         return response()->json([
                //             "message" => "You do not have permission to update this social site  due to role restrictions."
                //         ], 403);
                //     }

                // }
                // else {
                //     if(!($social_site_prev->business_id == $request->user()->business_id)) {
                //         return response()->json([
                //             "message" => "You do not have permission to update this social site due to role restrictions."
                //         ], 403);
                //     }
                // }
                $social_site  =  tap(SocialSite::where($social_site_query_params))->update(
                    collect($request_data)->only([
                        'name',
                        'icon',
                        'link',
                        // "is_active",
                        // "is_default",
                        // "business_id",
                        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$social_site) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }


                return response($social_site, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/social-sites",
     *      operationId="getSocialSites",
     *      tags={"social_sites"},
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
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get social sites  ",
     *      description="This method is to get social sites ",
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

    public function getSocialSites(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('social_site_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }


            $social_sites = SocialSite::when(!empty($request->search_key), function ($query) use ($request) {
                return $query->where(function ($query) use ($request) {
                    $term = $request->search_key;
                    $query->where("social_sites.name", "like", "%" . $term . "%")
                        ->orWhere("social_sites.description", "like", "%" . $term . "%");
                });
            })

            //     when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('social_sites.business_id', NULL)
            //                  ->where('social_sites.is_default', 1);
            // })
            // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('social_sites.business_id', $request->user()->business_id);
            // })


                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('social_sites.created_at', ">=", Carbon::createFromFormat('d-m-Y', ($request->start_date)));
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('social_sites.created_at', "<=", Carbon::createFromFormat('d-m-Y', ($request->end_date . ' 23:59:59'))->format('Y-m-d'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("social_sites.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("social_sites.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });;



            return response()->json($social_sites, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/social-sites/{id}",
     *      operationId="getSocialSiteById",
     *      tags={"social_sites"},
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
     *      summary="This method is to get social site by id",
     *      description="This method is to get social site by id",
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


    public function getSocialSiteById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('social_site_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $social_site =  SocialSite::where([
                "id" => $id,
            ])
            // ->when($request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('social_sites.business_id', NULL)
            //                  ->where('social_sites.is_default', 1);
            // })
            // ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
            //     return $query->where('social_sites.business_id', $request->user()->business_id);
            // })
                ->first();
            if (!$social_site) {
                return response()->json([
                    "message" => "no data found"
                ], 404);
            }

            return response()->json($social_site, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/social-sites/{ids}",
     *      operationId="deleteSocialSitesByIds",
     *      tags={"social_sites"},
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
     *      summary="This method is to delete social site by id",
     *      description="This method is to delete social site by id",
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

    public function deleteSocialSitesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('social_site_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $idsArray = explode(',', $ids);
            $existingIds = SocialSite::whereIn('id', $idsArray)
            ->when(empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('social_sites.business_id', NULL)
                             ->where('social_sites.is_default', 1);
            })
            ->when(!empty($request->user()->business_id), function ($query) use ($request) {
                return $query->where('social_sites.business_id', $request->user()->business_id)
                ->where('social_sites.is_default', 0);
            })
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

           $employee_social_site_exists =  UserSocialSite::whereIn("social_site_id", $existingIds)->exists();
            if ($employee_social_site_exists) {
                // $conflictingSocialSites = UserSocialSite::whereIn("social_site_id", $existingIds)->get([
                //     'id',
                // ]);
                return response()->json([
                    "message" => "Some user's are using some of these social sites.",
                    // "conflicting_users" => $conflictingSocialSites
                ], 409);
            }

            SocialSite::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully","deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
