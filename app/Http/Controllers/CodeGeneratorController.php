<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CodeGeneratorController extends Controller
{
   public function getCodeGeneratorForm (Request $request) {
        $names=[];

        $validationRules = [
         'Basic Validation Rules' => [
           'required',
           'optional',
           'present',
           'filled',
           'nullable',
         ],
         'String Validation Rules' => [
           'string',
           'text',
           'email',
           'url',
           'date'
         ],
         'Numeric Validation Rules' => [
           'integer',
           'numeric',
         ],
       ];

         return view('code_generator.code-generator', compact("names","validationRules"));
     }


   public   function generateCode(Request $request) {

        $names["table_name"] = $request->table_name;
        $names["singular_table_name"] = Str::singular($names["table_name"]);

        $names["singular_model_name"] = Str::studly($names["singular_table_name"]);
        $names["plural_model_name"] = Str::plural($names["singular_model_name"]);

        $names["api_name"] = str_replace('_', '-', $names["table_name"]);
        $names["controller_name"] = $names["singular_model_name"] . 'Controller';

        $names["singular_comment_name"] = Str::singular(str_replace('_', ' ', $names["table_name"]));
        $names["plural_comment_name"] = str_replace('_', ' ', $names["table_name"]);



        $fields = collect();
        $field_names = $request->field_name;
        $field_validation_type = $request->validation_type;
        $field_string_validation_rules = $request->string_validation_rules;
        $field_number_validation_rules = $request->number_validation_rules;

        foreach($field_names as $index=>$value) {

            $field["name"] = $value;
            $field["type"] = $field_validation_type[$index];

            if($field["type"] == "string") {
                $field["request_validation_type"] = $field_string_validation_rules[$index];
            }
            else if($field["type"] == "number") {
                $field["request_validation_type"] = $field_number_validation_rules[$index];
            }
            else {
                $field["request_validation_type"] = $field["type"];
            }

            $fields->push($field);

        }





        $validationRules = [
            'Basic Validation Rules' => [
              'required',
              'optional',
              'present',
              'filled',
              'nullable',
            ],
            'String Validation Rules' => [
              'string',
              'email',
              'url',
              'date'
            ],
            'Numeric Validation Rules' => [
              'integer',
              'numeric',
            ],

          ];

        return view('code_generator.code-generator',compact("names","fields","validationRules"));


    }



}
