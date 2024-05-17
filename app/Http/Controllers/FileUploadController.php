<?php

namespace App\Http\Controllers;

use App\Http\Requests\SingleFileUploadRequest;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\UserActivityUtil;
use Exception;
use Illuminate\Http\Request;

class FileUploadController extends Controller
{
    use UserActivityUtil,ErrorUtil;
  /**
     *
     * @OA\Post(
     *      path="/v1.0/files/single-file-upload",
     *      operationId="createFileSingle",
     *      tags={"files"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
     *      summary="This method is to store files",
     *      description="This method is to store files",
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

     public function createFileSingle(SingleFileUploadRequest $request)
     {
         try {
             $this->storeActivity($request, "DUMMY activity", "DUMMY description");


             $request_data = $request->validated();

             $location =  config("setup-config.temporary_files_location");

             $new_file_name = time() . '_' . str_replace(' ', '_', $request_data["file"]->getClientOriginalName());

             $request_data["file"]->move(public_path($location), $new_file_name);




             return response()->json([

            "file" => $new_file_name,
            "location" => $location,
             "full_location" => ("/" . $location . "/" . $new_file_name)


            ], 200);
         } catch (Exception $e) {
             error_log($e->getMessage());
             return $this->sendError($e, 500, $request);
         }
     }
}
