<?php

namespace App\Http\Controllers;

use App\Http\Utils\BusinessUtil;
use App\Http\Utils\ErrorUtil;
use App\Http\Utils\PayrunUtil;
use App\Http\Utils\UserActivityUtil;
use App\Models\Payrun;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    use ErrorUtil, UserActivityUtil, BusinessUtil,PayrunUtil;

    /**
     *
     * @OA\Get(
     *      path="/v1.0/payrolls",
     *      operationId="getPayrolls",
     *      tags={"administrator.payrolls"},
     *       security={
     *           {"bearerAuth": {}}
     *       },
   *      * *  @OA\Parameter(
     * name="payrun_id",
     * in="query",
     * description="payrun_id",
     * required=true,
     * example="1"
     * ),
     *
     * @OA\Parameter(
     * name="user_id",
     * in="query",
     * description="user_id",
     * required=true,
     * example="1"
     * ),
     *
     *
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
     *  * *  @OA\Parameter(
     * name="is_active",
     * in="query",
     * description="is_active",
     * required=true,
     * example="1"
     * ),
     * *  @OA\Parameter(
     * name="order_by",
     * in="query",
     * description="order_by",
     * required=true,
     * example="ASC"
     * ),

     *      summary="This method is to get payrolls  ",
     *      description="This method is to get payrolls ",
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


    public function getPayrolls(Request $request)
    {
        try {
            $this->storeActivity($request, "DUMMY activity","DUMMY description");

            $payrun_id =  $request->payrun_id;
            if(!$payrun_id) {
               $error = [ "message" => "The given data was invalid.",
               "errors" => ["payrun_id"=>["The payrun_id field is required."]]
               ];
                   throw new Exception(json_encode($error),422);
            }

            $payrun = Payrun::where([
                "id" => $payrun_id,
                "business_id" => auth()->user()->business_id
            ])
            ->first();

            if(!$payrun) {
                $error = [ "message" => "The given data was invalid.",
                "errors" => ["payrun_id"=>["The payrun_id field is required."]]
                ];
                    throw new Exception(json_encode($error),422);
             }


            $employees = User::where([
                "business_id" => $payrun->business_id,
                "is_active" => 1
            ])
            ->when(!empty($request->user_id), function($query) use($request) {
                $query->where("id",$request->user_id);
            })


                ->get();


           $processed_employees =  $this->process_payrun($payrun,$employees,today());

            return response()->json($processed_employees);


        } catch (Exception $e) {
            error_log($e->getMessage());
            return $this->sendError($e, 500, $request);
        }
    }
}
