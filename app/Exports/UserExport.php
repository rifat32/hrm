<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;

class UserExport implements FromCollection
{
    protected $user;


    public function __construct($user)
    {
        $this->user = $user;
    }

    public function view(): View
    {
        return view('export.user', ["user" => $this->user, "request" => request()]);
    }

    public function collection()
    {
        //
    }
}
