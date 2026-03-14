<?php

namespace App\Console\Commands;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private $startRow = 0;
    private $endRow = 0;

    public function setRows($startRow, $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell($column, $row, $worksheetName = '')
    {
        if ($row == 1) {
            return true;
        }

        return ($row >= $this->startRow && $row < $this->endRow);
    }
}
