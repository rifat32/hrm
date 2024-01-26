<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;

class AttendancesExport implements FromCollection
{
    protected $users;

    public function __construct($users)
    {
        $this->users = $users;
    }

    public function role_string($inputString) {
        // Remove underscore
        $withoutUnderscore = str_replace('_', '', $inputString);

        // Remove everything from the pound sign (#) and onwards
        $finalString = explode('#', $withoutUnderscore)[0];

        // Extract the role part (e.g., 'admin' or 'employee')
        $rolePart = str_replace('business_', '', $finalString);

        return $rolePart;
    }

    public function collection()
    {
        if ($this->users instanceof \Illuminate\Support\Collection) {

            return collect($this->users)->map(function ($user, $index) {
                return [
                    // $index+1,
                    ($user->first_Name ." " . $user->last_Name . " " . $user->last_Name ),
                    $user->user_id,
                    $user->email,
                    $user->designation->name,
                    $this->role_string($user->roles[0]->name),
                    ($user->is_active ? "Active":"De-active")


                ];
            });





        } else {
            return collect($this->users->items())->map(function ($user, $index) {
                return [
                    // $index+1,
                    ($user->first_Name ." " . $user->last_Name . " " . $user->last_Name ),
                    $user->user_id,
                    $user->email,
                    $user->designation->name,
                    $this->role_string($user->roles[0]->name),
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
        $headings = [
            'Employee',
            'Employee ID',
            'Email',
            'Designation',
            'Role',
            'Status',
        ];

        // Prepend report name to headings with a colon separator
        array_unshift($headings, "Employee Report" . ':');

        return $headings;

    }
}
