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



                "business_update",
                "business_view",
                "product_category_view",
                "global_business_background_image_view",



                "department_create",
                "department_update",
                "department_view",
                "department_delete",

                "holiday_create",
                "holiday_update",
                "holiday_view",
                "holiday_delete",

            ],
        ],






    ],
    "roles" => [
        "superadmin",
        'reseller',
        "business_owner",
        "manager",
        "employee",

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

       "holiday_create",
       "holiday_update",
       "holiday_view",
       "holiday_delete",

    ],
    "unchangeable_roles" => [
        "superadmin",
        "reseller"
    ],
    "unchangeable_permissions" => [
        "business_update",
        "business_view",
    ],
    "user_image_location" => "user_image",









    "business_background_image_location" => "business_background_image",
    "business_background_image_location_full" => "business_background_image/business_background_image.jpeg",

    "temporary_files_location" => "temporary_files",
];
