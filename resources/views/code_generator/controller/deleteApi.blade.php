/**
*
*     @OA\Delete(
*      path="/v1.0/{{ $names["api_name"] }}/{ids}",
*      operationId="delete{{ $names["plural_model_name"] }}ByIds",
*      tags={"{{ $names["table_name"] }}"},
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
*      summary="This method is to delete {{ $names["singular_comment_name"] }} by id",
*      description="This method is to delete {{ $names["singular_comment_name"] }} by id",
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

public function delete{{ $names["plural_model_name"] }}ByIds(Request $request, $ids)
{

   try {
       $this->storeActivity($request, "DUMMY activity", "DUMMY description");
       if (!$request->user()->hasPermissionTo('{{ $names["singular_table_name"] }}_delete')) {
           return response()->json([
               "message" => "You can not perform this action"
           ], 401);
       }

       $idsArray = explode(',', $ids);
       $existingIds = {{ $names["singular_model_name"] }}::whereIn('id', $idsArray)
           ->when(empty($request->user()->business_id), function ($query) use ($request) {
               if ($request->user()->hasRole("superadmin")) {
                   return $query->where('{{ $names["table_name"] }}.business_id', NULL)
                       ->where('{{ $names["table_name"] }}.is_default', 1);
               } else {
                   return $query->where('{{ $names["table_name"] }}.business_id', NULL)
                       ->where('{{ $names["table_name"] }}.is_default', 0)
                       ->where('{{ $names["table_name"] }}.created_by', $request->user()->id);
               }
           })
           ->when(!empty($request->user()->business_id), function ($query) use ($request) {
               return $query->where('{{ $names["table_name"] }}.business_id', $request->user()->business_id)
                   ->where('{{ $names["table_name"] }}.is_default', 0);
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





       {{ $names["singular_model_name"] }}::destroy($existingIds);


       return response()->json(["message" => "data deleted sussfully", "deleted_ids" => $existingIds], 200);
   } catch (Exception $e) {

       return $this->sendError($e, 500, $request);
   }
}
