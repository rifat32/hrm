<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;




use App\Http\Controllers\DashboardManagementController;
use App\Http\Controllers\EmailTemplateController;
use App\Http\Controllers\EmailTemplateWrapperController;




use App\Http\Controllers\BusinessBackgroundImageController;



use App\Http\Controllers\BusinessController;
use App\Http\Controllers\BusinessTierController;
use App\Http\Controllers\BusinessTimesController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\DesignationController;
use App\Http\Controllers\EmploymentStatusController;
use App\Http\Controllers\HistoryDetailsController;
use App\Http\Controllers\HolidayController;
use App\Http\Controllers\JobListingController;
use App\Http\Controllers\JobPlatformController;
use App\Http\Controllers\JobTypeController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\ModuleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationTemplateController;
use App\Http\Controllers\PaymentTypeController;
use App\Http\Controllers\ProductCategoryController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RecruitmentProcessController;
use App\Http\Controllers\RolesController;
use App\Http\Controllers\SettingAttendanceController;
use App\Http\Controllers\SettingLeaveController;
use App\Http\Controllers\SettingLeaveTypeController;
use App\Http\Controllers\SettingPayrollController;
use App\Http\Controllers\SocialSiteController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserAddressHistoryController;
use App\Http\Controllers\UserAssetController;
use App\Http\Controllers\UserDocumentController;
use App\Http\Controllers\UserEducationHistoryController;
use App\Http\Controllers\UserJobHistoryController;
use App\Http\Controllers\UserManagementController;
use App\Http\Controllers\UserPassportHistoryController;
use App\Http\Controllers\UserSocialSiteController;
use App\Http\Controllers\UserSponsorshipHistoryController;
use App\Http\Controllers\UserVisaHistoryController;
use App\Http\Controllers\WorkLocationController;
use App\Http\Controllers\WorkShiftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider jistoryin a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/


Route::post('/v1.0/register', [AuthController::class, "register"]);
Route::post('/v1.0/login', [AuthController::class, "login"]);

Route::post('/v1.0/token-regenerate', [AuthController::class, "regenerateToken"]);

Route::post('/forgetpassword', [AuthController::class, "storeToken"]);
Route::post('/v2.0/forgetpassword', [AuthController::class, "storeTokenV2"]);

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
    Route::post('/v1.0/logout', [AuthController::class, "logout"]);
    Route::get('/v1.0/user', [AuthController::class, "getUser"]);
    Route::patch('/auth/changepassword', [AuthController::class, "changePassword"]);
    Route::put('/v1.0/update-user-info', [AuthController::class, "updateUserInfo"]);



    // @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// modules  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


