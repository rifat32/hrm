<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;

class HolidayExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    protected $holidays;

    public function __construct($holidays)
    {
        $this->holidays = $holidays;
    }

    public function view(): View
    {
        return view('export.holidays', ["holidays" => $this->holidays]);
    }



    public function collection()
    {
        //
    }
}
