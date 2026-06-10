<?php

namespace App\Console\Commands;

use App\Models\Submission;
use Carbon\Carbon;
use phpseclib3\Net\SFTP;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
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

            // --- SFTP BACKUP & PURGE ---
            if (!empty($submission->sftp_host) && !empty($submission->sftp_username)) {
                $this->info("  -> Connected to SFTP. Starting Backup...");
                try {
                    $sftp = new SFTP($submission->sftp_host, $submission->sftp_port ?? 22);
                    if ($sftp->login($submission->sftp_username, $submission->sftp_password)) {
                        
                        // Convert Windows backslashes to Linux forward slashes for SFTP compatibility
                        $rawPath = empty($submission->sftp_path) ? '.' : $submission->sftp_path;
                        $remotePath = rtrim(str_replace('\\', '/', $rawPath), '/');
                        
                        $backupFolder = "backups/submission_{$submission->id}";
                        
                        // 1. Download
                        $this->info("  -> Downloading files to storage/app/{$backupFolder}");
                        $backupSuccess = $this->downloadSftpDirectory($sftp, $remotePath, $backupFolder);
                        
                        // 2. Delete
                        if ($backupSuccess) {
                            $this->info("  -> Backup complete! Purging original files from SFTP...");
                            // Safety check: Don't delete root directory accidentally if path is empty
                            if ($remotePath !== '.') {
                                $this->deleteSftpDirectory($sftp, $remotePath);
                            } else {
                                $this->info("  -> (Skipping Purge: Cannot safely delete root directory).");
                            }
                            $this->info("  -> SFTP Storage Housekeeping Successful.");
                        } else {
                            $this->error("  -> [ERROR] Backup failed! Aborting purge to protect original files.");
                        }
                    } else {
                        $this->warn("  -> Failed to log into SFTP for Backup.");
                    }
                } catch (\Exception $e) {
                    $this->warn("  -> SFTP Backup Error: " . $e->getMessage());
                }
            }

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

    /**
     * Recursively download files from SFTP to Laravel Local Storage
     */
    private function downloadSftpDirectory(SFTP $sftp, string $remoteDir, string $localDir)
    {
        Storage::disk('local')->makeDirectory($localDir);
        $files = $sftp->nlist($remoteDir);
        if ($files === false) {
            $this->warn("      [!] Failed to read remote directory: {$remoteDir} (Path might be invalid or permission denied)");
            return false;
        }

        $allSuccess = true;
        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $remotePath = $remoteDir . '/' . $file;
            $localPath = $localDir . '/' . $file;

            if ($sftp->is_dir($remotePath)) {
                if (!$this->downloadSftpDirectory($sftp, $remotePath, $localPath)) {
                    $allSuccess = false;
                }
            } else {
                $data = $sftp->get($remotePath);
                if ($data !== false) {
                    Storage::disk('local')->put($localPath, $data);
                } else {
                    $allSuccess = false;
                }
            }
        }
        return $allSuccess;
    }

    /**
     * Recursively delete files and folders from SFTP
     */
    private function deleteSftpDirectory(SFTP $sftp, string $remoteDir)
    {
        $files = $sftp->nlist($remoteDir);
        if (!$files) return;

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $remotePath = $remoteDir . '/' . $file;
            if ($sftp->is_dir($remotePath)) {
                $this->deleteSftpDirectory($sftp, $remotePath);
            } else {
                $sftp->delete($remotePath);
            }
        }
        $sftp->rmdir($remoteDir);
    }
}
