<?php

namespace App\Http\Controllers;

use App\Mail\SubmissionReceived;
use App\Models\Submission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

class SubmissionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'project_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'camera_config' => 'required|in:Single-Lens,Multi-Lens',
            'category' => 'required|string|max:255',
            'output_category' => 'required|array',
            'image_metadata' => 'required|in:EXIF,POS,EXIF & POS',
            'capture_date' => 'nullable|date',
            'google_drive_link' => [
                'required_without:sftp_host',
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    if (!$value) return; // Skip if empty (SFTP used instead)

                    $isGoogle = str_contains($value, 'drive.google.com');
                    $isOneDrive = str_contains($value, 'onedrive.live.com') || str_contains($value, 'sharepoint.com') || str_contains($value, '1drv.ms');
                    
                    if (!$isGoogle && !$isOneDrive) {
                        $fail('The link must be a valid Google Drive or OneDrive shared link.');
                        return;
                    }

                    // Automatic permission check for Google Drive
                    if ($isGoogle) {
                        try {
                            $response = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                            ])->get($value);
                            
                            if (!$response->successful()) {
                                $fail('The Google Drive link is inaccessible or broken.');
                                return;
                            }

                            $finalUrl = $response->effectiveUri();
                            if (str_contains($finalUrl, 'accounts.google.com')) {
                                $fail('Access Denied: The Google Drive link is Private. Please set it to "Anyone with the link".');
                                return;
                            }
                        } catch (\Exception $e) {
                            $fail('Could not verify Google Drive link permissions.');
                        }
                    }

                    // Automatic permission check for OneDrive / SharePoint
                    if ($isOneDrive) {
                        try {
                            $response = Http::withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                            ])->get($value);

                            if (!$response->successful() && $response->status() !== 401 && $response->status() !== 403) {
                                $fail('The OneDrive link is inaccessible or broken.');
                                return;
                            }

                            $finalUrl = $response->effectiveUri();
                            if (str_contains($finalUrl, 'login.live.com') || 
                                str_contains($finalUrl, 'login.microsoftonline.com') ||
                                str_contains($finalUrl, 'login.windows.net')) {
                                $fail('Access Denied: The OneDrive link is Private. Please set it to "Anyone with the link".');
                                return;
                            }

                            if ($response->status() === 401 || $response->status() === 403) {
                                $fail('Access Denied: You do not have permission to access this SharePoint link.');
                                return;
                            }
                        } catch (\Exception $e) {
                            $fail('Could not verify OneDrive link permissions. Please ensure the link is publicly accessible.');
                        }
                    }
                },
            ],
            'sftp_host' => 'required_without:google_drive_link|nullable|string|max:255',
            'sftp_port' => 'nullable|integer',
            'sftp_username' => 'required_with:sftp_host|nullable|string|max:255',
            'sftp_password' => 'required_with:sftp_host|nullable|string|max:255',
            'sftp_path' => 'nullable|string|max:255',
        ]);

        $submission = Submission::create([
            'user_id' => Auth::id(),
            'project_name' => $validated['project_name'],
            'description' => $validated['description'],
            'camera_config' => $validated['camera_config'],
            'category' => $validated['category'],
            'output_category' => implode(',', $validated['output_category']),
            'image_metadata' => $validated['image_metadata'],
            'capture_date' => $validated['capture_date'],
            'google_drive_link' => $validated['google_drive_link'] ?? null,
            'sftp_host' => $validated['sftp_host'] ?? null,
            'sftp_port' => $validated['sftp_port'] ?? 22,
            'sftp_username' => $validated['sftp_username'] ?? null,
            'sftp_password' => $validated['sftp_password'] ?? null,
            'sftp_path' => $validated['sftp_path'] ?? null,
            'status' => 'pending',
        ]);

        // Send notification email
        Mail::to(config('mail.admin_address'))->send(new SubmissionReceived($submission));

        return redirect()->route('dashboard')->with('success', 'Submission sent successfully!');
    }
}