Route::put('/v1.0/modules/toggle-active', [ModuleController::class, "toggleActiveModule"]);
Route::get('/v1.0/modules', [ModuleController::class, "getModules"]);


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end modules management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/business-tiers', [BusinessTierController::class, "createBusinessTier"]);
Route::put('/v1.0/business-tiers', [BusinessTierController::class, "updateBusinessTier"]);
Route::get('/v1.0/business-tiers', [BusinessTierController::class, "getBusinessTiers"]);
Route::get('/v1.0/business-tiers/{id}', [BusinessTierController::class, "getBusinessTierById"]);
Route::delete('/v1.0/business-tiers/{ids}', [BusinessTierController::class, "deleteBusinessTiersByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end job platform management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



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

Route::post('/v1.0/users/single-file-upload', [UserManagementController::class, "createUserFileSingle"]);
Route::post('/v1.0/users/multiple-file-upload', [UserManagementController::class, "createUserFileMultiple"]);
Route::post('/v1.0/users', [UserManagementController::class, "createUser"]);
Route::get('/v1.0/users/{id}', [UserManagementController::class, "getUserById"]);
Route::put('/v1.0/users', [UserManagementController::class, "updateUser"]);

Route::put('/v1.0/users/assign-roles', [UserManagementController::class, "assignUserRole"]);

Route::put('/v1.0/users/profile', [UserManagementController::class, "updateUserProfile"]);
Route::put('/v1.0/users/toggle-active', [UserManagementController::class, "toggleActiveUser"]);
Route::get('/v1.0/users', [UserManagementController::class, "getUsers"]);
Route::get('/v2.0/users', [UserManagementController::class, "getUsersV2"]);
Route::delete('/v1.0/users/{ids}', [UserManagementController::class, "deleteUsersByIds"]);
Route::get('/v1.0/users/get/user-activity', [UserManagementController::class, "getUserActivity"]);



Route::post('/v2.0/users', [UserManagementController::class, "createUserV2"]);
Route::put('/v2.0/users', [UserManagementController::class, "updateUserV2"]);
Route::put('/v1.0/users/update-address', [UserManagementController::class, "updateUserAddress"]);
Route::put('/v1.0/users/update-joining-date', [UserManagementController::class, "updateUserJoiningDate"]);


Route::put('/v1.0/users/update-emergency-contact', [UserManagementController::class, "updateEmergencyContact"]);
Route::put('/v1.0/users/store-details', [UserManagementController::class, "storeUserDetails"]);
Route::get('/v3.0/users', [UserManagementController::class, "getUsersV3"]);
Route::get('/v2.0/users/{id}', [UserManagementController::class, "getUserByIdV2"]);
Route::get('/v1.0/users/generate/employee-id', [UserManagementController::class, "generateEmployeeId"]);
Route::get('/v1.0/users/validate/employee-id/{employee_id}', [UserManagementController::class, "validateEmployeeId"]);

Route::get('/v1.0/users/get-leave-details/{id}', [UserManagementController::class, "getLeaveDetailsByUserId"]);
Route::get('/v1.0/users/get-holiday-details/{id}', [UserManagementController::class, "getholidayDetailsByUserId"]);



// ********************************************
// user management section --role
// ********************************************
Route::get('/v1.0/initial-role-permissions', [RolesController::class, "getInitialRolePermissions"]);
Route::get('/v1.0/initial-permissions', [RolesController::class, "getInitialPermissions"]);
Route::post('/v1.0/roles', [RolesController::class, "createRole"]);
Route::put('/v1.0/roles', [RolesController::class, "updateRole"]);
Route::get('/v1.0/roles', [RolesController::class, "getRoles"]);

Route::get('/v1.0/roles/{id}', [RolesController::class, "getRoleById"]);
Route::delete('/v1.0/roles/{ids}', [RolesController::class, "deleteRolesByIds"]);
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
// end user management section
// %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user document  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/user-documents/single-file-upload', [UserDocumentController::class, "createUserDocumentFileSingle"]);
Route::post('/v1.0/user-documents', [UserDocumentController::class, "createUserDocument"]);
Route::put('/v1.0/user-documents', [UserDocumentController::class, "updateUserDocument"]);
Route::get('/v1.0/user-documents', [UserDocumentController::class, "getUserDocuments"]);
Route::get('/v1.0/user-documents/{id}', [UserDocumentController::class, "getUserDocumentById"]);
Route::delete('/v1.0/user-documents/{ids}', [UserDocumentController::class, "deleteUserDocumentsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user document management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user job history  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/user-job-histories', [UserJobHistoryController::class, "createUserJobHistory"]);
Route::put('/v1.0/user-job-histories', [UserJobHistoryController::class, "updateUserJobHistory"]);
Route::get('/v1.0/user-job-histories', [UserJobHistoryController::class, "getUserJobHistories"]);
Route::get('/v1.0/user-job-histories/{id}', [UserJobHistoryController::class, "getUserJobHistoryById"]);
Route::delete('/v1.0/user-job-histories/{ids}', [UserJobHistoryController::class, "deleteUserJobHistoriesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user job history management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user education history  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/user-education-histories', [UserEducationHistoryController::class, "createUserEducationHistory"]);
Route::put('/v1.0/user-education-histories', [UserEducationHistoryController::class, "updateUserEducationHistory"]);
Route::get('/v1.0/user-education-histories', [UserEducationHistoryController::class, "getUserEducationHistories"]);
Route::get('/v1.0/user-education-histories/{id}', [UserEducationHistoryController::class, "getUserEducationHistoryById"]);
Route::delete('/v1.0/user-education-histories/{ids}', [UserEducationHistoryController::class, "deleteUserEducationHistoriesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user education history management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user address history  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/user-address-histories', [UserAddressHistoryController::class, "createUserAddressHistory"]);
Route::put('/v1.0/user-address-histories', [UserAddressHistoryController::class, "updateUserAddressHistory"]);
Route::get('/v1.0/user-address-histories', [UserAddressHistoryController::class, "getUserAddressHistories"]);
Route::get('/v1.0/user-address-histories/{id}', [UserAddressHistoryController::class, "getUserAddressHistoryById"]);
Route::delete('/v1.0/user-address-histories/{ids}', [UserAddressHistoryController::class, "deleteUserAddressHistoriesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user address history management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user passport history  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/user-passport-histories', [UserPassportHistoryController::class, "createUserPassportHistory"]);
Route::put('/v1.0/user-passport-histories', [UserPassportHistoryController::class, "updateUserPassportHistory"]);
Route::get('/v1.0/user-passport-histories', [UserPassportHistoryController::class, "getUserPassportHistories"]);
Route::get('/v1.0/user-passport-histories/{id}', [UserPassportHistoryController::class, "getUserPassportHistoryById"]);
Route::delete('/v1.0/user-passport-histories/{ids}', [UserPassportHistoryController::class, "deleteUserPassportHistoriesByIds"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user passport history management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user passport history  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/user-visa-histories', [UserVisaHistoryController::class, "createUserVisaHistory"]);
Route::put('/v1.0/user-visa-histories', [UserVisaHistoryController::class, "updateUserVisaHistory"]);
Route::get('/v1.0/user-visa-histories', [UserVisaHistoryController::class, "getUserVisaHistories"]);
Route::get('/v1.0/user-visa-histories/{id}', [UserVisaHistoryController::class, "getUserVisaHistoryById"]);
Route::delete('/v1.0/user-visa-histories/{ids}', [UserVisaHistoryController::class, "deleteUserVisaHistoriesByIds"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user passport history management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user sponsorship history history  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/user-sponsorship-histories', [UserSponsorshipHistoryController::class, "createUserSponsorshipHistory"]);
Route::put('/v1.0/user-sponsorship-histories', [UserSponsorshipHistoryController::class, "updateUserSponsorshipHistory"]);
Route::get('/v1.0/user-sponsorship-histories', [UserSponsorshipHistoryController::class, "getUserSponsorshipHistories"]);
Route::get('/v1.0/user-sponsorship-histories/{id}', [UserSponsorshipHistoryController::class, "getUserSponsorshipHistoryById"]);
Route::delete('/v1.0/user-sponsorship-histories/{ids}', [UserSponsorshipHistoryController::class, "deleteUserSponsorshipHistoriesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user sponsorship  history management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user asset  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/user-assets/single-file-upload', [UserAssetController::class, "createUserAssetFileSingle"]);
Route::post('/v1.0/user-assets', [UserAssetController::class, "createUserAsset"]);
Route::put('/v1.0/user-assets/add-existing', [UserAssetController::class, "addExistingUserAsset"]);
Route::put('/v1.0/user-assets', [UserAssetController::class, "updateUserAsset"]);
Route::get('/v1.0/user-assets', [UserAssetController::class, "getUserAssets"]);
Route::get('/v1.0/user-assets/{id}', [UserAssetController::class, "getUserAssetById"]);
Route::delete('/v1.0/user-assets/{ids}', [UserAssetController::class, "deleteUserAssetsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user asset management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// user social site  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/user-social-sites', [UserSocialSiteController::class, "createUserSocialSite"]);
Route::put('/v1.0/user-social-sites', [UserSocialSiteController::class, "updateUserSocialSite"]);
Route::get('/v1.0/user-social-sites', [UserSocialSiteController::class, "getUserSocialSites"]);
Route::get('/v1.0/user-social-sites/{id}', [UserSocialSiteController::class, "getUserSocialSiteById"]);
Route::delete('/v1.0/user-social-sites/{ids}', [UserSocialSiteController::class, "deleteUserSocialSitesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end user social site management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


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
// Garage Time Management
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::patch('/v1.0/business-times', [BusinessTimesController::class, "updateBusinessTimes"]);
Route::get('/v1.0/business-times', [BusinessTimesController::class, "getBusinessTimes"]);


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// Garage Background Image Management
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

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
// asset type  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/asset-types', [AssetTypeController::class, "createAssetType"]);
Route::put('/v1.0/asset-types', [AssetTypeController::class, "updateAssetType"]);
Route::get('/v1.0/asset-types', [AssetTypeController::class, "getAssetTypes"]);
Route::get('/v1.0/asset-types/{id}', [AssetTypeController::class, "getAssetTypeById"]);
Route::delete('/v1.0/asset-types/{ids}', [AssetTypeController::class, "deleteAssetTypesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end asset type  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@





// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// department  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/departments', [DepartmentController::class, "createDepartment"]);
Route::put('/v1.0/departments', [DepartmentController::class, "updateDepartment"]);
Route::put('/v1.0/departments/toggle-active', [DepartmentController::class, "toggleActiveDepartment"]);
Route::get('/v1.0/departments', [DepartmentController::class, "getDepartments"]);
Route::get('/v2.0/departments', [DepartmentController::class, "getDepartmentsV2"]);
Route::get('/v3.0/departments', [DepartmentController::class, "getDepartmentsV3"]);
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

Route::get('/v1.0/work-shifts/get-by-user-id/{user_id}', [WorkShiftController::class, "getWorkShiftByUserId"]);

Route::delete('/v1.0/work-shifts/{ids}', [WorkShiftController::class, "deleteWorkShiftsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end work shift  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// announcements  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/announcements', [AnnouncementController::class, "createAnnouncement"]);
Route::put('/v1.0/announcements', [AnnouncementController::class, "updateAnnouncement"]);
Route::get('/v1.0/announcements', [AnnouncementController::class, "getAnnouncements"]);
Route::get('/v1.0/announcements/{id}', [AnnouncementController::class, "getAnnouncementById"]);
Route::delete('/v1.0/announcements/{ids}', [AnnouncementController::class, "deleteAnnouncementsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end announcements management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// job platform  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/job-platforms', [JobPlatformController::class, "createJobPlatform"]);
Route::put('/v1.0/job-platforms', [JobPlatformController::class, "updateJobPlatform"]);

Route::put('/v1.0/job-platforms/toggle-active', [JobPlatformController::class, "toggleActiveJobPlatform"]);
Route::get('/v1.0/job-platforms', [JobPlatformController::class, "getJobPlatforms"]);
Route::get('/v1.0/job-platforms/{id}', [JobPlatformController::class, "getJobPlatformById"]);
Route::delete('/v1.0/job-platforms/{ids}', [JobPlatformController::class, "deleteJobPlatformsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end job platform management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
//  social media management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/social-sites', [SocialSiteController::class, "createSocialSite"]);
Route::put('/v1.0/social-sites', [SocialSiteController::class, "updateSocialSite"]);
Route::get('/v1.0/social-sites', [SocialSiteController::class, "getSocialSites"]);
Route::get('/v1.0/social-sites/{id}', [SocialSiteController::class, "getSocialSiteById"]);
Route::delete('/v1.0/social-sites/{ids}', [SocialSiteController::class, "deleteSocialSitesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end social media management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// designation  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/designations', [DesignationController::class, "createDesignation"]);
Route::put('/v1.0/designations', [DesignationController::class, "updateDesignation"]);
Route::put('/v1.0/designations/toggle-active', [DesignationController::class, "toggleActiveDesignation"]);
Route::get('/v1.0/designations', [DesignationController::class, "getDesignations"]);
Route::get('/v1.0/designations/{id}', [DesignationController::class, "getDesignationById"]);
Route::delete('/v1.0/designations/{ids}', [DesignationController::class, "deleteDesignationsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end designation management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// designation  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/job-types', [JobTypeController::class, "createJobType"]);
Route::put('/v1.0/job-types', [JobTypeController::class, "updateJobType"]);
Route::put('/v1.0/job-types/toggle-active', [JobTypeController::class, "toggleActiveJobType"]);
Route::get('/v1.0/job-types', [JobTypeController::class, "getJobTypes"]);
Route::get('/v1.0/job-types/{id}', [JobTypeController::class, "getJobTypeById"]);
Route::delete('/v1.0/job-types/{ids}', [JobTypeController::class, "deleteJobTypesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end designation management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// work locations  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/work-locations', [WorkLocationController::class, "createWorkLocation"]);
Route::put('/v1.0/work-locations', [WorkLocationController::class, "updateWorkLocation"]);
Route::put('/v1.0/work-locations/toggle-active', [WorkLocationController::class, "toggleActiveWorkLocation"]);
Route::get('/v1.0/work-locations', [WorkLocationController::class, "getWorkLocations"]);
Route::get('/v1.0/work-locations/{id}', [WorkLocationController::class, "getWorkLocationById"]);
Route::delete('/v1.0/work-locations/{ids}', [WorkLocationController::class, "deleteWorkLocationsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end work locations management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// work locations  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/recruitment-processes', [RecruitmentProcessController::class, "createRecruitmentProcess"]);
Route::put('/v1.0/recruitment-processes', [RecruitmentProcessController::class, "updateRecruitmentProcess"]);
Route::put('/v1.0/recruitment-processes/toggle-active', [RecruitmentProcessController::class, "toggleActiveRecruitmentProcess"]);
Route::get('/v1.0/recruitment-processes', [RecruitmentProcessController::class, "getRecruitmentProcesses"]);
Route::get('/v1.0/recruitment-processes/{id}', [RecruitmentProcessController::class, "getRecruitmentProcessById"]);
Route::delete('/v1.0/recruitment-processes/{ids}', [RecruitmentProcessController::class, "deleteRecruitmentProcessesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end work locations management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// employment status management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/employment-statuses', [EmploymentStatusController::class, "createEmploymentStatus"]);
Route::put('/v1.0/employment-statuses', [EmploymentStatusController::class, "updateEmploymentStatus"]);
Route::put('/v1.0/employment-statuses/toggle-active', [EmploymentStatusController::class, "toggleActiveEmploymentStatus"]);
Route::get('/v1.0/employment-statuses', [EmploymentStatusController::class, "getEmploymentStatuses"]);
Route::get('/v1.0/employment-statuses/{id}', [EmploymentStatusController::class, "getEmploymentStatusById"]);
Route::delete('/v1.0/employment-statuses/{ids}', [EmploymentStatusController::class, "deleteEmploymentStatusesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end employment status  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// setting leave types  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/setting-leave-types', [SettingLeaveTypeController::class, "createSettingLeaveType"]);
Route::put('/v1.0/setting-leave-types', [SettingLeaveTypeController::class, "updateSettingLeaveType"]);
Route::put('/v1.0/setting-leave-types/toggle-active', [SettingLeaveTypeController::class, "toggleActiveSettingLeaveType"]);
Route::put('/v1.0/setting-leave-types/toggle-earning-enabled', [SettingLeaveTypeController::class, "toggleEarningEnabledSettingLeaveType"]);
Route::get('/v1.0/setting-leave-types', [SettingLeaveTypeController::class, "getSettingLeaveTypes"]);
Route::get('/v1.0/setting-leave-types/{id}', [SettingLeaveTypeController::class, "getSettingLeaveTypeById"]);
Route::delete('/v1.0/setting-leave-types/{ids}', [SettingLeaveTypeController::class, "deleteSettingLeaveTypesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end setting leave types management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// setting leave  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/setting-leave', [SettingLeaveController::class, "createSettingLeave"]);
// Route::put('/v1.0/setting-leave', [SettingLeaveTypeController::class, "updateSettingLeaveType"]);
 Route::get('/v1.0/setting-leave', [SettingLeaveController::class, "getSettingLeave"]);
// Route::get('/v1.0/setting-leave/{id}', [SettingLeaveTypeController::class, "getSettingLeaveTypeById"]);
// Route::delete('/v1.0/setting-leave/{ids}', [SettingLeaveTypeController::class, "deleteSettingLeaveTypesByIds"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end setting leave management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// leaves  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/leaves/multiple-file-upload', [LeaveController::class, "createLeaveFileMultiple"]);
Route::post('/v1.0/leaves', [LeaveController::class, "createLeave"]);
Route::put('/v1.0/leaves/approve', [LeaveController::class, "approveLeave"]);
Route::put('/v1.0/leaves/bypass', [LeaveController::class, "bypassLeave"]);
Route::put('/v1.0/leaves', [LeaveController::class, "updateLeave"]);
Route::get('/v1.0/leaves', [LeaveController::class, "getLeaves"]);
Route::get('/v2.0/leaves', [LeaveController::class, "getLeavesV2"]);
Route::get('/v3.0/leaves', [LeaveController::class, "getLeavesV3"]);
Route::get('/v4.0/leaves', [LeaveController::class, "getLeavesV4"]);
Route::get('/v1.0/leaves/{id}', [LeaveController::class, "getLeaveById"]);
Route::delete('/v1.0/leaves/{ids}', [LeaveController::class, "deleteLeavesByIds"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end leaves management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@







// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// setting attendance  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/setting-attendance', [SettingAttendanceController::class, "createSettingAttendance"]);
// Route::put('/v1.0/setting-leave', [SettingLeaveTypeController::class, "updateSettingLeaveType"]);
 Route::get('/v1.0/setting-attendance', [SettingAttendanceController::class, "getSettingAttendance"]);
// Route::get('/v1.0/setting-leave/{id}', [SettingLeaveTypeController::class, "getSettingLeaveTypeById"]);
// Route::delete('/v1.0/setting-leave/{ids}', [SettingLeaveTypeController::class, "deleteSettingLeaveTypesByIds"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end setting attendance management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// attendances  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/attendances', [AttendanceController::class, "createAttendance"]);
Route::post('/v1.0/attendances/multiple', [AttendanceController::class, "createMultipleAttendance"]);
Route::put('/v1.0/attendances', [AttendanceController::class, "updateAttendance"]);
Route::put('/v1.0/attendances/approve', [AttendanceController::class, "approveAttendance"]);
Route::get('/v1.0/attendances', [AttendanceController::class, "getAttendances"]);
Route::get('/v2.0/attendances', [AttendanceController::class, "getAttendancesV2"]);
Route::get('/v3.0/attendances', [AttendanceController::class, "getAttendancesV3"]);

Route::get('/v1.0/attendances/{id}', [AttendanceController::class, "getAttendanceById"]);
Route::delete('/v1.0/attendances/{ids}', [AttendanceController::class, "deleteAttendancesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end attendances management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// history details  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::get('/v1.0/histories/user-assets', [HistoryDetailsController::class, "getUserAssetHistory"]);
Route::get('/v1.0/histories/user-passport-details', [HistoryDetailsController::class, "getUserPassportDetailsHistory"]);
Route::get('/v1.0/histories/user-visa-details', [HistoryDetailsController::class, "getUserVisaDetailsHistory"]);
Route::get('/v1.0/histories/user-sponsorship-details', [HistoryDetailsController::class, "getUserSponsorshipDetailsHistory"]);
Route::get('/v1.0/histories/user-address-details', [HistoryDetailsController::class, "getUserAddressDetailsHistory"]);
Route::get('/v1.0/histories/user-attendance-details', [HistoryDetailsController::class, "getUserAttendanceDetailsHistory"]);
Route::get('/v1.0/histories/user-leave-details', [HistoryDetailsController::class, "getUserLeaveDetailsHistory"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end history details management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// setting payrun  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/setting-payrun', [SettingPayrollController::class, "createSettingPayrun"]);
// Route::put('/v1.0/setting-leave', [SettingLeaveTypeController::class, "updateSettingLeaveType"]);
 Route::get('/v1.0/setting-payrun', [SettingPayrollController::class, "getSettingPayrun"]);
// Route::get('/v1.0/setting-leave/{id}', [SettingLeaveTypeController::class, "getSettingLeaveTypeById"]);
// Route::delete('/v1.0/setting-leave/{ids}', [SettingLeaveTypeController::class, "deleteSettingLeaveTypesByIds"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end setting payrun management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@


// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// setting payslip  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/setting-payslip/upload-logo', [SettingPayrollController::class, "createSettingPayslipUploadLogo"]);
Route::post('/v1.0/setting-payslip', [SettingPayrollController::class, "createSettingPayslip"]);
// Route::put('/v1.0/setting-leave', [SettingLeaveTypeController::class, "updateSettingLeaveType"]);
 Route::get('/v1.0/setting-payslip', [SettingPayrollController::class, "getSettingPayslip"]);
// Route::get('/v1.0/setting-leave/{id}', [SettingLeaveTypeController::class, "getSettingLeaveTypeById"]);
// Route::delete('/v1.0/setting-leave/{ids}', [SettingLeaveTypeController::class, "deleteSettingLeaveTypesByIds"]);
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end setting payslip management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// job listings  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/job-listings', [JobListingController::class, "createJobListing"]);
Route::put('/v1.0/job-listings', [JobListingController::class, "updateJobListing"]);
Route::get('/v1.0/job-listings', [JobListingController::class, "getJobListings"]);
Route::get('/v1.0/job-listings/{id}', [JobListingController::class, "getJobListingById"]);
Route::delete('/v1.0/job-listings/{ids}', [JobListingController::class, "deleteJobListingsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end job listings  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@






// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// candidates  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
Route::post('/v1.0/candidates/multiple-file-upload', [CandidateController::class, "createCandidateFileMultiple"]);
Route::post('/v1.0/candidates', [CandidateController::class, "createCandidate"]);
Route::put('/v1.0/candidates', [CandidateController::class, "updateCandidate"]);
Route::get('/v1.0/candidates', [CandidateController::class, "getCandidates"]);
Route::get('/v1.0/candidates/{id}', [CandidateController::class, "getCandidateById"]);
Route::delete('/v1.0/candidates/{ids}', [CandidateController::class, "deleteCandidatesByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end candidates management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@



// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// project  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/projects', [ProjectController::class, "createProject"]);
Route::put('/v1.0/projects', [ProjectController::class, "updateProject"]);
Route::get('/v1.0/projects', [ProjectController::class, "getProjects"]);
Route::get('/v1.0/projects/{id}', [ProjectController::class, "getProjectById"]);
Route::delete('/v1.0/projects/{ids}', [ProjectController::class, "deleteProjectsByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end project  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@




// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// project  management section
// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@

Route::post('/v1.0/tasks', [TaskController::class, "createTask"]);
Route::put('/v1.0/tasks', [TaskController::class, "updateTask"]);
Route::get('/v1.0/tasks', [TaskController::class, "getTasks"]);
Route::get('/v1.0/tasks/{id}', [TaskController::class, "getTaskById"]);
Route::delete('/v1.0/tasks/{ids}', [TaskController::class, "deleteTasksByIds"]);

// @@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@@
// end project  management section
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


