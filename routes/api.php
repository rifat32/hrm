<?php


use App\Http\Controllers\AuthController;




use App\Http\Controllers\DashboardManagementController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EmailTemplateWrapperController;




use App\Http\Controllers\BusinessBackgroundImageController;



use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;

use App\Http\Controllers\RolesController;



use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\WorkShiftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/v1.0/register', [AuthController::class, "register"]);
Route::post('/v1.0/login', [AuthController::class, "login"]);
Route::post('/v1.0/token-regenerate', [AuthController::class, "regenerateToken"]);

Route::post('/forgetpassword', [AuthController::class, "storeToken"]);
Route::post('/resend-email-verify-mail', [AuthController::class, "resendEmailVerifyToken"]);

Route::patch('/forgetpassword/reset/{token}', [AuthController::class, "changePasswordByToken"]);
Route::post('/auth/check/email', [AuthController::class, "checkEmail"]);




























Route::post('/v1.0/user-image', [UserManagementController::class, "createUserImage"]);

Route::post('/v1.0/business-image', [BusinessController::class, "createBusinessImage"]);
Route::post('/v1.0/business-image-multiple', [BusinessController::class, "createBusinessImageMultiple"]);


// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
// Protected Routes
// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
Route::middleware(['auth:api'])->group(function () {
    Route::get('/v1.0/user', [AuthController::class, "getUser"]);
    Route::patch('/auth/changepassword', [AuthController::class, "changePassword"]);

    Route::put('/v1.0/update-user-info', [AuthController::class, "updateUserInfo"]);



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// notification management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
    Route::get('/v1.0/notifications/{perPage}', [NotificationController::class, "getNotifications"]);

    Route::get('/v1.0/notifications/{business_id}/{perPage}', [NotificationController::class, "getNotificationsByBusinessId"]);

    Route::put('/v1.0/notifications/change-status', [NotificationController::class, "updateNotificationStatus"]);

    Route::delete('/v1.0/notifications/{id}', [NotificationController::class, "deleteNotificationById"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// notification management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// ********************************************
// user management section --user
// ********************************************

Route::post('/v1.0/users', [UserManagementController::class, "createUser"]);
Route::get('/v1.0/users/get-by-id/{id}', [UserManagementController::class, "getUserById"]);
Route::put('/v1.0/users', [UserManagementController::class, "updateUser"]);
Route::put('/v1.0/users/profile', [UserManagementController::class, "updateUserProfile"]);
Route::put('/v1.0/users/toggle-active', [UserManagementController::class, "toggleActiveUser"]);
Route::get('/v1.0/users', [UserManagementController::class, "getUsers"]);
Route::delete('/v1.0/users/{ids}', [UserManagementController::class, "deleteUsersByIds"]);

// ********************************************
// user management section --role
// ********************************************
Route::get('/v1.0/initial-role-permissions', [RolesController::class, "getInitialRolePermissions"]);
Route::post('/v1.0/roles', [RolesController::class, "createRole"]);
Route::put('/v1.0/roles', [RolesController::class, "updateRole"]);
Route::get('/v1.0/roles/{perPage}', [RolesController::class, "getRoles"]);
Route::get('/v1.0/roles/get/all', [RolesController::class, "getRolesAll"]);
Route::get('/v1.0/roles/get-by-id/{id}', [RolesController::class, "getRoleById"]);
Route::delete('/v1.0/roles/{id}', [RolesController::class, "deleteRoleById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end user management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// business management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/auth/register-with-business', [BusinessController::class, "registerUserWithBusiness"]);
Route::post('/v1.0/businesses', [BusinessController::class, "createBusiness"]);
Route::put('/v1.0/businesses/toggle-active', [BusinessController::class, "toggleActiveBusiness"]);
Route::put('/v1.0/businesses', [BusinessController::class, "updateBusiness"]);
Route::put('/v1.0/businesses/separate', [BusinessController::class, "updateBusinessSeparate"]);
Route::get('/v1.0/businesses', [BusinessController::class, "getBusinesses"]);
Route::get('/v1.0/businesses/{id}', [BusinessController::class, "getBusinessById"]);
Route::delete('/v1.0/businesses/{ids}', [BusinessController::class, "deleteBusinessesByIds"]);
Route::get('/v1.0/businesses/by-business-owner/all', [BusinessController::class, "getAllBusinessesByBusinessOwner"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end business management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// businesses Background Image Management
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/business-background-image', [BusinessBackgroundImageController::class, "updateBusinessBackgroundImage"]);
Route::post('/v1.0/business-background-image/by-user', [BusinessBackgroundImageController::class, "updateBusinessBackgroundImageByUser"]);
Route::get('/v1.0/business-background-image', [BusinessBackgroundImageController::class, "getBusinessBackgroundImage"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end businesses Background Image Management
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// template management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// ********************************************
// template management section --wrapper
// ********************************************
Route::put('/v1.0/email-template-wrappers', [EmailTemplateWrapperController::class, "updateEmailTemplateWrapper"]);
Route::get('/v1.0/email-template-wrappers/{perPage}', [EmailTemplateWrapperController::class, "getEmailTemplateWrappers"]);
Route::get('/v1.0/email-template-wrappers/single/{id}', [EmailTemplateWrapperController::class, "getEmailTemplateWrapperById"]);

// ********************************************
// template management section
// ********************************************
Route::post('/v1.0/email-templates', [EmailTemplateController::class, "createEmailTemplate"]);
Route::put('/v1.0/email-templates', [EmailTemplateController::class, "updateEmailTemplate"]);
Route::get('/v1.0/email-templates/{perPage}', [EmailTemplateController::class, "getEmailTemplates"]);
Route::get('/v1.0/email-templates/single/{id}', [EmailTemplateController::class, "getEmailTemplateById"]);
Route::get('/v1.0/email-template-types', [EmailTemplateController::class, "getEmailTemplateTypes"]);
 Route::delete('/v1.0/email-templates/{id}', [EmailTemplateController::class, "deleteEmailTemplateById"]);

// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// template management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// ********************************************
// notification template management section
// ********************************************

Route::put('/v1.0/notification-templates', [NotificationTemplateController::class, "updateNotificationTemplate"]);
Route::get('/v1.0/notification-templates/{perPage}', [NotificationTemplateController::class, "getNotificationTemplates"]);
Route::get('/v1.0/notification-templates/single/{id}', [NotificationTemplateController::class, "getEmailTemplateById"]);
Route::get('/v1.0/notification-template-types', [NotificationTemplateController::class, "getNotificationTemplateTypes"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// notification template management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// payment type management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/payment-types', [PaymentTypeController::class, "createPaymentType"]);
Route::put('/v1.0/payment-types', [PaymentTypeController::class, "updatePaymentType"]);
Route::get('/v1.0/payment-types/{perPage}', [PaymentTypeController::class, "getPaymentTypes"]);
Route::delete('/v1.0/payment-types/{id}', [PaymentTypeController::class, "deletePaymentTypeById"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// payment type management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%









// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// department  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/departments', [DepartmentController::class, "createDepartment"]);
Route::put('/v1.0/departments', [DepartmentController::class, "updateDepartment"]);
Route::get('/v1.0/departments', [DepartmentController::class, "getDepartments"]);
Route::get('/v1.0/departments/{id}', [DepartmentController::class, "getDepartmentById"]);
Route::delete('/v1.0/departments/{ids}', [DepartmentController::class, "deleteDepartmentsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end department  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// holiday  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/holidays', [HolidayController::class, "createHoliday"]);
Route::put('/v1.0/holidays', [HolidayController::class, "updateHoliday"]);
Route::get('/v1.0/holidays', [HolidayController::class, "getHolidays"]);
Route::get('/v1.0/holidays/{id}', [HolidayController::class, "getHolidayById"]);
Route::delete('/v1.0/holidays/{ids}', [HolidayController::class, "deleteHolidaysByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end holiday  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// work shift  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/work-shifts', [WorkShiftController::class, "createWorkShift"]);
Route::put('/v1.0/work-shifts', [WorkShiftController::class, "updateWorkShift"]);
Route::get('/v1.0/work-shifts', [WorkShiftController::class, "getWorkShifts"]);
Route::get('/v1.0/work-shifts/{id}', [WorkShiftController::class, "getWorkShiftById"]);
Route::delete('/v1.0/work-shifts/{ids}', [WorkShiftController::class, "deleteWorkShiftsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end work shift  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@















































// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// product category management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/product-categories', [ProductCategoryController::class, "createProductCategory"]);
Route::put('/v1.0/product-categories', [ProductCategoryController::class, "updateProductCategory"]);
Route::get('/v1.0/product-categories/{perPage}', [ProductCategoryController::class, "getProductCategories"]);
Route::delete('/v1.0/product-categories/{id}', [ProductCategoryController::class, "deleteProductCategoryById"]);
Route::get('/v1.0/product-categories/single/get/{id}', [ProductCategoryController::class, "getProductCategoryById"]);

Route::get('/v1.0/product-categories/get/all', [ProductCategoryController::class, "getAllProductCategory"]);


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end product category management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// product management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/products', [ProductController::class, "createProduct"]);
Route::put('/v1.0/products', [ProductController::class, "updateProduct"]);
Route::patch('/v1.0/products/link-product-to-shop', [ProductController::class, "linkProductToShop"]);

Route::get('/v1.0/products/{perPage}', [ProductController::class, "getProducts"]);
Route::get('/v1.0/products/single/get/{id}', [ProductController::class, "getProductById"]);
Route::delete('/v1.0/products/{id}', [ProductController::class, "deleteProductById"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end product  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// dashboard section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



Route::get('/v1.0/business-owner-dashboard/jobs-in-area/{business_id}', [DashboardManagementController::class, "getBusinessOwnerDashboardDataJobList"]);

Route::get('/v1.0/business-owner-dashboard/jobs-application/{business_id}', [DashboardManagementController::class, "getBusinessOwnerDashboardDataJobApplications"]);


Route::get('/v1.0/business-owner-dashboard/winned-jobs-application/{business_id}', [DashboardManagementController::class, "getBusinessOwnerDashboardDataWinnedJobApplications"]);

Route::get('/v1.0/business-owner-dashboard/completed-bookings/{business_id}', [DashboardManagementController::class, "getBusinessOwnerDashboardDataCompletedBookings"]);


Route::get('/v1.0/business-owner-dashboard/upcoming-jobs/{business_id}/{duration}', [DashboardManagementController::class, "getBusinessOwnerDashboardDataUpcomingJobs"]);




Route::get('/v1.0/business-owner-dashboard/{business_id}', [DashboardManagementController::class, "getBusinessOwnerDashboardData"]);

Route::get('/v1.0/superadmin-dashboard', [DashboardManagementController::class, "getSuperAdminDashboardData"]);
Route::get('/v1.0/data-collector-dashboard', [DashboardManagementController::class, "getDataCollectorDashboardData"]);


// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end dashboard section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%





});

// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^
// end admin routes
// !!!!!!!@@@@@@@@@@@@$$$$$$$$$$$$%%%%%%%%%%%%%%%%^^^^^^^^^^


























































































Route::middleware(['auth:api'])->group(function () {
































});


