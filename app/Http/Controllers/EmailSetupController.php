<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class EmailSetupController extends Controller
{
    public function convertBladeToString($template)
    {
        $template = preg_replace('/{{\s*([a-zA-Z0-9_]+)\s*}}/', '{{ $1 }}', $template);
        $template = preg_replace('/{!!\s*([a-zA-Z0-9_]+)\s*!!}/', '{{ $1 }}', $template);
        $template = str_replace('<?=', '{{', $template);
        $template = str_replace('?>', '}}', $template);
        $template = str_replace('"', '\\"', $template);
        $template = str_replace("\n", '', $template);
        $template = str_replace("\r", '', $template);
        return $template;
    }


    public function generateEmailTemplate()
    {
        $templateString = view('email.business-welcome-mail')->render();
        // Now you can use the convertBladeToString method I provided earlier
        $business_welcome_mail = $this->convertBladeToString($templateString);




       $email_templates = [
        [
            "name" => "",
            "type" => "welcome_business_message",
            "is_active" => 1,
            "wrapper_id" => 1,
            "is_default" => 1,
            "business_id" => NULL,
            "template" => "business_welcome_mail",
        ]
    ];



    }
}
