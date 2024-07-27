/**
*
* @OA\Post(
*      path="/v1.0/{{ $names["api_name"] }}",
*      operationId="create{{ $names["singular_model_name"] }}",
*      tags={"{{ $names["table_name"] }}"},
*       security={
*           {"bearerAuth": {}}
*       },
*      summary="This method is to store {{ $names["plural_comment_name"] }}",
*      description="This method is to store {{ $names["plural_comment_name"] }}",
*
*  @OA\RequestBody(
*         required=true,
*         @OA\JsonContent(
@foreach ($fields->toArray() as $field)
* @OA\Property(property="{{$field["name"]}}", type="string", format="string", example="{{$field["name"]}}"),
@endforeach
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

public function create{{ $names["singular_model_name"] }}({{ $names["singular_model_name"] }}CreateRequest $request)
{

   try {
       $this->storeActivity($request, "DUMMY activity", "DUMMY description");
       return DB::transaction(function () use ($request) {
           if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_create')) {
               return response()->json([
                   "message" => "You can not perform this action"
               ], 401);
           }

           $request_data = $request->validated();

           $request_data["is_active"] = 1;
           $request_data["is_default"] = 0;
           $request_data["created_by"] = $request->user()->id;
           $request_data["business_id"] = $request->user()->business_id;

           if (empty($request->user()->business_id)) {
               $request_data["business_id"] = NULL;
               if ($request->user()->hasRole('superadmin')) {
                   $request_data["is_default"] = 1;
               }
           }




           ${{ $names["singular_table_name"] }} =  {{ $names["singular_model_name"] }}::create($request_data);




           return response(${{ $names["singular_table_name"] }}, 201);
       });
   } catch (Exception $e) {

       return $this->sendError($e, 500, $request);
   }
}
