<?php

use App\Http\Controllers\CodeGeneratorController;
use App\Http\Controllers\CustomWebhookController;
use App\Http\Controllers\SetUpController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\DeveloperLoginController;
use App\Models\Attendance;
use App\Models\AttendanceHistory;
use App\Models\AttendanceProject;
use App\Models\Business;
use App\Models\DepartmentUser;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateWrapper;
use App\Models\Project;
use App\Models\Role;
use App\Models\User;
use App\Models\UserWorkLocation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;



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




Route::get('/code-generator', [CodeGeneratorController::class,"getCodeGeneratorForm"])->name("code-generator-form");
Route::post('/code-generator',[CodeGeneratorController::class,"generateCode"] )->name("code-generator");


// Grouping the routes and applying middleware to the entire group
Route::middleware(['developer'])->group(function () {

    Route::get('/', function () {
        return view('welcome');
    });

    Route::get('/frontend-error-log', [SetUpController::class, "getFrontEndErrorLogs"])->name("frontend-error-log");
    Route::get('/error-log', [SetUpController::class, "getErrorLogs"])->name("error-log");

    Route::get('/error-log/{id}', [SetUpController::class, "testError"])->name("api-call");

    Route::get('/activity-log/{id}', [SetUpController::class, "testApi"])->name("api-test");



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


// Route::get("/run",function() {

//     // Find the user by email
//     $specialReseller = User::where('email', 'kids20acc@gmail.com')->first();

//     if ($specialReseller) {
//         // Fetch the required permissions
//         $permissions = Permission::whereIn('name', ['handle_self_registered_businesses'])->get();

//         if ($permissions->isNotEmpty()) {
//             // Assign the permissions to the user
//             $specialReseller->givePermissionTo($permissions);
//             echo "Permissions assigned successfully.";
//         } else {
//             echo "Permissions not found.";
//         }
//     } else {
//         echo "User not found.";
//     }
//             return "ok";
//         });


// Route::get("/run",function() {


//     $users = User::whereNotNull("work_location_id")->get();
//     foreach($users as $user){
//         UserWorkLocation::create([
//             "user_id" => $user->id,
//             "work_location_id" => $user->work_location_id
//         ]);
//     }
//             return "ok";
//         });



// Route::get("/run", function() {
//     // Get all attendances with non-null project_id using a single query
//     $attendances = Attendance::whereNotNull("project_id")->get();

//     // Prepare data for bulk insertion
//     $attendanceProjects = [];
//     foreach ($attendances as $attendance) {
//         // Check if project exists, otherwise insert null
//         $project = Project::find($attendance->project_id);
//         $projectId = $project ? $attendance->project_id : null;

//         $attendanceProjects[] = [
//             "attendance_id" => $attendance->id,
//             "project_id" => $projectId
//         ];
//     }

//     // Bulk insert into AttendanceProject table
//     AttendanceProject::insert($attendanceProjects);

//     return "ok";
// });




// Route::get("/run", function() {
//     $role = Role::where('name','reseller')->first();

//     $permission = Permission::where('name', "bank_create")->first();

//         $role->givePermissionTo($permission);


//     return "ok";
// });


// Route::get("/run", function() {
//     // Fetch all users in chunks to handle large data sets efficiently
//     User::chunk(100, function($users) {
//         foreach ($users as $user) {
//             // Fetch all DepartmentUser records for the user, ordered by creation date
//             $departmentUsers = DepartmentUser::where('user_id', $user->id)
//                                               ->orderBy('created_at')
//                                               ->get();

//             // Check if there are more than one records
//             if ($departmentUsers->count() > 1) {
//                 // Get the IDs of the records to delete, excluding the first one
//                 $idsToDelete = $departmentUsers->skip(1)->pluck('id');

//                 // Bulk delete the records
//                 DepartmentUser::whereIn('id', $idsToDelete)->delete();
//             }
//         }
//     });

//     return "ok";
// });


// Route::get("/run", function() {
//     // Get all business ids
//     $business_ids = Business::pluck("id");

//     // Define the permission key you want to revoke
//     $permissionKey = 'department_delete'; // Replace with your actual permission key

//     foreach($business_ids as $business_id) {
//         // Construct role name based on business id
//         $roleName = "business_manager#" . $business_id;

//         // Find the role by name
//         $role = Role::where("name", $roleName)->first();

//         // Revoke the permission from the role
//         if ($role) {
//             $permission = Permission::where('name', $permissionKey)->first();
//             if ($permission) {
//                 $role->revokePermissionTo($permission);
//                 // Optionally, you can sync permissions to remove all other permissions except the one you're revoking
//                 // $role->syncPermissions([$permission]);
//             }
//         }
//     }

//     return "ok";
// });


// Route::get("/run",function() {

//     // Find the user by email
//     $specialReseller = User::where('email', 'kids20acc@gmail.com')->first();

//     if ($specialReseller) {
//         // Fetch the required permissions
//         $permissions = Permission::whereIn('name', ['system_setting_view'])->get();

//         if ($permissions->isNotEmpty()) {
//             // Assign the permissions to the user
//             $specialReseller->givePermissionTo($permissions);
//             echo "Permissions assigned successfully.";
//         } else {
//             echo "Permissions not found.";
//         }
//     } else {
//         echo "User not found.";
//     }
//             return "ok";
//         });


