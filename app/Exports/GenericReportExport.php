<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class GenericReportExport implements FromArray, WithHeadings, WithTitle
{
    private array $columns;
    private array $rows;
    private string $title;

    public function __construct(array $columns, array $rows, string $title = 'Rapport')
    {
        $this->columns = $columns;
        $this->rows = $rows;
        $this->title = $title;
    }

    public function headings(): array
    {
        return $this->columns;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function title(): string
    {
        return $this->title;
    }
}
