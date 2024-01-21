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

    public function collection()
    {
        return collect($this->users)->map(function ($user) {
            return [
                $user->id,
                ($user->first_Name ." " . $user->last_Name . " " . $user->last_Name ),
                $user->email,
            ];
        });
    }

    public function map($user): array
    {
        // This method is still needed, even if it's empty for your case
        return [];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Email',
        ];
    }
}
