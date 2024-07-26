<?php

namespace App\Mail;

use App\Models\EmailTemplate;
use App\Models\EmailTemplateWrapper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DynamicMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
   private $data;
   private $type;
    public function __construct($data,$type)
    {
        $this->data = $data;
        $this->type = $type;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $email_content = EmailTemplate::where([
            "type" => $this->type,
            "is_active" => 1

        ])->first();

        if(!$email_content){
            return $this->view('email.dummy');
        }


        $html_content = json_decode($email_content->template);
        $html_content =  str_replace("[customer_FirstName]", $this->data->customer->first_Name, $html_content );
        $html_content =  str_replace("[customer_LastName]", $this->data->customer->last_Name, $html_content );
        $html_content =  str_replace("[customer_FullName]", ($this->data->customer->first_Name. " " .$this->data->customer->last_Name), $html_content );



        $email_template_wrapper = EmailTemplateWrapper::where([
            "id" => $email_content->wrapper_id
        ])
        ->first();

        $html_final = json_decode($email_template_wrapper->template);
        $html_final =  str_replace("[content]", $html_content, $html_final);




        return $this->view('email.dynamic_mail',["html_content"=>$html_final]);
    }
}
