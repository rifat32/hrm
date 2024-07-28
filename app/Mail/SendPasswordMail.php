<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\EmailTemplate;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class SendPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public $user;
    public $password;
    public function __construct($user,$password)
    {
        $this->user = $user;
        $this->password = $password;

    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {

        $user = $this->user;
        $contact_email = "";
        $business_id = null;
        $is_default = 1;

        if (!empty($user)) {
            $business_id = $user->business_id ?? null;
            $is_default = empty($business_id) ? 1 : 0;
            $contact_email = $user->business->email ?? $user->email ?? "asjadtariq@gmail.com";
        } else {
            $contact_email = "asjadtariq@gmail.com";
        }


        $email_content = EmailTemplate::where([
            "type" => "send_password_mail",
            "is_active" => 1,
            "business_id" => $business_id,
            "is_default" => $is_default,

        ])->first();

        if(empty($email_content)){
            $templateString = view('email.send_password_mail')->render();
            // Now you can use the convertBladeToString method I provided earlier
            $template = $this->convertBladeToString($templateString);
            $templateVariables = $this->extractVariables($template);


            $email_content = EmailTemplate::create([
                "name" => "",
                "type" => "send_password_mail",
                "is_active" => 1,
                "wrapper_id" => 1,
                "business_id" => $business_id,
                "is_default" => $is_default,

                "template" => $template,
                "template_variables" => implode(',', $templateVariables)

            ]
        );

        if(empty($business_id)){

           $business_ids = Business::pluck("id");



           $email_templates = $business_ids->map(function($business_id) use($is_default, $template, $templateVariables) {
return [
                "name" => "",
                "type" => "send_password_mail",
                "is_active" => 1,
                "wrapper_id" => 1,
                "business_id" => $business_id,
                "is_default" => $is_default,

                "template" => $template,
                "template_variables" => implode(',', $templateVariables),
                "created_at" => now(),
                "updated_at" => now(),
];
           });

           EmailTemplate::insert($email_templates->toArray());

        }

        }

        $front_end_url = env('FRONT_END_URL_DASHBOARD');
        $password_reset_link =  ($front_end_url.'/auth/change-password?token='.$this->user->resetPasswordToken);


        $html_content = $email_content->template;
        $html_content =  str_replace("[FULL_NAME]", ($this->user->first_Name . " " . $this->user->middle_Name . " " . $this->user->last_Name . " "), $html_content );
        $html_content =  str_replace("[APP_NAME]", env('APP_NAME'), $html_content);
        $html_content =  str_replace("[PASSWORD]", $this->password, $html_content);
        $html_content =  str_replace("[PASSWORD_RESET_LINK]", $password_reset_link, $html_content);




        return $this->subject(("Welcome to " . env("APP_NAME") .  " - Your Password"))->view('email.dynamic_mail',["html_content"=>$html_content]);

        // return $this->subject(("Welcome to " . env("APP_NAME") .  " - Please verify your email"))->view('email.send_password_mail',["html_content"=>$html_content]);





    }


}
