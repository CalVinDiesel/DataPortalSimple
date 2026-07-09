<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use App\Models\Submission;

class ConvertSplatJob implements ShouldQueue
{
    use Queueable;

    public $submissionId;
    public $modelDir;
    public $tilesetDir;
    public $nodeScript;
    
    /**
     * Create a new job instance.
     */
    public function __construct($submissionId, $modelDir, $tilesetDir, $nodeScript)
    {
        $this->submissionId = $submissionId;
        $this->modelDir = $modelDir;
        $this->tilesetDir = $tilesetDir;
        $this->nodeScript = $nodeScript;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info("Background Job: Starting PLY conversion for Submission ID {$this->submissionId}");
            
            // Set status to processing so the admin/user knows it's currently converting
            $submission = Submission::find($this->submissionId);
            if ($submission) {
                $submission->update(['status' => 'processing', 'admin_remarks' => 'Processing 3D data in background...']);
            }
            
            exec("node \"{$this->nodeScript}\" \"{$this->modelDir}\" \"{$this->tilesetDir}\"");
            
            if ($submission) {
                // Change back to pending so Admin can review and approve it manually!
                $submission->update(['status' => 'pending', 'admin_remarks' => '3D processing complete. Pending final review.']);
            }
            
            Log::info("Background Job: Finished PLY conversion for Submission ID {$this->submissionId}");
        } catch (\Exception $e) {
            Log::error("PLY Conversion Failed in Background Job: " . $e->getMessage());
            
            $submission = Submission::find($this->submissionId);
            if ($submission) {
                $submission->update(['status' => 'pending', 'admin_remarks' => 'Background processing failed. Please check logs.']);
            }
        }
    }
}
