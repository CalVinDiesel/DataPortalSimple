<?php

namespace App\Console\Commands;

use App\Models\Submission;
use Carbon\Carbon;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('submissions:housekeep {--days=7 : The number of days after which to archive}')]
#[Description('Archive completed submissions older than X days to save storage space.')]
class HousekeepSubmissions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $cutoff = Carbon::now()->subDays($days);

        $submissions = Submission::where('status', 'completed')
            ->whereRaw('is_archived = false')
            ->where('updated_at', '<', $cutoff)
            ->get();

        if ($submissions->isEmpty()) {
            $this->info("No old projects found to archive.");
            return;
        }

        $this->info("Found " . $submissions->count() . " projects to archive (Older than $days days).");

        foreach ($submissions as $submission) {
            $this->info("Archiving: #{$submission->id} - {$submission->project_name}");
            
            // Force true as a raw SQL boolean for Postgres
            \DB::table('submissions')
                ->where('id', $submission->id)
                ->update([
                    'is_archived' => \DB::raw('true'),
                    'updated_at' => now()
                ]);
        }

        $this->info("Housekeeping complete!");
    }
}
