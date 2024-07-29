<?php

namespace App\Http\Utils;

use App\Models\EmailTemplate;

trait SetupUtil
{
use BasicEmailUtil;

    public function storeEmailTemplates() {
        $email_templates = [
            $this->prepareEmailTemplateData("business_welcome_mail",NULL),
            $this->prepareEmailTemplateData("email_verification_mail",NULL),
            $this->prepareEmailTemplateData("reset_password_mail",NULL),
            $this->prepareEmailTemplateData("send_password_mail",NULL),
            $this->prepareEmailTemplateData("job_application_received_mail", NULL),
        ];

        EmailTemplate::insert($email_templates);



    }

}
