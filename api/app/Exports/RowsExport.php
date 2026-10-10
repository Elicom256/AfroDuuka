<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RowsExport implements FromArray, WithHeadings
{
    public function __construct(
        private readonly array $headers,
        private readonly array $rows,
    ) {}

    public function headings(): array
    {
        return $this->headers;
    }

    public function array(): array
    {
        return $this->rows;
    }
}
