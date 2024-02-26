<?php

namespace App\Http\Controllers;

use App\Http\Requests\AuthRegisterBusinessRequest;
use App\Http\Requests\BusinessCreateRequest;

use App\Http\Requests\BusinessUpdateRequest;
use App\Http\Requests\BusinessUpdateSeparateRequest;
use App\Http\Requests\CheckScheduleConflictRequest;
use App\Http\Requests\ImageUploadRequest;
use App\Http\Requests\MultipleImageUploadRequest;
use App\Http\Requests\GetIdRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\UserActivityUtil;
use App\Mail\SendPassword;

use App\Models\Business;
use App\Models\BusinessTime;
use App\Models\User;
use App\Models\WorkShift;
use App\Models\WorkShiftHistory;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;


class BusinessController extends Controller
{
    use ErrorUtil, BusinessUtil, UserActivityUtil;


    /**
     *
     * @OA\Post(
     *      path="/v1.0/business-image",
     *      operationId="createBusinessImage",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store business image ",
     *      description="This method is to store business image",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"image"},
     *         @OA\Property(
     *             description="image to upload",
     *             property="image",
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

    public function createBusinessImage(ImageUploadRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            // if(!$request->user()->hasPermissionTo('business_create')){
            //      return response()->json([
            //         "message" => "You can not perform this action"
            //      ],401);
            // }

            $request_data = $request->validated();

            $location =  config("setup-config.business_gallery_location");

            $new_file_name = time() . '_' . str_replace(' ', '_', $request_data["image"]->getClientOriginalName());

            $request_data["image"]->move(public_path($location), $new_file_name);


            return response()->json(["image" => $new_file_name, "location" => $location, "full_location" => ("/" . $location . "/" . $new_file_name)], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/business-image-multiple",
     *      operationId="createBusinessImageMultiple",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to store business gallery",
     *      description="This method is to store business gallery",
     *
     *  @OA\RequestBody(
     *   * @OA\MediaType(
     *     mediaType="multipart/form-data",
     *     @OA\Schema(
     *         required={"images[]"},
     *         @OA\Property(
     *             description="array of images to upload",
     *             property="images[]",
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

    public function createBusinessImageMultiple(MultipleImageUploadRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");

            $request_data = $request->validated();

            $location =  config("setup-config.business_gallery_location");

            $images = [];
            if (!empty($request_data["images"])) {
                foreach ($request_data["images"] as $image) {
                    $new_file_name = time() . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->move(public_path($location), $new_file_name);

                    array_push($images, ("/" . $location . "/" . $new_file_name));
                }
            }


            return response()->json(["images" => $images], 201);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Post(
     *      path="/v1.0/businesses",
     *      operationId="createBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store business",
     *      description="This method is to store  business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *  "owner_id":"1",
     *  "pension_scheme_registered" : 1,
     *   "pension_scheme_name" : "hh",
     *   "pension_scheme_letters" : {{"file" :"vv.jpg"}},
     * * "start_date":"start_date",
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"}
     *
     * }),
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
    public function createBusiness(BusinessCreateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return  DB::transaction(function () use (&$request) {
                if (!$request->user()->hasPermissionTo('business_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();

                $user = User::where([
                    "id" =>  $request_data['business']['owner_id']
                ])
                    ->first();

                if (!$user) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["owner_id" => ["No User Found"]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }

                if (!$user->hasRole('business_owner')) {
                    $error =  [
                        "message" => "The given data was invalid.",
                        "errors" => ["owner_id" => ["The user is not a businesses Owner"]]
                    ];
                    throw new Exception(json_encode($error), 422);
                }



                $request_data['business']['status'] = "pending";

                $request_data['business']['created_by'] = $request->user()->id;
                $request_data['business']['is_active'] = true;
                $business =  Business::create($request_data['business']);



                $this->storeDefaultsToBusiness($business->id, $business->name, $business->owner_id, $business->address_line_1, $business);










                return response([

                    "business" => $business
                ], 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Post(
     *      path="/v1.0/auth/check-schedule-conflict",
     *      operationId="checkScheduleConflict",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user with business",
     *      description="This method is to store user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *
     *      @OA\Property(property="times", type="string", format="array",example={
     *
     *{"day":0,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":1,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":2,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":3,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":4,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":5,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":6,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true}
     *
     * }),
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
    public function checkScheduleConflict(CheckScheduleConflictRequest $request)
    {


        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return  DB::transaction(function () use (&$request) {

                //     if(!$request->user()->hasPermissionTo('business_create')){
                //         return response()->json([
                //            "message" => "You can not perform this action"
                //         ],401);
                //    }
                $request_data = $request->validated();

                $conflicted_work_shift_ids = collect();

                $timesArray = collect($request_data["times"])->unique("day");
                foreach ($timesArray as $business_time) {
                    $work_shift_ids = WorkShift::where([
                        "business_id" => auth()->user()->business_id
                    ])
                        ->whereHas('details', function ($query) use ($business_time) {
                            $query->where('work_shift_details.day', ($business_time["day"]))
                                ->when(!empty($time["is_weekend"]), function ($query) {
                                    $query->where('work_shift_details.is_weekend', 1);
                                })
                                ->where(function ($query) use ($business_time) {
                                    $query->whereTime('work_shift_details.start_at', '<=', ($business_time["start_at"]))
                                        ->orWhereTime('work_shift_details.end_at', '>=', ($business_time["end_at"]));
                                });
                        })
                        ->pluck("id");
                    $conflicted_work_shift_ids = $conflicted_work_shift_ids->merge($work_shift_ids);
                }
                $conflicted_work_shift_ids = $conflicted_work_shift_ids->unique()->values()->all();

                $conflicted_work_shifts =   WorkShift::whereIn("id", $conflicted_work_shift_ids)->get();

                return response([
                    "conflicted_work_shifts" => $conflicted_work_shifts
                ], 200);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }







    /**
     *
     * @OA\Post(
     *      path="/v1.0/auth/register-with-business",
     *      operationId="registerUserWithBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user with business",
     *      description="This method is to store user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     * "first_Name":"Rifat",
     * "last_Name":"Al-Ashwad",
     * "middle_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "send_password":1,
     * "gender":"male"
     *
     *
     * }),
     *
     *   *      *    @OA\Property(property="times", type="string", format="array",example={
     *
     *{"day":0,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":1,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":2,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":3,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":4,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":5,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":6,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true}
     *
     * }),
     *
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   "pension_scheme_registered" : 1,
     *   "pension_scheme_name" : "hh",
     *   "pension_scheme_letters" : {{"file" :"vv.jpg"}},
     * "name":"ABCD businesses",
     * "start_date":"start_date",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"}
     *
     * }),
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
    public function registerUserWithBusiness(AuthRegisterBusinessRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return  DB::transaction(function () use (&$request) {

                if (!$request->user()->hasPermissionTo('business_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();



                // user info starts ##############

                $password = $request_data['user']['password'];
                $request_data['user']['password'] = Hash::make($password);


                //    if(!$request->user()->hasRole('superadmin') || empty($request_data['user']['password'])) {
                //     $password = Str::random(10);
                //     $request_data['user']['password'] = Hash::make($password);
                //     }


                $request_data['user']['remember_token'] = Str::random(10);
                $request_data['user']['is_active'] = true;
                $request_data['user']['created_by'] = $request->user()->id;

                $request_data['user']['address_line_1'] = $request_data['business']['address_line_1'];
                $request_data['user']['address_line_2'] = (!empty($request_data['business']['address_line_2']) ? $request_data['business']['address_line_2'] : "");
                $request_data['user']['country'] = $request_data['business']['country'];
                $request_data['user']['city'] = $request_data['business']['city'];
                $request_data['user']['postcode'] = $request_data['business']['postcode'];
                $request_data['user']['lat'] = $request_data['business']['lat'];
                $request_data['user']['long'] = $request_data['business']['long'];

                $user =  User::create($request_data['user']);

                $user->assignRole('business_owner');
                // end user info ##############


                //  business info ##############

                $request_data['business']['status'] = "pending";
                $request_data['business']['owner_id'] = $user->id;
                $request_data['business']['created_by'] = $request->user()->id;
                $request_data['business']['is_active'] = true;


                $business =  Business::create($request_data['business']);

                $user->email_verified_at = now();
                $user->business_id = $business->id;


                $token = Str::random(30);
                $user->resetPasswordToken = $token;
                $user->resetPasswordExpires = Carbon::now()->subDays(-1);

                $user->save();

                BusinessTime::where([
                    "business_id" => $business->id
                ])
                    ->delete();
                $timesArray = collect($request_data["times"])->unique("day");
                foreach ($timesArray as $business_time) {
                    BusinessTime::create([
                        "business_id" => $business->id,
                        "day" => $business_time["day"],
                        "start_at" => $business_time["start_at"],
                        "end_at" => $business_time["end_at"],
                        "is_weekend" => $business_time["is_weekend"],
                    ]);
                }

                $this->storeDefaultsToBusiness($business->id, $business->name, $business->owner_id, $business->address_line_1, $business);



                //  if($request_data['user']['send_password']) {



                if (env("SEND_EMAIL") == true) {
                    Mail::to($request_data['user']['email'])->send(new SendPassword($user, $password));
                }




                // }

                return response([
                    "user" => $user,
                    "business" => $business
                ], 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



      /**
     *
     * @OA\Post(
     *      path="/v1.0/client/auth/register-with-business",
     *      operationId="registerUserWithBusinessClient",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store user with business",
     *      description="This method is to store user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     * "first_Name":"Rifat",
     * "last_Name":"Al-Ashwad",
     * "middle_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "send_password":1,
     * "gender":"male"
     *
     *
     * }),
     *
     *  @OA\Property(property="times", type="string", format="array",example={
     *
     *{"day":0,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":1,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":2,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":3,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":4,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":5,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true},
     *{"day":6,"start_at":"10:10:00","end_at":"10:15:00","is_weekend":true}
     *
     * }),
     *
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   "pension_scheme_registered" : 1,
     *   "pension_scheme_name" : "hh",
     *   "pension_scheme_letters" : {{"file" :"vv.jpg"}},
     * "name":"ABCD businesses",
     * "start_date":"start_date",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  * "currency":"BDT",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",

     *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"}
     *
     * }),
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
    public function registerUserWithBusinessClient(AuthRegisterBusinessRequest $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return  DB::transaction(function () use (&$request) {


                $request_data = $request->validated();
                // user info starts ##############

                $password = $request_data['user']['password'];
                $request_data['user']['password'] = Hash::make($password);


                //    if(!$request->user()->hasRole('superadmin') || empty($request_data['user']['password'])) {
                //     $password = Str::random(10);
                //     $request_data['user']['password'] = Hash::make($password);
                //     }


                $request_data['user']['remember_token'] = Str::random(10);
                $request_data['user']['is_active'] = true;


                $request_data['user']['address_line_1'] = $request_data['business']['address_line_1'];
                $request_data['user']['address_line_2'] = (!empty($request_data['business']['address_line_2']) ? $request_data['business']['address_line_2'] : "");
                $request_data['user']['country'] = $request_data['business']['country'];
                $request_data['user']['city'] = $request_data['business']['city'];
                $request_data['user']['postcode'] = $request_data['business']['postcode'];
                $request_data['user']['lat'] = $request_data['business']['lat'];
                $request_data['user']['long'] = $request_data['business']['long'];

                $user =  User::create($request_data['user']);
                Auth::login($user);

                $user->assignRole('business_owner');
                // end user info ##############


                //  business info ##############
                $request_data['business']['status'] = "pending";
                $request_data['business']['owner_id'] = $user->id;
                $request_data['business']['created_by'] = $user->id;
                $request_data['business']['is_active'] = false;


                $business =  Business::create($request_data['business']);


                $user->email_verified_at = now();
                $user->business_id = $business->id;


                $token = Str::random(30);
                $user->resetPasswordToken = $token;
                $user->resetPasswordExpires = Carbon::now()->subDays(-1);
                $user->created_by = $user->id;

                $user->save();

                BusinessTime::where([
                    "business_id" => $business->id
                ])
                    ->delete();
                $timesArray = collect($request_data["times"])->unique("day");
                foreach ($timesArray as $business_time) {
                    BusinessTime::create([
                        "business_id" => $business->id,
                        "day" => $business_time["day"],
                        "start_at" => $business_time["start_at"],
                        "end_at" => $business_time["end_at"],
                        "is_weekend" => $business_time["is_weekend"],
                    ]);
                }

                $this->storeDefaultsToBusiness($business->id, $business->name, $business->owner_id, $business->address_line_1, $business);



                //  if($request_data['user']['send_password']) {



                if (env("SEND_EMAIL") == true) {
                    Mail::to($request_data['user']['email'])->send(new SendPassword($user, $password));
                }




                // }

                return response([
                    "user" => $user,
                    "business" => $business
                ], 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }




    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses",
     *      operationId="updateBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update user with business",
     *      description="This method is to update user with business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *             @OA\Property(property="user", type="string", format="array",example={
     *  * "id":1,
     * "first_Name":"Rifat",
     *  * "middle_Name":"Al-Ashwad",
     *
     * "last_Name":"Al-Ashwad",
     * "email":"rifatalashwad@gmail.com",
     *  "password":"12345678",
     *  "password_confirmation":"12345678",
     *  "phone":"01771034383",
     *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     * "gender":"male"
     *
     *
     * }),
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     *  "currency":"BDT"
     *
     * }),
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
    public function updateBusiness(BusinessUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return  DB::transaction(function () use (&$request) {
                if (!$request->user()->hasPermissionTo('business_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                if (!$this->businessOwnerCheck($request["business"]["id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the business or the requested business does not exist."
                    ], 401);
                }

                $request_data = $request->validated();
                //    user email check
                $userPrev = User::where([
                    "id" => $request_data["user"]["id"]
                ]);
                if (!$request->user()->hasRole('superadmin')) {
                    $userPrev  = $userPrev->where(function ($query) {
                        return  $query->where('created_by', auth()->user()->id)
                            ->orWhere('id', auth()->user()->id);
                    });
                }
                $userPrev = $userPrev->first();
                if (!$userPrev) {
                    $this->storeError(
                        "no data found",
                        404,
                        "front end error",
                        "front end error"
                    );
                    return response()->json([
                        "message" => "no user found with this id"
                    ], 404);
                }




                //  $businessPrev = Business::where([
                //     "id" => $request_data["business"]["id"]
                //  ]);

                // $businessPrev = $businessPrev->first();
                // if(!$businessPrev) {
                //     return response()->json([
                //        "message" => "no business found with this id"
                //     ],404);
                //   }

                if (!empty($request_data['user']['password'])) {
                    $request_data['user']['password'] = Hash::make($request_data['user']['password']);
                } else {
                    unset($request_data['user']['password']);
                }
                $request_data['user']['is_active'] = true;
                $request_data['user']['remember_token'] = Str::random(10);
                $request_data['user']['address_line_1'] = $request_data['business']['address_line_1'];
                $request_data['user']['address_line_2'] = $request_data['business']['address_line_2'];
                $request_data['user']['country'] = $request_data['business']['country'];
                $request_data['user']['city'] = $request_data['business']['city'];
                $request_data['user']['postcode'] = $request_data['business']['postcode'];
                $request_data['user']['lat'] = $request_data['business']['lat'];
                $request_data['user']['long'] = $request_data['business']['long'];
                $user  =  tap(User::where([
                    "id" => $request_data['user']["id"]
                ]))->update(
                    collect($request_data['user'])->only([
                        'first_Name',
                        'middle_Name',
                        'last_Name',
                        'phone',
                        'image',
                        'address_line_1',
                        'address_line_2',
                        'country',
                        'city',
                        'postcode',
                        'email',
                        'password',
                        "lat",
                        "long",
                        "gender"
                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$user) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                // $user->syncRoles(["business_owner"]);



                //  business info ##############
                // $request_data['business']['status'] = "pending";

                $business  =  tap(Business::where([
                    "id" => $request_data['business']["id"]
                ]))->update(
                    collect($request_data['business'])->only([
                        "name",
                        "about",
                        "web_page",
                        "phone",
                        "email",
                        "additional_information",
                        "address_line_1",
                        "address_line_2",
                        "lat",
                        "long",
                        "country",
                        "city",
                        "postcode",
                        "logo",
                        "image",
                        "status",
                        "background_image",
                        // "is_active",




                        "currency"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$business) {
                    return response()->json([
                        "massage" => "something went wrong"
                    ], 500);
                }


                // end business info ##############

                if (!empty($request_data["times"])) {

                    $timesArray = collect($request_data["times"])->unique("day");


                    $conflicted_work_shift_ids = collect();

                    foreach ($timesArray as $business_time) {
                        $work_shift_ids = WorkShift::where([
                            "business_id" => auth()->user()->business_id
                        ])
                            ->whereHas('details', function ($query) use ($business_time) {
                                $query->where('work_shift_details.day', ($business_time["day"]))
                                    ->when(!empty($time["is_weekend"]), function ($query) {
                                        $query->where('work_shift_details.is_weekend', 1);
                                    })
                                    ->where(function ($query) use ($business_time) {
                                        $query->whereTime('work_shift_details.start_at', '<=', ($business_time["start_at"]))
                                            ->orWhereTime('work_shift_details.end_at', '>=', ($business_time["end_at"]));
                                    });
                            })
                            ->pluck("id");
                        $conflicted_work_shift_ids = $conflicted_work_shift_ids->merge($work_shift_ids);
                    }
                    $conflicted_work_shift_ids = $conflicted_work_shift_ids->unique()->values()->all();

                    if (!empty($conflicted_work_shift_ids)) {
                        WorkShift::whereIn("id", $conflicted_work_shift_ids)->update([
                            "is_active" => 0
                        ]);

                        WorkShiftHistory::where([
                            "to_date" => NULL
                        ])
                            ->whereIn("work_shift_id", $conflicted_work_shift_ids)
                            ->update([
                                "to_date" => now()
                            ]);
                    }





                    BusinessTime::where([
                        "business_id" => $business->id
                    ])
                        ->delete();

                    $timesArray = collect($request_data["times"])->unique("day");
                    foreach ($timesArray as $business_time) {
                        BusinessTime::create([
                            "business_id" => $business->id,
                            "day" => $business_time["day"],
                            "start_at" => $business_time["start_at"],
                            "end_at" => $business_time["end_at"],
                            "is_weekend" => $business_time["is_weekend"],
                        ]);
                    }
                }




                return response([
                    "user" => $user,
                    "business" => $business
                ], 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Put(
     *      path="/v1.0/business-pension-information",
     *      operationId="updateBusinessPensionInformation",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update business pension information",
     *      description="This method is to update pension information",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"user","business"},
     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     *   "pension_scheme_registered" : 1,
     *   "pension_scheme_name" : "hh",
     *   "pension_scheme_letters" : {{"file" :"vv.jpg"}}
     *
     * }),
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
    public function updateBusinessPensionInformation(BusinessUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return  DB::transaction(function () use (&$request) {
                if (!$request->user()->hasPermissionTo('business_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }

                if (!$this->businessOwnerCheck($request["business"]["id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the business or the requested business does not exist."
                    ], 401);
                }

                $request_data = $request->validated();

                $business  =  tap(Business::where([
                    "id" => $request_data['business']["id"]
                ]))->update(
                    collect($request_data['business'])->only([
                        "pension_scheme_registered",
                        "pension_scheme_name",
                        "pension_scheme_letters",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$business) {
                    return response()->json([
                        "massage" => "something went wrong"
                    ], 500);
                }






                return response([

                    "business" => $business
                ], 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses/toggle-active",
     *      operationId="toggleActiveBusiness",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to toggle business",
     *      description="This method is to toggle business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"id","first_Name","last_Name","email","password","password_confirmation","phone","address_line_1","address_line_2","country","city","postcode","role"},
     *           @OA\Property(property="id", type="string", format="number",example="1"),
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

    public function toggleActiveBusiness(GetIdRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('business_update')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $request_data = $request->validated();

            $businessQuery  = Business::where(["id" => $request_data["id"]]);
            if (!auth()->user()->hasRole('superadmin')) {
                $businessQuery = $businessQuery->where(function ($query) {
                    return   $query->where('id', auth()->user()->business_id)
                        ->orWhere('created_by', auth()->user()->id)
                        ->orWhere('owner_id', auth()->user()->id);
                });
            }

            $business =  $businessQuery->first();


            if (!$business) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "no business found"
                ], 404);
            }


            $business->update([
                'is_active' => !$business->is_active
            ]);

            return response()->json(['message' => 'business status updated successfully'], 200);
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }





    /**
     *
     * @OA\Put(
     *      path="/v1.0/businesses/separate",
     *      operationId="updateBusinessSeparate",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update business",
     *      description="This method is to update business",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *            required={"business"},

     *
     *  @OA\Property(property="business", type="string", format="array",example={
     *   *  * "id":1,
     * "name":"ABCD businesses",
     * "about":"Best businesses in Dhaka",
     * "web_page":"https://www.facebook.com/",
     *  "phone":"01771034383",
     *  "email":"rifatalashwad@gmail.com",
     *  "phone":"01771034383",
     *  "additional_information":"No Additional Information",
     *  "address_line_1":"Dhaka",
     *  "address_line_2":"Dinajpur",
     *    * *  "lat":"23.704263332849386",
     *    * *  "long":"90.44707059805279",
     *
     *  "country":"Bangladesh",
     *  "city":"Dhaka",
     *  "postcode":"Dinajpur",
     *
     *  "logo":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *      *  *  "image":"https://images.unsplash.com/photo-1671410714831-969877d103b1?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=387&q=80",
     *  "images":{"/a","/b","/c"},
     * *  "currency":"BDT"
     *
     * }),
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
    public function updateBusinessSeparate(BusinessUpdateSeparateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            return  DB::transaction(function () use (&$request) {
                if (!$request->user()->hasPermissionTo('business_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                if (!$this->businessOwnerCheck($request["business"]["id"])) {
                    return response()->json([
                        "message" => "you are not the owner of the business or the requested business does not exist."
                    ], 401);
                }

                $request_data = $request->validated();


                //  business info ##############
                // $request_data['business']['status'] = "pending";

                $business  =  tap(Business::where([
                    "id" => $request_data['business']["id"]
                ]))->update(
                    collect($request_data['business'])->only([
                        "name",
                        "about",
                        "web_page",
                        "phone",
                        "email",
                        "additional_information",
                        "address_line_1",
                        "address_line_2",
                        "lat",
                        "long",
                        "country",
                        "city",
                        "postcode",
                        "logo",
                        "image",
                        "background_image",
                        "status",
                        // "is_active",



                        "currency",

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$business) {
                    $this->storeError(
                        "no data found",
                        404,
                        "front end error",
                        "front end error"
                    );
                    return response()->json([
                        "massage" => "no business found"
                    ], 404);
                }








                return response([
                    "business" => $business
                ], 201);
            });
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }



    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses",
     *      operationId="getBusinesses",
     *      tags={"business_management"},
     * *  @OA\Parameter(
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
     * name="country_code",
     * in="query",
     * description="country_code",
     * required=true,
     * example="country_code"
     * ),
     * *  @OA\Parameter(
     * name="address",
     * in="query",
     * description="address",
     * required=true,
     * example="address"
     * ),
     * *  @OA\Parameter(
     * name="city",
     * in="query",
     * description="city",
     * required=true,
     * example="city"
     * ),
     * *  @OA\Parameter(
     * name="start_lat",
     * in="query",
     * description="start_lat",
     * required=true,
     * example="3"
     * ),
     * *  @OA\Parameter(
     * name="end_lat",
     * in="query",
     * description="end_lat",
     * required=true,
     * example="2"
     * ),
     * *  @OA\Parameter(
     * name="start_long",
     * in="query",
     * description="start_long",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="end_long",
     * in="query",
     * description="end_long",
     * required=true,
     * example="4"
     * ),
     * *  @OA\Parameter(
     * name="per_page",
     * in="query",
     * description="per_page",
     * required=true,
     * example="10"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *
     *      summary="This method is to get businesses",
     *      description="This method is to get businesses",
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

    public function getBusinesses(Request $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $businesses = Business::with("owner")
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($request) {
                    return   $query->where(function ($query) {
                        return   $query->where('id', auth()->user()->business_id)
                            ->orWhere('created_by', auth()->user()->id)
                            ->orWhere('owner_id', auth()->user()->id);
                    });
                })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    $term = $request->search_key;
                    return $query->where(function ($query) use ($term) {
                        $query->where("name", "like", "%" . $term . "%")
                            ->orWhere("phone", "like", "%" . $term . "%")
                            ->orWhere("email", "like", "%" . $term . "%")
                            ->orWhere("city", "like", "%" . $term . "%")
                            ->orWhere("postcode", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('created_at', ">=", $request->start_date);
                })
                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->start_lat), function ($query) use ($request) {
                    return $query->where('lat', ">=", $request->start_lat);
                })
                ->when(!empty($request->end_lat), function ($query) use ($request) {
                    return $query->where('lat', "<=", $request->end_lat);
                })
                ->when(!empty($request->start_long), function ($query) use ($request) {
                    return $query->where('long', ">=", $request->start_long);
                })
                ->when(!empty($request->end_long), function ($query) use ($request) {
                    return $query->where('long', "<=", $request->end_long);
                })
                ->when(!empty($request->address), function ($query) use ($request) {
                    $term = $request->address;
                    return $query->where(function ($query) use ($term) {
                        $query->where("country", "like", "%" . $term . "%")
                            ->orWhere("city", "like", "%" . $term . "%");
                    });
                })
                ->when(!empty($request->country_code), function ($query) use ($request) {
                    return $query->orWhere("country", "like", "%" . $request->country_code . "%");
                })
                ->when(!empty($request->city), function ($query) use ($request) {
                    return $query->orWhere("city", "like", "%" . $request->city . "%");
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("businesses.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("businesses.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });

            return response()->json($businesses, 200);
            return response()->json($businesses, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses/{id}",
     *      operationId="getBusinessById",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="id",
     *         required=true,
     *  example="1"
     *      ),
     *      summary="This method is to get business by id",
     *      description="This method is to get business by id",
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

    public function getBusinessById($id, Request $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('business_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            if (!$this->businessOwnerCheck($id)) {
                return response()->json([
                    "message" => "you are not the owner of the business or the requested business does not exist."
                ], 401);
            }

            $business = Business::with(
                "owner",
                "times"
                // "default_work_shift.details"

            )->where([
                "id" => $id
            ])
                ->first();




            return response()->json($business, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Delete(
     *      path="/v1.0/businesses/{ids}",
     *      operationId="deleteBusinessesByIds",
     *      tags={"business_management"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="ids",
     *         in="path",
     *         description="ids",
     *         required=true,
     *  example="6,7,8"
     *      ),
     *      summary="This method is to delete business by id",
     *      description="This method is to delete business by id",
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

    public function deleteBusinessesByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasPermissionTo('business_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = Business::whereIn('id', $idsArray)
                ->when(!$request->user()->hasRole('superadmin'), function ($query) use ($business_id) {

                    return   $query->where(function ($query) {
                        return  $query->where('id', auth()->user()->business_id)
                            ->orWhere('created_by', auth()->user()->id)
                            ->orWhere('owner_id', auth()->user()->id);
                    });
                })
                ->select('id')
                ->get()
                ->pluck('id')
                ->toArray();
            $nonExistingIds = array_diff($idsArray, $existingIds);

            if (!empty($nonExistingIds)) {
                $this->storeError(
                    "no data found",
                    404,
                    "front end error",
                    "front end error"
                );
                return response()->json([
                    "message" => "Some or all of the specified data do not exist."
                ], 404);
            }
            Business::destroy($existingIds);
            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }







    /**
     *
     * @OA\Get(
     *      path="/v1.0/businesses/by-business-owner/all",
     *      operationId="getAllBusinessesByBusinessOwner",
     *      tags={"business_management"},

     *       security={
     *           {"bearerAuth": {}}
     *       },

     *      summary="This method is to get businesses",
     *      description="This method is to get businesses",
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

    public function getAllBusinessesByBusinessOwner(Request $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity", "DUMMY description");
            if (!$request->user()->hasRole('business_owner')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }

            $businessesQuery = Business::where([
                "owner_id" => $request->user()->id
            ]);



            $businesses = $businessesQuery->orderByDesc("id")->get();
            return response()->json($businesses, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
