<?php

namespace App\Console\Commands;

use App\Models\FertigationSchedule;
use App\Services\ScheduleRunner;
use Illuminate\Console\Command;

class RunDueSchedules extends Command
{
    protected $signature = 'schedules:run';

    protected $description = 'Run fertigation schedules whose next_run_at is due';

    public function handle(ScheduleRunner $runner): int
    {
        // Backfill next_run_at for enabled schedules that don't have one yet.
        FertigationSchedule::where('enabled', true)->whereNull('next_run_at')->get()
            ->each(fn ($s) => $s->update(['next_run_at' => $runner->nextRun($s)]));

        // Fire everything that is due.
        $due = FertigationSchedule::where('enabled', true)
            ->whereNotNull('next_run_at')
            ->where('next_run_at', '<=', now())
            ->get();

        foreach ($due as $schedule) {
            $runner->run($schedule);
            $this->info("Ran schedule #{$schedule->id} \"{$schedule->name}\".");
        }

        $this->info("Done. {$due->count()} schedule(s) executed.");

        return self::SUCCESS;
    }
}
