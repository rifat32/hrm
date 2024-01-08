<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkShiftCreateRequest;
use App\Http\Requests\WorkShiftUpdateRequest;
use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\BusinessTime;
use App\Models\EmployeeWorkShiftHistory;
use App\Models\User;
use App\Models\UserWorkShift;
use App\Models\WorkShift;
use App\Models\WorkShiftDetail;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WorkShiftController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil;
    /**
     *
     * @OA\Post(
     *      path="/v1.0/work-shifts",
     *      operationId="createWorkShift",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store work shift",
     *      description="This method is to store work shift",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *     @OA\Property(property="type", type="string", format="string", example="regular"),
     *  *     @OA\Property(property="description", type="string", format="string", example="description"),
     *      *  *     @OA\Property(property="is_personal", type="boolean", format="boolean", example="0"),
     *
     *     @OA\Property(property="break_type", type="string", format="string", example="paid"),
     *  *     @OA\Property(property="break_hours", type="boolean", format="boolean", example="0"),
     *
     *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3}),

     *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
     * *     @OA\Property(property="details", type="string", format="array", example={
     *         {
     *             "day": "0",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "1",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "2",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "3",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "4",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "5",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "6",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         }
     *     }),

     *     @OA\Property(property="start_date", type="string", format="date", example="2023-11-16"),
     *     @OA\Property(property="end_date", type="string", format="date", example="2023-11-16"),
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

    public function createWorkShift(WorkShiftCreateRequest $request)
    {

        try {

            $this->storeActivity($request, "DUMMY activity","DUMMY description");

            return DB::transaction(function () use ($request) {
                if (!$request->user()->hasPermissionTo('work_shift_create')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }
                $request_data = $request->validated();

                foreach($request_data['details'] as $index => $detail) {
                 $business_time =   BusinessTime::where([
                        "business_id" => auth()->user()->business_id,
                        "day" => $detail["day"]
                    ])
                    ->first();
                    if(!$business_time) {
                    $error = [
                            "message" => "The given data was invalid.",
                            "errors" => [("details.".$index.".day")=>["no business time found on this day"]]
                     ];
                        throw new Exception(json_encode($error),422);
                    }

                    if($business_time->is_weekend == 1 && $detail["is_weekend"] != 1) {
                        $error = [
                                "message" => "The given data was invalid.",
                                "errors" => [("details.".$index.".is_weekend")=>["This is weekend day"]]
                         ];
                            throw new Exception(json_encode($error),422);
                     }


                     if(!empty($detail["start_at"]) && !empty($detail["end_at"] && !empty($business_time->start_at) && !empty($business_time->end_at)) ) {

                    $request_start_at = Carbon::createFromFormat('H:i:s', $detail["start_at"]);
                    $request_end_at = Carbon::createFromFormat('H:i:s', $detail["end_at"]);
                    $business_start_at = Carbon::createFromFormat('H:i:s', $business_time->start_at);
                    $business_end_at = Carbon::createFromFormat('H:i:s', $business_time->end_at);


                    $difference_in_both_request  = $request_start_at->diffInHours($request_end_at);
                    $difference_in_both_start_at  = $business_start_at->diffInHours($request_start_at);
                    $difference_in_end_at_start_at  = $business_end_at->diffInHours($request_start_at);
                    $difference_in_both_end_at  = $business_end_at->diffInHours($business_end_at);
                    $difference_in_start_at_end_at  = $business_start_at->diffInHours($request_end_at);






                    if($difference_in_both_request < 0) {
                        $error = [
                            "message" => "The given data was invalid.",
                            "errors" => [
                                ("details.".$index.".end_at")=>["end at should be greater than start at"]

                                ]
                     ];
                        throw new Exception(json_encode($error),422);
                    }


                    if($difference_in_both_start_at < 0) {
                        $error = [
                            "message" => "The given data was invalid.",
                            "errors" => [ ("details.".$index.".start_at")=>["start at should be in business working time $difference_in_both_start_at"]]
                     ];
                        throw new Exception(json_encode($error),422);
                    }



                    if($difference_in_end_at_start_at < 0) {
                        $error = [
                            "message" => "The given data was invalid.",
                            "errors" => [ ("details.".$index.".start_at")=>["start at should be in business working time"]]
                     ];
                        throw new Exception(json_encode($error),422);
                    }


                    if($difference_in_both_end_at > 0) {
                        $error = [
                            "message" => "The given data was invalid.",
                            "errors" => [ ("details.".$index.".end_at")=>["end at should be in business working time"]]
                     ];
                        throw new Exception(json_encode($error),422);
                    }

                    if($difference_in_start_at_end_at < 0) {
                        $error = [
                            "message" => "The given data was invalid.",
                            "errors" => [ ("details.".$index.".end_at")=>["end at should be in business working time"]]
                     ];
                        throw new Exception(json_encode($error),422);
                    }
                     }





                }


                $request_data["business_id"] = $request->user()->business_id;
                $request_data["is_active"] = true;
                $request_data["created_by"] = $request->user()->id;
                $request_data["is_default"] = false;

                $request_data["attendances_count"] = 0;
                $work_shift =  WorkShift::create($request_data);

                $work_shift->departments()->sync($request_data['departments'], []);
                // $work_shift->users()->sync($request_data['users'], []);
                $work_shift->details()->createMany($request_data['details']);

                return response($work_shift, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Put(
     *      path="/v1.0/work-shifts",
     *      operationId="updateWorkShift",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to update work shift ",
     *      description="This method is to update work_shift",
     *
     *  @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *      @OA\Property(property="id", type="number", format="number", example="Updated Christmas"),
     *     @OA\Property(property="name", type="string", format="string", example="Updated Christmas"),
     *     @OA\Property(property="type", type="string", format="string", example="regular"),
     *     @OA\Property(property="description", type="string", format="string", example="description"),
     *    *      *  *     @OA\Property(property="is_personal", type="boolean", format="boolean", example="0"),
     *   *     @OA\Property(property="break_type", type="string", format="string", example="paid"),
     *  *     @OA\Property(property="break_hours", type="boolean", format="boolean", example="0"),
     *
     *     @OA\Property(property="departments", type="string",  format="array", example={1,2,3,4}),

     *     @OA\Property(property="users", type="string", format="array", example={1,2,3}),
     * *     @OA\Property(property="details", type="string", format="array", example={
     *         {
     *             "day": "0",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "1",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "2",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "3",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "4",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "5",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         },
     *         {
     *             "day": "6",
     *             "start_at": "",
     *             "end_at": "",
     *             "is_weekend": 0
     *         }
     *     }),

     *     @OA\Property(property="start_date", type="string", format="date", example="2023-11-16"),
     *     @OA\Property(property="end_date", type="string", format="date", example=""),
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

    public function updateWorkShift(WorkShiftUpdateRequest $request)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            return DB::transaction(function () use ($request) {

                if (!$request->user()->hasPermissionTo('work_shift_update')) {
                    return response()->json([
                        "message" => "You can not perform this action"
                    ], 401);
                }


                $request_data = $request->validated();

                foreach($request_data['details'] as $index => $detail) {
                    $business_time =   BusinessTime::where([
                           "business_id" => auth()->user()->business_id,
                           "day" => $detail["day"]
                       ])
                       ->first();
                       if(!$business_time) {
                       $error = [
                               "message" => "The given data was invalid.",
                               "errors" => [("details.".$index.".day")=>["no business time found on this day"]]
                        ];
                           throw new Exception(json_encode($error),422);
                       }


                       if($business_time->is_weekend == 1 && $detail["is_weekend"] != 1) {
                           $error = [
                                   "message" => "The given data was invalid.",
                                   "errors" => [("details.".$index.".is_weekend")=>["This is weekend day"]]
                            ];
                               throw new Exception(json_encode($error),422);
                        }

                        if(!empty($detail["start_at"]) && !empty($detail["end_at"] && !empty($business_time->start_at) && !empty($business_time->end_at)) ) {

                       $request_start_at = Carbon::createFromFormat('H:i:s', $detail["start_at"]);
                       $request_end_at = Carbon::createFromFormat('H:i:s', $detail["end_at"]);

                       $business_start_at = Carbon::createFromFormat('H:i:s', $business_time->start_at);
                       $business_end_at = Carbon::createFromFormat('H:i:s', $business_time->end_at);

                       $difference_in_both_request  = $request_start_at->diffInHours($request_end_at);
                       $difference_in_both_start_at  = $business_start_at->diffInHours($request_start_at);
                       $difference_in_end_at_start_at  = $business_end_at->diffInHours($request_start_at);

                       $difference_in_both_end_at  = $business_end_at->diffInHours($business_end_at);
                       $difference_in_start_at_end_at  = $business_start_at->diffInHours($request_end_at);


                       if($difference_in_both_request < 0) {
                           $error = [
                               "message" => "The given data was invalid.",
                               "errors" => [
                                   ("details.".$index.".end_at")=>["end at should be greater than start at"]

                                   ]
                        ];
                           throw new Exception(json_encode($error),422);
                       }


                       if($difference_in_both_start_at < 0) {
                           $error = [
                               "message" => "The given data was invalid.",
                               "errors" => [ ("details.".$index.".start_at")=>["start at should be in business working time"]]
                        ];
                           throw new Exception(json_encode($error),422);
                       }



                       if($difference_in_end_at_start_at < 0) {
                        $error = [
                            "message" => "The given data was invalid.",
                            "errors" => [ ("details.".$index.".start_at")=>["start at should be in business working time"]]
                     ];
                        throw new Exception(json_encode($error),422);
                    }


                       if($difference_in_both_end_at > 0) {
                           $error = [
                               "message" => "The given data was invalid.",
                               "errors" => [ ("details.".$index.".end_at")=>["end at should be in business working time"]]
                        ];
                           throw new Exception(json_encode($error),422);
                       }

                       if($difference_in_start_at_end_at < 0) {
                           $error = [
                               "message" => "The given data was invalid.",
                               "errors" => [ ("details.".$index.".end_at")=>["end at should be in business working time"]]
                        ];
                           throw new Exception(json_encode($error),422);
                       }
                    }

                   }







                $work_shift_query_params = [
                    "id" => $request_data["id"],
                ];

                $work_shift_prev = WorkShift::where($work_shift_query_params)->first();

                $work_shift  =  tap(WorkShift::where($work_shift_query_params))->update(
                    collect($request_data)->only([
        'name',
        'type',
        "description",
        'attendances_count',
        'is_personal',
        'break_type',
        'break_hours',

        'start_date',
        'end_date',
        // "is_active",
        // "business_id",
        // "created_by"

                    ])->toArray()
                )
                    // ->with("somthing")

                    ->first();
                if (!$work_shift) {
                    return response()->json([
                        "message" => "something went wrong."
                    ], 500);
                }

                $work_shift->departments()->delete();
                $work_shift->departments()->sync($request_data['departments'], []);

                // $work_shift->users()->delete();
                // $work_shift->users()->sync($request_data['users'], []);



                $work_shift->details()->delete();
                $work_shift->details()->createMany($request_data['details']);



                $fields_to_check = [
                'name',
                'type',
                "description",
                'attendances_count',
                'is_personal',
                'break_type',
                'break_hours'
            ];
                $fields_changed = false; // Initialize to false
                foreach ($fields_to_check as $field) {
                    $value1 = $work_shift_prev->$field;
                    $value2 = $work_shift->$field;

                    if ($value1 !== $value2) {
                        $fields_changed = true;
                        break;
                    }
                }


if(!$fields_changed){
    $fields_to_check = [
        'work_shift_id',
        'day',
        "start_at",
        'end_at',
        'is_weekend',
    ];
        $fields_changed = false; // Initialize to false
        foreach ($fields_to_check as $field) {

            foreach($work_shift_prev->details as $prev_detail){

                foreach($work_shift->details as $current_detail){
                    $value1 = $work_shift_prev->details->$field;
                    $value2 = $work_shift->$field;

                    if ($value1 != $value2) {
                        $fields_changed = true;
                        break 3;
                    }
                }

            }

        }


}





                if (
                    $fields_changed
                ) {

                    EmployeeWorkShiftHistory::where([
                        "to_date" => NULL
                    ])
                    ->whereHas('users',function($query) use($work_shift_prev)  {
                        $query->whereIn("users.id",$work_shift_prev->users()->pluck("id"));
                    })
                    ->update([
                        "to_date" => now()
                    ]);

        $employee_work_shift_history_data = $work_shift->toArray();
        $employee_work_shift_history_data["work_shift_id"] = $work_shift->id;
        $employee_work_shift_history_data["from_date"] = now();
        $employee_work_shift_history_data["to_date"] = NULL;
         $employee_work_shift_history =  EmployeeWorkShiftHistory::create($employee_work_shift_history_data);
         $employee_work_shift_history->users()->sync($work_shift->users()->pluck("id"),[]);




                }











                return response($work_shift, 201);
            });
        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }


    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shifts",
     *      operationId="getWorkShifts",
     *      tags={"administrator.work_shift"},
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
     *      * *  @OA\Parameter(
     * name="is_personal",
     * in="query",
     * description="is_personal",
     * required=true,
     * example="1"
     * ),
     *
     *
     * @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),
     *

     *      summary="This method is to get work shifts  ",
     *      description="This method is to get work shifts ",
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

    public function getWorkShifts(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('work_shift_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
        $business_times =    BusinessTime::where([
                "is_weekend" => 1,
                "business_id" => auth()->user()->business_id,
            ])->get();



            $work_shifts = WorkShift::with("details","departments","users")

            ->when(!empty(auth()->user()->business_id), function ($query) use ($business_times) {
                return $query->where([
                    "work_shifts.business_id" => auth()->user()->business_id
                ])
                ->orWhere(function($query) use($business_times) {
                    $query->where([
                        "business_id" => NULL,
                        "is_default" => 1
                    ])
                    ->whereHas('details', function($query) use($business_times) {

                    foreach($business_times as $business_time) {
                        $query->where([
                            "day" => $business_time->day,
                        ]);
                        if($business_time["is_weekend"]) {
                            $query->where([
                                "is_weekend" => 1,
                            ]);
                        } else {
                            $query->where(function($query) use($business_time) {
                                $query->whereTime("start_at", ">=", $business_time->start_at);
                                $query->orWhereTime("end_at", "<=", $business_time->end_at);
                            });
                        }

                    }
                });

                });
            })
            ->when(empty(auth()->user()->business_id), function ($query) use ($request) {

                return $query->where([
                    "work_shifts.is_default" => 1,
                    "work_shifts.business_id" => NULL
                ]);

            })
                ->when(!empty($request->search_key), function ($query) use ($request) {
                    return $query->where(function ($query) use ($request) {
                        $term = $request->search_key;
                        $query->where("work_shifts.name", "like", "%" . $term . "%")
                            ->orWhere("work_shifts.description", "like", "%" . $term . "%");
                    });
                })
                ->when(isset($request->is_personal), function ($query) use ($request) {
                    return $query->where('work_shifts.is_personal', intval($request->is_personal));
                })
                ->when(!isset($request->is_personal), function ($query) use ($request) {
                    return $query->where('work_shifts.is_personal', 0);
                })


                ->when(isset($request->is_default), function ($query) use ($request) {
                    return $query->where('work_shifts.is_default', intval($request->is_personal));
                })


                //    ->when(!empty($request->product_category_id), function ($query) use ($request) {
                //        return $query->where('product_category_id', $request->product_category_id);
                //    })
                ->when(!empty($request->start_date), function ($query) use ($request) {
                    return $query->where('work_shifts.created_at', ">=", $request->start_date);
                })





                ->when(!empty($request->end_date), function ($query) use ($request) {
                    return $query->where('work_shifts.created_at', "<=", ($request->end_date . ' 23:59:59'));
                })
                ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
                    return $query->orderBy("work_shifts.id", $request->order_by);
                }, function ($query) {
                    return $query->orderBy("work_shifts.id", "DESC");
                })
                ->when(!empty($request->per_page), function ($query) use ($request) {
                    return $query->paginate($request->per_page);
                }, function ($query) {
                    return $query->get();
                });



            return response()->json($work_shifts, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }

    /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shifts/{id}",
     *      operationId="getWorkShiftById",
     *      tags={"administrator.work_shift"},
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
     *      summary="This method is to get work shift by id",
     *      description="This method is to get work shift by id",
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


    public function getWorkShiftById($id, Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('work_shift_view')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $work_shift =  WorkShift::with("details")->where([
                "id" => $id,
                "business_id" => $business_id
            ])
                ->first();
            if (!$work_shift) {
                return response()->json([
                    "message" => "no work shift found"
                ], 404);
            }
            $work_shift->departments = $work_shift->departments;
            $work_shift->users = $work_shift->users;

            return response()->json($work_shift, 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }


  /**
     *
     * @OA\Get(
     *      path="/v1.0/work-shifts/get-by-user-id/{user_id}",
     *      operationId="getWorkShiftByUserId",
     *      tags={"administrator.work_shift"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *              @OA\Parameter(
     *         name="user_id",
     *         in="path",
     *         description="user_id",
     *         required=true,
     *  example="6"
     *      ),
     *      summary="This method is to get work shift by user id",
     *      description="This method is to get work shift by user id",
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


     public function getWorkShiftByUserId($user_id, Request $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity","DUMMY description");
             if (!$request->user()->hasPermissionTo('work_shift_view')) {
                 return response()->json([
                     "message" => "You can not perform this action"
                 ], 401);
             }
             $business_id =  auth()->user()->business_id;


             $work_shift =   WorkShift::with("details")->where([
                "business_id" => $business_id
            ])->whereHas('users', function ($query) use ($user_id) {
                $query->where('users.id', $user_id);
            })->first();



             if (!$work_shift) {
                 return response()->json([
                     "message" => "no work shift found for the user"
                 ], 404);
             }

             return response()->json($work_shift, 200);
         } catch (Exception $e) {

             return $this->sendError($e, 500, $request);
         }
     }

    /**
     *
     *     @OA\Delete(
     *      path="/v1.0/work-shifts/{ids}",
     *      operationId="deleteWorkShiftsByIds",
     *      tags={"administrator.work_shift"},
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
     *      summary="This method is to delete work shift by id",
     *      description="This method is to delete work shift by id",
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

    public function deleteWorkShiftsByIds(Request $request, $ids)
    {

        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");
            if (!$request->user()->hasPermissionTo('work_shift_delete')) {
                return response()->json([
                    "message" => "You can not perform this action"
                ], 401);
            }
            $business_id =  $request->user()->business_id;
            $idsArray = explode(',', $ids);
            $existingIds = WorkShift::where([
                "business_id" => $business_id,
                "is_default" => 0
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

            $user_exists =  UserWorkShift::whereIn("user_id", $existingIds)->exists();
            if ($user_exists) {
                $conflictingUsers = User::whereIn("designation_id", $existingIds)->get([
                    'id', 'first_Name',
                    'last_Name',
                ]);

                return response()->json([
                    "message" => "Some users are associated with the specified work shifts",
                    "conflicting_users" => $conflictingUsers
                ], 409);
            }

            WorkShift::destroy($existingIds);


            return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
        } catch (Exception $e) {

            return $this->sendError($e, 500, $request);
        }
    }
}
