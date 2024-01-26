<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class UsersExport implements FromCollection, WithHeadings
{
    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

   public function processString($inputString) {
        // Remove underscore
        $withoutUnderscore = str_replace('_', '', $inputString);

        // Remove everything from the pound sign (#) and onwards
        $finalString = explode('#', $withoutUnderscore)[0];

        // Capitalize the string
        $capitalizedString = ucwords($finalString);

        return $capitalizedString;
    }

    public function collection()
    {
        if ($this->users instanceof \Illuminate\Support\Collection) {

            return collect($this->users)->map(function ($user, $index) {
                return [
                    $index+1,
                    ($user->first_Name ." " . $user->last_Name . " " . $user->last_Name ),
                    $user->user_id,
                    $user->email,
                    $user->designation->name,
                    $this->processString($user->roles[0]->name),
                    ($user->is_active ? "Active":"De-active")


                ];
            });





        } else {
            return collect($this->users->items())->map(function ($user, $index) {
                return [
                    $index+1,
                    ($user->first_Name ." " . $user->last_Name . " " . $user->last_Name ),
                    $user->user_id,
                    $user->email,
                    $user->designation->name,
                    $this->processString($user->roles[0]->name),
                    ($user->is_active ? "Active":"De-active")


                ];
            });

        }


    }

    public function map($user): array
    {
        // This method is still needed, even if it's empty for your case
        return [];
    }

    public function headings(): array
    {
        return [
            "",
            'Employee',
            'Employee ID',
            'Email',
            'Designation',
            'Role',
            'Status',
        ];

    }
}
