<?php

namespace App\Mail;

use App\Http\Utils\BasicEmailUtil;

use App\Models\EmailTemplate;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class BusinessWelcomeMail extends Mailable
{
    use Queueable, SerializesModels, BasicEmailUtil;

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
        $front_end_url = env('FRONT_END_URL_DASHBOARD');
        $password_reset_link =  ($front_end_url.'/auth/change-password?token='.$this->user->resetPasswordToken);


        $email_content = EmailTemplate::where([
            "type" => "business_welcome_mail",
            "is_active" => 1

        ])->first();

        if(empty($email_content)){

            $templateString = view('email.business_welcome_mail')->render();
            // Now you can use the convertBladeToString method I provided earlier
            $template = $this->convertBladeToString($templateString);
            $templateVariables = $this->extractVariables($template);


            $email_content = EmailTemplate::create([
                "name" => "",
                "type" => "business_welcome_mail",
                "is_active" => 1,
                "wrapper_id" => 1,
                "is_default" => 1,
                "business_id" => NULL,
                "template" => $template,
                "template_variables" => implode(',', $templateVariables)

            ]
        );




        }


        $html_content = $email_content->template;
        $html_content =  str_replace("[FULL_NAME]", ($this->user->first_Name . " " . $this->user->middle_Name . " " . $this->user->last_Name . " "), $html_content );
        $html_content =  str_replace("[APP_NAME]", env("APP_NAME"), $html_content );
        $html_content =  str_replace("[PASSWORD_RESET_LINK]", $password_reset_link, $html_content );




        return $this->subject(("Welcome to " . env("APP_NAME") .  " - Set Your Password"))->view('email.dynamic_mail',["html_content"=>$html_content]);




        // return $this->subject(("Welcome to " . env("APP_NAME") .  " - Set Your Password"))->view('email.business-welcome-mail',["user" => $this->user,"password_reset_link" => $password_reset_link]);


    }
}
