
/**
*
* @OA\Get(
*      path="/v1.0/{{ $names["api_name"] }}",
*      operationId="get{{ $names["plural_model_name"] }}",
*      tags={"{{ $names["table_name"] }}"},
*       security={
*           {"bearerAuth": {}}
*       },

@foreach ($fields->toArray() as $field)
@if ($field["type"] == "string")
@if ($field["request_validation_type"] == "date")
*         @OA\Parameter(
    *         name="start_{{$field["name"]}}",
    *         in="query",
    *         description="start_{{$field["name"]}}",
    *         required=true,
    *  example="6"
    *      ),
    *         @OA\Parameter(
    *         name="end_{{$field["name"]}}",
    *         in="query",
    *         description="end_{{$field["name"]}}",
    *         required=true,
    *  example="6"
    *      ),

    @else
    *         @OA\Parameter(
*         name="{{$field["name"]}}",
*         in="query",
*         description="{{$field["name"]}}",
*         required=true,
*  example="6"
*      ),

@endif

@endif

@endforeach
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




*      summary="This method is to get {{ $names["plural_comment_name"] }}  ",
*      description="This method is to get {{ $names["plural_comment_name"] }} ",
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

public function get{{ $names["plural_model_name"] }}(Request $request)
{
   try {
       $this->storeActivity($request, "DUMMY activity", "DUMMY description");
       if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_view')) {
           return response()->json([
               "message" => "You can not perform this action"
           ], 401);
       }
       $created_by  = NULL;
       if(auth()->user()->business) {
           $created_by = auth()->user()->business->created_by;
       }



       ${{ $names["table_name"] }} = {{ $names["singular_model_name"] }}::
       @if ($is_active && $is_default)
       when(empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
        if (auth()->user()->hasRole('superadmin')) {
            return $query->where('{{ $names["table_name"] }}.business_id', NULL)
                ->where('{{ $names["table_name"] }}.is_default', 1)
                ->when(isset($request->is_active), function ($query) use ($request) {
                    return $query->where('{{ $names["table_name"] }}.is_active', intval($request->is_active));
                });
        } else {
            return $query

            ->where(function($query) use($request) {
                $query->where('{{ $names["table_name"] }}.business_id', NULL)
                ->where('{{ $names["table_name"] }}.is_default', 1)
                ->where('{{ $names["table_name"] }}.is_active', 1)
                ->when(isset($request->is_active), function ($query) use ($request) {
                    if(intval($request->is_active)) {
                        return $query->whereDoesntHave("disabled", function($q) {
                            $q->whereIn("disabled_{{ $names["table_name"] }}.created_by", [auth()->user()->id]);
                        });
                    }

                })
                ->orWhere(function ($query) use ($request) {
                    $query->where('{{ $names["table_name"] }}.business_id', NULL)
                        ->where('{{ $names["table_name"] }}.is_default', 0)
                        ->where('{{ $names["table_name"] }}.created_by', auth()->user()->id)
                        ->when(isset($request->is_active), function ($query) use ($request) {
                            return $query->where('{{ $names["table_name"] }}.is_active', intval($request->is_active));
                        });
                });

            });
        }
    })
        ->when(!empty(auth()->user()->business_id), function ($query) use ($request, $created_by) {
            return $query
            ->where(function($query) use($request, $created_by) {


                $query->where('{{ $names["table_name"] }}.business_id', NULL)
                ->where('{{ $names["table_name"] }}.is_default', 1)
                ->where('{{ $names["table_name"] }}.is_active', 1)
                ->whereDoesntHave("disabled", function($q) use($created_by) {
                    $q->whereIn("disabled_{{ $names["table_name"] }}.created_by", [$created_by]);
                })
                ->when(isset($request->is_active), function ($query) use ($request, $created_by)  {
                    if(intval($request->is_active)) {
                        return $query->whereDoesntHave("disabled", function($q) use($created_by) {
                            $q->whereIn("disabled_{{ $names["table_name"] }}.business_id",[auth()->user()->business_id]);
                        });
                    }

                })


                ->orWhere(function ($query) use($request, $created_by){
                    $query->where('{{ $names["table_name"] }}.business_id', NULL)
                        ->where('{{ $names["table_name"] }}.is_default', 0)
                        ->where('{{ $names["table_name"] }}.created_by', $created_by)
                        ->where('{{ $names["table_name"] }}.is_active', 1)

                        ->when(isset($request->is_active), function ($query) use ($request) {
                            if(intval($request->is_active)) {
                                return $query->whereDoesntHave("disabled", function($q) {
                                    $q->whereIn("disabled_{{ $names["table_name"] }}.business_id",[auth()->user()->business_id]);
                                });
                            }

                        })


                        ;
                })
                ->orWhere(function ($query) use($request) {
                    $query->where('{{ $names["table_name"] }}.business_id', auth()->user()->business_id)
                        ->where('{{ $names["table_name"] }}.is_default', 0)
                        ->when(isset($request->is_active), function ($query) use ($request) {
                            return $query->where('{{ $names["table_name"] }}.is_active', intval($request->is_active));
                        });
                });
            });

        })
        @else
        where('{{ $names["table_name"] }}.business_id', auth()->user()->business_id)
       @endif



           ->when(!empty($request->id), function ($query) use ($request) {
             return $query->where('{{ $names["table_name"] }}.id', $request->id);
         })
         @foreach ($fields->toArray() as $field)
@if ($field["type"] == "string")

@if ($field["request_validation_type"] !== "date")
->when(!empty($request->{{$field["name"]}}), function ($query) use ($request) {
    return $query->where('{{ $names["table_name"] }}.id', $request->{{$field["type"]}});
})
@else
->when(!empty($request->start_{{$field["name"]}}), function ($query) use ($request) {
    return $query->where('{{ $names["table_name"] }}.{{$field["name"]}}', ">=", $request->start_{{$field["name"]}});
})
->when(!empty($request->end_{{$field["name"]}}), function ($query) use ($request) {
    return $query->where('{{ $names["table_name"] }}.{{$field["name"]}}', "<=", ($request->end_{{$field["name"]}} . ' 23:59:59'));
})
@endif



@endif

@endforeach

->when(!empty($request->search_key), function ($query) use ($request) {
return $query->where(function ($query) use ($request) {
$term = $request->search_key;
$query

@foreach ($fields->toArray() as $index=>$field)
@if ($field["type"] == "string" && $field["request_validation_type"] != "date")
@if ($index == 1)
->where("{{ $names["table_name"] }}.{{$field["name"]}}", "like", "%" . $term . "%")
@else
->orWhere("{{ $names["table_name"] }}.{{$field["name"]}}", "like", "%" . $term . "%")
@endif
 @endif
 @endforeach
;
});


})


           ->when(!empty($request->start_date), function ($query) use ($request) {
               return $query->where('{{ $names["table_name"] }}.created_at', ">=", $request->start_date);
           })
           ->when(!empty($request->end_date), function ($query) use ($request) {
               return $query->where('{{ $names["table_name"] }}.created_at', "<=", ($request->end_date . ' 23:59:59'));
           })
           ->when(!empty($request->order_by) && in_array(strtoupper($request->order_by), ['ASC', 'DESC']), function ($query) use ($request) {
               return $query->orderBy("{{ $names["table_name"] }}.id", $request->order_by);
           }, function ($query) {
               return $query->orderBy("{{ $names["table_name"] }}.id", "DESC");
           })
           ->when($request->filled("is_single_search") && $request->boolean("is_single_search"), function ($query) use ($request) {
             return $query->first();
     }, function($query) {
        return $query->when(!empty(request()->per_page), function ($query) {
             return $query->paginate(request()->per_page);
         }, function ($query) {
             return $query->get();
         });
     });

     if($request->filled("is_single_search") && empty(${{ $names["table_name"] }})){
throw new Exception("No data found",404);
     }


       return response()->json(${{ $names["table_name"] }}, 200);
   } catch (Exception $e) {

       return $this->sendError($e, 500, $request);
   }
}

