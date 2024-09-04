<?php

namespace App\Http\Controllers;

use App\Http\Utils\BasicEmailUtil;
use App\Http\Utils\SetupUtil;
use App\Models\Business;
use App\Models\BusinessPensionHistory;
use App\Models\Candidate;
use App\Models\CandidateRecruitmentProcess;
use App\Models\EmailTemplate;
use App\Models\EmployeePensionHistory;
use App\Models\EmployeeRightToWorkHistory;
use App\Models\EmployeeVisaDetailHistory;
use App\Models\Leave;
use App\Models\Module;
use App\Models\Payslip;
use App\Models\RecruitmentProcess;
use App\Models\ServicePlan;
use App\Models\ServicePlanModule;
use App\Models\SettingPayslip;
use App\Models\UserAsset;
use App\Models\UserDocument;
use App\Models\UserEducationHistory;
use App\Models\UserRecruitmentProcess;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class UpdateDatabaseController extends Controller
{
    use BasicEmailUtil, SetupUtil;

    private function storeEmailTemplates()
    {



        // Prepare initial email templates
        $email_templates = collect([
            $this->prepareEmailTemplateData("business_welcome_mail", NULL),
            $this->prepareEmailTemplateData("email_verification_mail", NULL),
            $this->prepareEmailTemplateData("reset_password_mail", NULL),
            $this->prepareEmailTemplateData("send_password_mail", NULL),
            $this->prepareEmailTemplateData("job_application_received_mail", NULL),

        ]);

        // Fetch business IDs and prepare business-specific email templates
        $business_email_templates = Business::pluck("id")->flatMap(function ($business_id) {
            return [
                $this->prepareEmailTemplateData("reset_password_mail", $business_id),
                $this->prepareEmailTemplateData("send_password_mail", $business_id),
                $this->prepareEmailTemplateData("job_application_received_mail", $business_id),

            ];
        });

        // Combine the two collections
        $email_templates = $email_templates->merge($business_email_templates);


        // Insert all email templates at once
        EmailTemplate::upsert(
            $email_templates->toArray(),
            ['type', 'business_id'], // Columns that determine uniqueness
            [
                "name",
                // "type",
                "template",
                "is_active",
                "is_default",
                // "business_id",
                'wrapper_id',
                "template_variables"
            ] // Columns to update if a match is found
        );
    }

    public function updateDatabase()
    {

        $i = 1;
        for ($i; $i <= 20; $i++) {

            if ($i == 1) {
                $modules = config("setup-config.system_modules");
                foreach ($modules as $module) {
                    $module_exists = Module::where([
                        "name" => $module
                    ])
                        ->exists();

                    if (!$module_exists) {
                        Module::create([
                            "name" => $module,
                            "is_enabled" => 1,
                            'created_by' => 1,
                        ]);
                    }
                }
            }


            // @@@@@@@@@@@@@@@@@@@@  number - 1 @@@@@@@@@@@@@@@@@@@@@


            if ($i == 1) {
                $this->storeEmailTemplates();
            }
            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 2) {
                // Check and add the 'number_of_employees_allowed' column if it doesn't exist
                if (!Schema::hasColumn('businesses', 'number_of_employees_allowed')) {
                    DB::statement("ALTER TABLE businesses ADD COLUMN number_of_employees_allowed INTEGER DEFAULT 0");
                }
            }



            if ($i == 4) {

                // Define the table and foreign key
                $table = 'letter_templates';
                $foreignKey = 'letter_templates_business_id_foreign';

                // Check if the foreign key exists before trying to drop it
                $foreignKeyExists = DB::select(DB::raw("
    SELECT CONSTRAINT_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_NAME = '{$table}'
    AND CONSTRAINT_NAME = '{$foreignKey}'
"));

                if (!empty($foreignKeyExists)) {
                    // Drop the existing foreign key constraint
                    DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreignKey}");
                }

                // Modify the column to be nullable
                DB::statement("
    ALTER TABLE {$table}
    MODIFY COLUMN business_id BIGINT UNSIGNED NULL;
");

                // Re-add the foreign key constraint
                DB::statement("
    ALTER TABLE {$table}
    ADD CONSTRAINT {$foreignKey}
    FOREIGN KEY (business_id) REFERENCES businesses(id)
    ON DELETE CASCADE;
");
            }


            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 6) {
                // Check and add the 'in_geolocation' column if it doesn't exist
                if (!Schema::hasColumn('attendance_histories', 'in_geolocation')) {
                    DB::statement("ALTER TABLE attendance_histories ADD COLUMN in_geolocation VARCHAR(255) NULL");
                }

                // Check and add the 'out_geolocation' column if it doesn't exist
                if (!Schema::hasColumn('attendance_histories', 'out_geolocation')) {
                    DB::statement("ALTER TABLE attendance_histories ADD COLUMN out_geolocation VARCHAR(255) NULL");
                }
            }

            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 7) {
                // Check if the 'feedback' column exists
                if (Schema::hasColumn('candidates', 'feedback')) {
                    // Make the 'feedback' column nullable
                    DB::statement('ALTER TABLE candidates MODIFY feedback VARCHAR(255) NULL');
                }
            }
            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 8) {
                // // Check if the 'is_default' column exists
                // if (!Schema::hasColumn('asset_types', 'is_default')) {
                //     // Add the 'is_default' column as a boolean with a default value of 1
                //     DB::statement("ALTER TABLE asset_types ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 1");
                // }

                // Make the 'feedback' column nullable
                DB::statement('ALTER TABLE asset_types MODIFY business_id BIGINT(20) UNSIGNED NULL');


                $this->setupAssetTypes();
            }
            // @@@@@@@@@@@@@@@@@@@@  number - 2 @@@@@@@@@@@@@@@@@@@@@
            if ($i == 9) {
                if (Schema::hasColumn('comments', 'description')) {
                    DB::statement('ALTER TABLE comments MODIFY description LONGTEXT NULL');
                }
            }

            if ($i == 10) {
                EmailTemplate::where("type", "send_password_mail")->delete();
            }

            // @@@@@@@@@@@@@@@@@@@@  number - 3 @@@@@@@@@@@@@@@@@@@@@


            if ($i == 11) {
                $foreignKeys = [
                    'disabled_setting_leave_types' => 'disabled_setting_leave_types_business_id_foreign',
                    'disabled_task_categories' => 'disabled_task_categories_business_id_foreign',
                    'disabled_letter_templates' => 'disabled_letter_templates_business_id_foreign',
                    'disabled_asset_types' => 'disabled_asset_types_business_id_foreign',
                    'disabled_designations' => 'disabled_designations_business_id_foreign',
                    'disabled_employment_statuses' => 'disabled_employment_statuses_business_id_foreign',
                    'disabled_job_platforms' => 'disabled_job_platforms_business_id_foreign',
                    'disabled_job_types' => 'disabled_job_types_business_id_foreign',
                    'disabled_work_locations' => 'disabled_work_locations_business_id_foreign',
                    'disabled_recruitment_processes' => 'disabled_recruitment_processes_business_id_foreign',
                    'disabled_banks' => 'disabled_banks_business_id_foreign',
                    'disabled_termination_types' => 'disabled_termination_types_business_id_foreign',
                    'disabled_termination_reasons' => 'disabled_termination_reasons_business_id_foreign',
                ];

                // Disable foreign key checks to avoid errors during deletion
                DB::statement('SET FOREIGN_KEY_CHECKS=0;');

                // Delete invalid records from tables
                foreach ($foreignKeys as $table => $foreignKey) {
                    // Delete records with invalid business_id
                    DB::statement("
              DELETE FROM {$table}
              WHERE business_id IS NOT NULL
              AND business_id NOT IN (SELECT id FROM businesses);
          ");
                }

                // Enable foreign key checks
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');

                // Drop foreign key constraints if they exist
                foreach ($foreignKeys as $table => $foreignKey) {
                    try {
                        // Check if the foreign key exists before attempting to drop it
                        $foreignKeyExists = DB::select(DB::raw("
                  SELECT CONSTRAINT_NAME
                  FROM information_schema.KEY_COLUMN_USAGE
                  WHERE TABLE_NAME = '{$table}'
                  AND CONSTRAINT_NAME = '{$foreignKey}'
              "));

                        if (!empty($foreignKeyExists)) {
                            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$foreignKey}");
                        } else {
                        }
                    } catch (\Exception $e) {
                        // Log the error or handle it as needed
                        echo "Failed to drop foreign key '{$foreignKey}' on table '{$table}': " . $e->getMessage();
                    }
                }

                // Re-add foreign key constraints
                foreach ($foreignKeys as $table => $foreignKey) {
                    try {
                        DB::statement("ALTER TABLE {$table} ADD CONSTRAINT {$foreignKey} FOREIGN KEY (business_id) REFERENCES businesses(id) ON DELETE CASCADE");
                    } catch (\Exception $e) {
                        // Log the error or handle it as needed
                        echo "Failed to add foreign key '{$foreignKey}' on table '{$table}': " . $e->getMessage();
                    }
                }
            }

            if ($i == 13) {
                if (Schema::hasColumn('notifications', 'entity_name')) {
                    // Modify the column type to VARCHAR in MySQL
                    DB::statement('ALTER TABLE notifications MODIFY entity_name VARCHAR(255) NULL');
                }
                DB::statement("
                ALTER Table tasks
                MODIFY COLUMN task_category_id BIGINT UNSIGNED NULL;
            ");
            }






            if ($i == 12) {
                $modules = Module::where('is_enabled', 1)->pluck('id');

                $service_plan = ServicePlan::first(); // Retrieve the first service plan

                if ($service_plan) {
                    $service_plan->update([
                        'name' => 'Standard Plan',
                        'description' => '',
                        'set_up_amount' => 100,
                        'number_of_employees_allowed' => 100,
                        'duration_months' => 1,
                        'price' => 20,
                        'business_tier_id' => 1,
                        'created_by' => 1,
                    ]);

                    $service_plan_modules = $modules->map(function ($module_id) use ($service_plan) {
                        return [
                            'is_enabled' => 1,
                            'service_plan_id' => $service_plan->id,
                            'module_id' => $module_id,
                            'created_by' => 1,
                        ];
                    })->toArray();

                    ServicePlanModule::insert($service_plan_modules);
                } else {
                    $this->setupServicePlan();
                    $service_plan = ServicePlan::first(); // Retrieve the first service plan
                    if (empty($service_plan)) {
                        throw new Exception("service plan issues");
                    }
                }


                $businesses = Business::whereHas("owner")
                    ->get(["id", "owner_id", "service_plan_id", "reseller_id", "created_by"]);

                $this->defaultDataSetupForBusinessV2($businesses, $service_plan);
            }
        }




        return "ok";
    }


    public function moveFilesToBusinessFolder(array $fileNames, $businessId)
    {
        // Define the base directory for files
        $baseDirectory = public_path();

        // Construct the new base directory path with the business ID
        $newBaseDirectory = public_path("{$businessId}");

        // Ensure the new base directory exists
        if (!File::exists($newBaseDirectory)) {
            File::makeDirectory($newBaseDirectory, 0755, true);
        }

        foreach ($fileNames as $fileName) {
            // Construct the old file path
            $oldFilePath = $baseDirectory . DIRECTORY_SEPARATOR . $fileName;

            // Check if the file exists at the old path
            if (File::exists($oldFilePath)) {
                // Construct the new file path
                $relativeFilePath = $fileName; // The relative path to the file within the base directory
                $newFilePath = $newBaseDirectory . DIRECTORY_SEPARATOR . $relativeFilePath;

                // Ensure the new directory exists
                $newDirectory = dirname($newFilePath);
                if (!File::exists($newDirectory)) {
                    File::makeDirectory($newDirectory, 0755, true);
                }

                // Move the file to the new location
                try {
                    File::move($oldFilePath, $newFilePath);
                    Log::info("File moved successfully from {$oldFilePath} to {$newFilePath}");
                } catch (Exception $e) {
                    // Log any exceptions that occur during the file move
                    Log::error("Failed to move file from {$oldFilePath} to {$newFilePath}: " . $e->getMessage());
                }
            } else {
                // Log an error if the file does not exist
                Log::error("File does not exist: {$oldFilePath}");
            }
        }
    }

    public function moveFilesAndUpdateDatabaseForBusiness($businessId)
    {
        $modelData = Business::where("id", $businessId)->get(["id", "logo", "image", "background_image"]);

        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->logo,
                $data->image,
                $data->background_image
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            $data->update([
                'logo' => $businessId . DIRECTORY_SEPARATOR . $data->logo,
                'image' => $businessId . DIRECTORY_SEPARATOR . $data->image,
                'background_image' => $businessId . DIRECTORY_SEPARATOR . $data->background_image,
            ]);
        });
    }

    public function moveFilesAndUpdateDatabaseForBusinessPensionHistory($businessId)
    {
        $modelData = BusinessPensionHistory::where("business_id", $businessId)->get(["id", "pension_scheme_letters"]);

        $modelData->each(function ($data) use ($businessId) {
            // Convert pension_scheme_letters to an array if it's not already one
            $pensionSchemeLetters = is_array($data->pension_scheme_letters) ? $data->pension_scheme_letters : json_decode($data->pension_scheme_letters, true);

            if (is_array($pensionSchemeLetters)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($pensionSchemeLetters, $businessId);

                // Update the paths in the database
                $updatedLetters = collect($pensionSchemeLetters)->map(function ($letter) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $letter;
                })->toArray();

                $data->update([
                    'pension_scheme_letters' => json_encode($updatedLetters)
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForCandidateRecruitmentProcess($businessId)
    {
        $modelData = CandidateRecruitmentProcess::whereHas("candidate", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }
    public function moveFilesAndUpdateDatabaseForCandidate($businessId)
    {
        $modelData = Candidate::where("business_id", $businessId)->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForLeave($businessId)
    {
        $modelData = Leave::where("business_id", $businessId)->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }
    public function moveFilesAndUpdateDatabaseForSettingPayslip($businessId)
    {
        $modelData = SettingPayslip::where("business_id", $businessId)->get(["id", "logo"]);

        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->logo
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            $data->update([
                'logo' => $businessId . DIRECTORY_SEPARATOR . $data->logo
            ]);
        });
    }

    public function moveFilesAndUpdateDatabaseForUserAsset($businessId)
    {
        $modelData = UserAsset::where("business_id", $businessId)->get(["id", "image"]);

        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->image
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            $data->update([
                'image' => $businessId . DIRECTORY_SEPARATOR . $data->image
            ]);
        });
    }
    public function moveFilesAndUpdateDatabaseForUserDocument($businessId)
    {
        $modelData = UserDocument::whereHas('user', function ($query) use ($businessId) {
                $query->where("business_id", $businessId);
            })

            ->get(["id", "file_name"]);

        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->file_name
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
            $data->update([
                'logo' => $businessId . DIRECTORY_SEPARATOR . $data->file_name
            ]);
        });
    }
    public function moveFilesAndUpdateDatabaseForUserEducationHistory($businessId)
    {
        $modelData = UserEducationHistory::whereHas('user', function ($query) use ($businessId) {
                $query->where("business_id", $businessId);
            })
            ->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForUserRecruitmentProcess($businessId)
    {
        $modelData = UserRecruitmentProcess::whereHas("user", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "attachments"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $attachments = $data->attachments;

            if (is_array($attachments)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($attachments, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($attachments)->map(function ($attachment) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $attachment;
                })->toArray();

                $data->update([
                    'attachments' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }


    public function moveFilesAndUpdateDatabaseForEmployeeRightToWorkHistory($businessId)
    {
        $modelData = EmployeeRightToWorkHistory::whereHas("employee", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "right_to_work_docs"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $right_to_work_docs = $data->right_to_work_docs;

            if (is_array($right_to_work_docs)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($right_to_work_docs, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($right_to_work_docs)->map(function ($attachment) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $attachment;
                })->toArray();

                $data->update([
                    'right_to_work_docs' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForEmployeeVisaDetailHistory($businessId)
    {
        $modelData = EmployeeVisaDetailHistory::whereHas("employee", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "visa_docs"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $visa_docs = $data->visa_docs;

            if (is_array($visa_docs)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($visa_docs, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($visa_docs)->map(function ($attachment) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $attachment;
                })->toArray();

                $data->update([
                    'visa_docs' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }

    public function moveFilesAndUpdateDatabaseForPayslip($businessId)
    {
        $modelData = Payslip::whereHas("employee", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })
        ->get(["id", "payslip_file", "payment_record_file"]);


        // Collect all file paths that need to be moved
        $filePaths = $modelData->flatMap(function ($data) {
            return [
                $data->payslip_file,
            ];
        })->filter()->toArray(); // Filter out any null or empty paths

        // Move all files to the business folder
        $this->moveFilesToBusinessFolder($filePaths, $businessId);

        // Update the Business model with new file paths
        $modelData->each(function ($data) use ($businessId) {
              // Ensure attachments are handled as an array
              $payment_record_file = $data->payment_record_file;

              if (is_array($payment_record_file)) {
                  // Move files to the business folder
                  $this->moveFilesToBusinessFolder($payment_record_file, $businessId);

                  // Update the paths in the database
                  $updatedAttachments = collect($payment_record_file)->map(function ($attachment) use ($businessId) {
                      return $businessId . DIRECTORY_SEPARATOR . $attachment;
                  })->toArray();

                  $data->update([
                      'payment_record_file' => $updatedAttachments // Attachments should remain an array after update
                  ]);
              }


            $data->update([
                'payslip_file' => $businessId . DIRECTORY_SEPARATOR . $data->payslip_file,
            ]);

        });
    }


    public function moveFilesAndUpdateDatabaseForEmployeePensionHistory($businessId)
    {
        $modelData = EmployeePensionHistory::whereHas("employee", function ($query) use ($businessId) {
            $query->where("business_id", $businessId);
        })->get(["id", "pension_letters"]);

        $modelData->each(function ($data) use ($businessId) {
            // Ensure attachments are handled as an array
            $pension_letters = $data->pension_letters;

            if (is_array($pension_letters)) {
                // Move files to the business folder
                $this->moveFilesToBusinessFolder($pension_letters, $businessId);

                // Update the paths in the database
                $updatedAttachments = collect($pension_letters)->map(function ($attachment) use ($businessId) {
                    return $businessId . DIRECTORY_SEPARATOR . $attachment;
                })->toArray();

                $data->update([
                    'pension_letters' => $updatedAttachments // Attachments should remain an array after update
                ]);
            }
        });
    }




    public function updateDatabaseFilesForBusiness()
    {
        $businesses = Business::get(["id", "logo", "image", "background_image"]);

        $businesses->each(function ($business) {
            $this->moveFilesAndUpdateDatabaseForBusiness($business->id);
            $this->moveFilesAndUpdateDatabaseForBusinessPensionHistory($business->id);
            $this->moveFilesAndUpdateDatabaseForCandidateRecruitmentProcess($business->id);
            $this->moveFilesAndUpdateDatabaseForCandidate($business->id);
            $this->moveFilesAndUpdateDatabaseForLeave($business->id);
            $this->moveFilesAndUpdateDatabaseForSettingPayslip($business->id);
            $this->moveFilesAndUpdateDatabaseForUserAsset($business->id);
            $this->moveFilesAndUpdateDatabaseForUserDocument($business->id);
            $this->moveFilesAndUpdateDatabaseForUserEducationHistory($business->id);
            $this->moveFilesAndUpdateDatabaseForUserRecruitmentProcess($business->id);
            $this->moveFilesAndUpdateDatabaseForEmployeeRightToWorkHistory($business->id);
            $this->moveFilesAndUpdateDatabaseForEmployeeVisaDetailHistory($business->id);
            $this->moveFilesAndUpdateDatabaseForPayslip($business->id);
            $this->moveFilesAndUpdateDatabaseForEmployeePensionHistory($business->id);




        });
    }
}
