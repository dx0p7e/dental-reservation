<?php

namespace App\Console\Commands;

use App\Models\DoctorSchedule;
use App\Services\SlotGenerationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class GenerateSlotsCommand extends Command
{
    protected $signature = 'slots:generate
        {--date= : Start date (YYYY-MM-DD), defaults to today}
        {--days=14 : Number of days ahead to generate}';

    protected $description = 'Generate ScheduleSlot rows from active DoctorSchedule records';

    public function __construct(private readonly SlotGenerationService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $from = $this->option('date')
            ? Carbon::parse($this->option('date'))
            : Carbon::today();

        $days = (int) $this->option('days');

        $created = 0;
        $skipped = 0;

        DoctorSchedule::where('is_active', true)->each(function (DoctorSchedule $schedule) use ($from, $days, &$created, &$skipped): void {
            $result = $this->service->generateForSchedule($schedule, $from, $days);
            $created += $result['created'];
            $skipped += $result['skipped'];
        });

        $this->info("Generated {$created} slots, skipped {$skipped} existing.");

        return self::SUCCESS;
    }
}
