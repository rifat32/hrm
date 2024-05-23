<?php

use App\Http\Controllers\CustomWebhookController;
use App\Http\Controllers\SetUpController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DeveloperLoginController;
use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateWrapper;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/


Route::get("/developer-login",[DeveloperLoginController::class,"login"])->name("login.view");
Route::post("/developer-login",[DeveloperLoginController::class,"passUser"]);




// Grouping the routes and applying middleware to the entire group
Route::middleware(['developer'])->group(function () {

    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/frontend-error-log', [SetUpController::class, "getFrontEndErrorLogs"])->name("frontend-error-log");
    Route::get('/error-log', [SetUpController::class, "getErrorLogs"])->name("error-log");
    Route::get('/activity-log', [SetUpController::class, "getActivityLogs"])->name("activity-log");



    Route::get('/setup', [SetUpController::class, "setUp"])->name("setup");
    Route::get('/backup', [SetUpController::class, "backup"])->name("backup");
    Route::get('/roleRefresh', [SetUpController::class, "roleRefresh"])->name("roleRefresh");
    Route::get('/swagger-refresh', [SetUpController::class, "swaggerRefresh"]);
    Route::get('/migrate', [SetUpController::class, "migrate"]);


});











Route::get("/subscriptions/redirect-to-stripe",[SubscriptionController::class,"redirectUserToStripe"]);
Route::get("/subscriptions/get-success-payment",[SubscriptionController::class,"stripePaymentSuccess"])->name("subscription.success_payment");
Route::get("/subscriptions/get-failed-payment",[SubscriptionController::class,"stripePaymentFailed"])->name("subscription.failed_payment");







Route::get("/activate/{token}",function(Request $request,$token) {
    $user = User::where([
        "email_verify_token" => $token,
    ])
        ->where("email_verify_token_expires", ">", now())
        ->first();
    if (!$user) {
        return response()->json([
            "message" => "Invalid Url Or Url Expired"
        ], 400);
    }

    $user->email_verified_at = now();
    $user->save();


    $email_content = EmailTemplate::where([
        "type" => "welcome_message",
        "is_active" => 1

    ])->first();


    $html_content = json_decode($email_content->template);
    $html_content =  str_replace("[FirstName]", $user->first_Name, $html_content );
    $html_content =  str_replace("[LastName]", $user->last_Name, $html_content );
    $html_content =  str_replace("[FullName]", ($user->first_Name. " " .$user->last_Name), $html_content );
    $html_content =  str_replace("[AccountVerificationLink]", (env('APP_URL').'/activate/'.$user->email_verify_token), $html_content);
    $html_content =  str_replace("[ForgotPasswordLink]", (env('FRONT_END_URL').'/fotget-password/'.$user->resetPasswordToken), $html_content );



    $email_template_wrapper = EmailTemplateWrapper::where([
        "id" => $email_content->wrapper_id
    ])
    ->first();


    $html_final = json_decode($email_template_wrapper->template);
    $html_final =  str_replace("[content]", $html_content, $html_final);


    return view("dynamic-welcome-message",["html_content" => $html_final]);
});



// Route::get("/test",function() {

//     $attendances = Attendance::get();
//     foreach($attendances as $attendance) {
//         if($attendance->in_time) {
//             $attendance->attendance_records = [
//                 [
//                        "in_time" => $attendance->in_time,
//                        "out_time" => $attendance->out_time,
//                 ]
//                 ];
//         }
//         $attendance->save();
//     }

//     $attendance_histories = AttendanceHistory::get();
//     foreach($attendance_histories as $attendance_history) {
//         if($attendance_history->in_time) {
//             $attendance_history->attendance_records = [
//                 [
//                        "in_time" => $attendance->in_time,
//                        "out_time" => $attendance->out_time,
//                 ]
//                 ];
//         }
// $attendance_history->save();
//     }
//     return "ok";
// });



// Route::get("/test",function() {

//     $attendances = Attendance::get();
//     foreach($attendances as $attendance) {
//         if($attendance->in_time) {
//             $attendance->attendance_records = [
//                 [
//                        "in_time" => $attendance->in_time,
//                        "out_time" => $attendance->out_time,
//                 ]
//                 ];
//         }

//         $total_present_hours = 0;

// collect($attendance->attendance_records)->each(function($attendance_record) use(&$total_present_hours) {
//     $in_time = Carbon::createFromFormat('H:i:s', $attendance_record["in_time"]);
//     $out_time = Carbon::createFromFormat('H:i:s', $attendance_record["out_time"]);
//     $total_present_hours += $out_time->diffInHours($in_time);
// });

// if($total_present_hours > 0){
//     $attendance->is_present=1;
//     $attendance->save();
// } else {
//     $attendance->is_present=0;
//     $attendance->save();
// }

//     }


//     return "ok";
// });


