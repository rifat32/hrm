/**
*
* @OA\Put(
*      path="/v1.0/{{ $names["api_name"] }}/toggle-active",
*      operationId="toggleActive{{ $names["singular_model_name"] }}",
*      tags={"{{ $names["table_name"] }}"},
*       security={
*           {"bearerAuth": {}}
*       },
*      summary="This method is to toggle {{ $names["plural_comment_name"] }}",
*      description="This method is to toggle {{ $names["plural_comment_name"] }}",
*
*  @OA\RequestBody(
*         required=true,
*         @OA\JsonContent(

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

public function toggleActive{{ $names["singular_model_name"] }}(GetIdRequest $request)
{

   try {
       $this->storeActivity($request, "DUMMY activity", "DUMMY description");
       if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_activate')) {
           return response()->json([
               "message" => "You can not perform this action"
           ], 401);
       }
       $request_data = $request->validated();

       ${{ $names["singular_table_name"] }} =  {{ $names["singular_model_name"] }}::where([
           "id" => $request_data["id"],
       ])
           ->first();
       if (!${{ $names["singular_table_name"] }}) {

           return response()->json([
               "message" => "no data found"
           ], 404);
       }









       $should_update = 0;
       $should_disable = 0;
       if (empty(auth()->user()->business_id)) {

           if (auth()->user()->hasRole('superadmin')) {
               if ((${{ $names["singular_table_name"] }}->business_id != NULL )) {

                   return response()->json([
                       "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                   ], 403);
               } else {
                   $should_update = 1;
               }
           } else {
               if (${{ $names["singular_table_name"] }}->business_id != NULL) {

                   return response()->json([
                       "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                   ], 403);
               } else if (${{ $names["singular_table_name"] }}->is_default == 0) {

                   if(${{ $names["singular_table_name"] }}->created_by != auth()->user()->id) {

                       return response()->json([
                           "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                       ], 403);
                   }
                   else {
                       $should_update = 1;
                   }



               }
               else {
                $should_disable = 1;

               }
           }
       } else {
           if (${{ $names["singular_table_name"] }}->business_id != NULL) {
               if ((${{ $names["singular_table_name"] }}->business_id != auth()->user()->business_id)) {

                   return response()->json([
                       "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                   ], 403);
               } else {
                   $should_update = 1;
               }
           } else {
               if (${{ $names["singular_table_name"] }}->is_default == 0) {
                   if (${{ $names["singular_table_name"] }}->created_by != auth()->user()->created_by) {

                       return response()->json([
                           "message" => "You do not have permission to update this {{ $names["singular_comment_name"] }} due to role restrictions."
                       ], 403);
                   } else {
                       $should_disable = 1;

                   }
               } else {
                   $should_disable = 1;

               }
           }
       }

       if ($should_update) {
           ${{ $names["singular_table_name"] }}->update([
               'is_active' => !${{ $names["singular_table_name"] }}->is_active
           ]);
       }

       if($should_disable) {
           $disabled_{{ $names["singular_table_name"] }} =    Disabled{{ $names["singular_model_name"] }}::where([
               '{{ $names["singular_table_name"] }}_id' => ${{ $names["singular_table_name"] }}->id,
               'business_id' => auth()->user()->business_id,
               'created_by' => auth()->user()->id,
           ])->first();
           if(!$disabled_{{ $names["singular_table_name"] }}) {
               Disabled{{ $names["singular_model_name"] }}::create([
                   '{{ $names["singular_table_name"] }}_id' => ${{ $names["singular_table_name"] }}->id,
                   'business_id' => auth()->user()->business_id,
                   'created_by' => auth()->user()->id,
               ]);
           } else {
               $disabled_{{ $names["singular_table_name"] }}->delete();
           }
       }


       return response()->json(['message' => '{{ $names["singular_comment_name"] }} status updated successfully'], 200);
   } catch (Exception $e) {
       error_log($e->getMessage());
       return $this->sendError($e, 500, $request);
   }
}
