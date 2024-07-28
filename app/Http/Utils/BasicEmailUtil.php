<?php

namespace App\Http\Utils;



trait BasicEmailUtil
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

    function extractVariables($string) {
        preg_match_all('/\[(.*?)\]/', $string, $matches);
        return $matches[1];
    }


    public function prepareEmailTemplateData($template_type,$business_id) {
        $template_string = view(('email.' . $template_type))->render();
        // Now you can use the convertBladeToString method I provided earlier
        $template = $this->convertBladeToString($template_string);
        $templateVariables = $this->extractVariables($template);
        $email_content = [
            "name" => "",
            "type" => $template_type,
            "is_active" => 1,
            "wrapper_id" => 1,
            "is_default" => empty($business_id)?1:0,
            "business_id" => $business_id,
            "template" => $template,
            "template_variables" => implode(',', $templateVariables),
            "created_at" => now(),
            "updated_at" => now()

        ];

        return $email_content;

    }
}
