<?php

namespace App\Console\Commands;

use App\Models\TaskOccurrence;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkOverdueOccurrences extends Command
{
    protected $signature   = 'occurrences:mark-overdue';
    protected $description = 'Marca ocorrências PENDING de dias anteriores como OVERDUE';

    public function handle(): void
    {
        $count = TaskOccurrence::withoutGlobalScopes()
            ->where('status', 'PENDING')
            ->whereDate('period_start', '<', Carbon::today())
            ->update(['status' => 'OVERDUE']);

        $this->info("{$count} ocorrência(s) marcada(s) como OVERDUE.");
    }
}