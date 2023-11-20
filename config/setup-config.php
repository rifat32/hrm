<?php

return [
    "roles_permission" => [
        [
            "role" => "superadmin",
            "permissions" => [
                "global_business_background_image_create",
                "global_business_background_image_view",
       "user_create",
       "user_update",
       "user_view",
       "user_delete",

       "role_create",
       "role_update",
       "role_view",
       "role_delete",

       "business_create",
       "business_update",
       "business_view",
       "business_delete",

       "template_create",
       "template_update",
       "template_view",
       "template_delete",

       "payment_type_create",
       "payment_type_update",
       "payment_type_view",
       "payment_type_delete",


"product_category_create",
"product_category_update",
"product_category_view",
"product_category_delete",

"product_create",
"product_update",
"product_view",
"product_delete",

"job_platform_create",
"job_platform_update",
"job_platform_view",
"job_platform_delete",


"designation_create",
"designation_update",
"designation_view",
"designation_delete",

"employment_status_create",
"employment_status_update",
"employment_status_view",
"employment_status_delete",

"setting_leave_type_create",
"setting_leave_type_update",
"setting_leave_type_view",
"setting_leave_type_delete",
"setting_leave_create",

"setting_attendance_create",

"setting_payroll_create",

            ],
        ],

        [
            "role" => "reseller",
            "permissions" => [


                "user_create",
                "user_update",
                "user_view",
                "user_delete",

                "business_create",
                "business_update",
                "business_view",
                "business_delete",

            ],
        ],

        [
            "role" => "business_owner",
            "permissions" => [


                "user_create",
                "user_update",
                "user_view",
                "user_delete",

                "role_create",
                "role_update",
                "role_view",
                "role_delete",

                "business_update",
                "business_view",
                "product_category_view",
                "global_business_background_image_view",



                "department_create",
                "department_update",
                "department_view",
                "department_delete",

                "job_listing_create",
                "job_listing_update",
                "job_listing_view",
                "job_listing_delete",

                "holiday_create",
                "holiday_update",
                "holiday_view",
                "holiday_delete",

                "work_shift_create",
                "work_shift_update",
                "work_shift_view",
                "work_shift_delete",

                "announcement_create",
                "announcement_update",
                "announcement_view",
                "announcement_delete",



                "job_platform_create",
                "job_platform_update",
                "job_platform_view",
                "job_platform_delete",


                "designation_create",
                "designation_update",
                "designation_view",
                "designation_delete",




                "employment_status_create",
                "employment_status_update",
                "employment_status_view",
                "employment_status_delete",

                "setting_leave_type_create",
                "setting_leave_type_update",
                "setting_leave_type_view",
                "setting_leave_type_delete",
                "setting_leave_create",



                "leave_create",
                "leave_update",
                "leave_view",
                "leave_delete",



                "setting_attendance_create",

                "attendance_create",
"attendance_update",
"attendance_view",
"attendance_delete",


"setting_payroll_create",
            ],
        ],

        [
            "role" => "business_admin",
            "permissions" => [


                "user_create",
                "user_update",
                "user_view",
                "user_delete",

                "role_create",
                "role_update",
                "role_view",
                "role_delete",

                "business_update",
                "business_view",
                "product_category_view",
                "global_business_background_image_view",



                "department_create",
                "department_update",
                "department_view",
                "department_delete",

                "job_listing_create",
                "job_listing_update",
                "job_listing_view",
                "job_listing_delete",

                "holiday_create",
                "holiday_update",
                "holiday_view",
                "holiday_delete",

                "work_shift_create",
                "work_shift_update",
                "work_shift_view",
                "work_shift_delete",

                "announcement_create",
                "announcement_update",
                "announcement_view",
                "announcement_delete",

                "job_platform_create",
                "job_platform_update",
                "job_platform_view",
                "job_platform_delete",


                "designation_create",
                "designation_update",
                "designation_view",
                "designation_delete",

                "employment_status_create",
                "employment_status_update",
                "employment_status_view",
                "employment_status_delete",

"setting_leave_type_create",
"setting_leave_type_update",
"setting_leave_type_view",
"setting_leave_type_delete",
"setting_leave_create",



"leave_create",
"leave_update",
"leave_view",
"leave_delete",


"setting_attendance_create",

"attendance_create",
"attendance_update",
"attendance_view",
"attendance_delete",

"setting_payroll_create",


            ],
        ],

        [
            "role" => "business_employee",
            "permissions" => [




            ],
        ],


    ],
    "roles" => [
        "superadmin",
        'reseller',
        "business_owner",
        "business_admin",
        "business_employee",

    ],
    "permissions" => [
        "global_business_background_image_create",
        "global_business_background_image_view",

       "user_create",
       "user_update",
       "user_view",
       "user_delete",


       "role_create",
       "role_update",
       "role_view",
       "role_delete",

       "business_create",
       "business_update",
       "business_view",
       "business_delete",


       "template_create",
       "template_update",
       "template_view",
       "template_delete",



       "payment_type_create",
       "payment_type_update",
       "payment_type_view",
       "payment_type_delete",


       "product_category_create",
       "product_category_update",
       "product_category_view",
       "product_category_delete",

       "product_create",
       "product_update",
       "product_view",
       "product_delete",


       "department_create",
       "department_update",
       "department_view",
       "department_delete",

       "job_listing_create",
       "job_listing_update",
       "job_listing_view",
       "job_listing_delete",

       "holiday_create",
       "holiday_update",
       "holiday_view",
       "holiday_delete",

       "work_shift_create",
       "work_shift_update",
       "work_shift_view",
       "work_shift_delete",


       "announcement_create",
       "announcement_update",
       "announcement_view",
       "announcement_delete",


       "job_platform_create",
       "job_platform_update",
       "job_platform_view",
       "job_platform_delete",


       "designation_create",
       "designation_update",
       "designation_view",
       "designation_delete",



       "employment_status_create",
       "employment_status_update",
       "employment_status_view",
       "employment_status_delete",

       "setting_leave_type_create",
"setting_leave_type_update",
"setting_leave_type_view",
"setting_leave_type_delete",
"setting_leave_create",



"leave_create",
"leave_update",
"leave_view",
"leave_delete",


"setting_attendance_create",

"attendance_create",
"attendance_update",
"attendance_view",
"attendance_delete",

"setting_payroll_create",

    ],
    "permissions_titles" => [
        "global_business_background_image_create" => "Can create global business background image",
        "global_business_background_image_view" => "Can view global business background image",

        "user_create" => "Can create user",
        "user_update" => "Can update user",
        "user_view" => "Can view user",
        "user_delete" => "Can delete user",

        "role_create" => "Can create role",
        "role_update" => "Can update role",
        "role_view" => "Can view role",
        "role_delete" => "Can delete role",

        "business_create" => "Can create business",
        "business_update" => "Can update business",
        "business_view" => "Can view business",
        "business_delete" => "Can delete business",

        "template_create" => "Can create template",
        "template_update" => "Can update template",
        "template_view" => "Can view template",
        "template_delete" => "Can delete template",

        "payment_type_create" => "Can create payment type",
        "payment_type_update" => "Can update payment type",
        "payment_type_view" => "Can view payment type",
        "payment_type_delete" => "Can delete payment type",

        "product_category_create" => "Can create product category",
        "product_category_update" => "Can update product category",
        "product_category_view" => "Can view product category",
        "product_category_delete" => "Can delete product category",

        "product_create" => "Can create product",
        "product_update" => "Can update product",
        "product_view" => "Can view product",
        "product_delete" => "Can delete product",

        "department_create" => "Can create department",
        "department_update" => "Can update department",
        "department_view" => "Can view department",
        "department_delete" => "Can delete department",

        "job_listing_create" => "Can create job listing",
        "job_listing_update" => "Can update job listing",
        "job_listing_view" => "Can view job listing",
        "job_listing_delete" => "Can delete job listing",


        "holiday_create" => "Can create holiday",
        "holiday_update" => "Can update holiday",
        "holiday_view" => "Can view holiday",
        "holiday_delete" => "Can delete holiday",

        "work_shift_create" => "Can create work shift",
        "work_shift_update" => "Can update work shift",
        "work_shift_view" => "Can view work shift",
        "work_shift_delete" => "Can delete work shift",

        "announcement_create" => "Can create announcement",
        "announcement_update" => "Can update announcement",
        "announcement_view" => "Can view announcement",
        "announcement_delete" => "Can delete announcement",




        "job_platform_create" => "Can create job platform",
        "job_platform_update" => "Can update job platform",
        "job_platform_view" => "Can view job platform",
        "job_platform_delete" => "Can delete job platform",


        "designation_create" => "Can create designation",
        "designation_update" => "Can update designation",
        "designation_view" => "Can view designation",
        "designation_delete" => "Can delete designation",

        "employment_status_create" => "Can create employment status",
        "employment_status_update" => "Can update employment status",
        "employment_status_view" => "Can view employment status",
        "employment_status_delete" => "Can delete employment status",

        "setting_leave_type_create" => "Can create setting leave type",
        "setting_leave_type_update" => "Can update setting leave type",
        "setting_leave_type_view" => "Can view setting leave type",
        "setting_leave_type_delete" => "Can delete setting leave type",
        "setting_leave_create" => "Can create setting leave",




        "leave_create" => "Can create leave",
        "leave_update" => "Can update leave",
        "leave_view" => "Can view leave",
        "leave_delete" => "Can delete leave",

        "setting_attendance_create" => "Can create setting attendance",


        "attendance_create" => "Can create attendance",
        "attendance_update" => "Can update attendance",
        "attendance_view" => "Can view attendance",
        "attendance_delete" => "Can delete attendance",


        "setting_payroll_create" => "Can create setting payroll",





    ],
    "unchangeable_roles" => [
        // "superadmin",
        // "reseller"
    ],
    "unchangeable_permissions" => [
        // "business_update",
        // "business_view",
    ],
    "user_image_location" => "user_image",

    "leave_files_location" => "leave_files",

    "payslip_logo_location" => "payslip_logo",






    "business_background_image_location" => "business_background_image",
    "business_background_image_location_full" => "business_background_image/business_background_image.jpeg",

    "temporary_files_location" => "temporary_files",
];
