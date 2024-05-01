<?php

namespace Modules\Account\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\Exportable;

class ReportFormatSaleExport implements  FromView
{
    use Exportable;

    protected $data;
    protected $add_reference;
    public function data($data)
    {
        $this->data = $data;

        return $this;
    }

    public function addReference($add_reference)
    {
        $this->add_reference = $add_reference;

        return $this;
    }
    
    public function view(): View {
        $this->data['add_reference'] = $this->add_reference;
        return view('account::account.templates.format_sale', $this->data);
        // return view('account::account.templates.format_sale', 
    }
}
