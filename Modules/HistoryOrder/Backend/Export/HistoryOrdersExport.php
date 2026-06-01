<?php

namespace Modules\HistoryOrder\Backend\Export;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class HistoryOrdersExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $items)
    {
    }

    public function collection(): Collection
    {
        return $this->items;
    }

    public function headings(): array
    {
        return ['更新日期', '客戶名稱', '型號', '縣市'];
    }

    public function map($item): array
    {
        return [
            optional($item->updated_at)->format('Y/m/d') ?? '',
            $item->customer_name ?? '',
            $item->series_model ?? '',
            $item->case_area ?? '',
        ];
    }
}
