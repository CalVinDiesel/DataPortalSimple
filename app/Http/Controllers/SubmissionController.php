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
                'nullable',
                'url',
                function ($attribute, $value, $fail) {
                    if (!$value) return; // Skip if empty

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
            'sftp_host' => 'nullable|string|max:255',
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
            'user_remarks' => $request->remarks,
            'status' => 'pending',
        ]);

        // Send notification email
        Mail::to(config('mail.admin_address'))->send(new SubmissionReceived($submission));

        return redirect()->route('dashboard')->with('success', 'Submission sent successfully!');
    }
    public function storeExternal(Request $request)
    {
        $request->validate([
            'project_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'processed_data_path' => 'nullable|string',
            'terrain_path' => 'nullable|url',
            'building_path' => 'nullable|url',
            'orthophoto_path' => 'nullable|url',
            'google_drive_link' => 'nullable|url',
            'sftp_host' => 'nullable|string',
            'sftp_port' => 'nullable|integer',
            'sftp_username' => 'nullable|string',
            'sftp_password' => 'nullable|string',
            'sftp_path' => 'nullable|string',
        ]);

        // Validation: Must provide either a Direct URL or a Transfer Link or a File
        if (!$request->processed_data_path && !$request->google_drive_link && !$request->sftp_host && !$request->hasFile('raw_model_file')) {
            return back()->withErrors(['processed_data_path' => 'Please provide either a Direct URL, a transfer link, or upload a model file.'])->withInput();
        }

        $submission = Submission::create([
            'user_id' => Auth::id(),
            'project_name' => $request->project_name,
            'description' => $request->description,
            'status' => 'pending',
            'output_category' => '3D Tiles',
            'submission_type' => 'external',
            'processed_data_path' => $request->processed_data_path,
            'terrain_path' => $request->terrain_path,
            'building_path' => $request->building_path,
            'orthophoto_path' => $request->orthophoto_path,
            'google_drive_link' => $request->google_drive_link,
            'sftp_host' => $request->sftp_host,
            'sftp_port' => $request->sftp_port ?? 22,
            'sftp_username' => $request->sftp_username,
            'sftp_password' => $request->sftp_password,
            'sftp_path' => $request->sftp_path,
            'user_remarks' => $request->remarks,
            'camera_config' => 'Single-Lens',
            'category' => 'External Registration',
            'image_metadata' => 'EXIF',
            'processed_data_path' => $request->processed_data_path,
        ]);

        // Handle Direct Upload Conversion
        if ($request->hasFile('raw_model_file')) {
            $files = $request->file('raw_model_file');
            if (!is_array($files)) {
                $files = [$files];
            }

            $modelDir = public_path("models/{$submission->id}");
            if (!file_exists($modelDir)) {
                mkdir($modelDir, 0777, true);
            }

            $processedUrls = [];
            $objFilesToConvert = [];
            $plyFilesToConvert = [];

            $filePaths = $request->input('file_paths');

            // 1. Move all files first (so .obj files have access to their .mtl and .jpg friends)
            foreach ($files as $index => $file) {
                $extension = strtolower($file->getClientOriginalExtension());
                
                if (is_array($filePaths) && isset($filePaths[$index])) {
                    $relativePath = ltrim($filePaths[$index], '/');
                } else {
                    $relativePath = $file->getClientOriginalName();
                }

                $dirName = dirname($relativePath);
                $directory = $dirName === '.' ? $modelDir : $modelDir . '/' . $dirName;
                
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }

                $filename = basename($relativePath);
                $rawPath = $modelDir . '/' . $relativePath;
                
                $file->move($directory, $filename);

                if ($extension === 'obj') {
                    $objFilesToConvert[] = [
                        'rawPath' => $rawPath,
                        'glbPath' => $directory . '/' . str_replace('.obj', '.glb', $filename),
                        'filename' => $filename
                    ];
                } elseif ($extension === 'ply') {
                    $plyFilesToConvert[] = [
                        'rawPath' => $rawPath,
                        'tilesetDir' => $modelDir . '/tileset_' . md5($filename),
                        'filename' => $filename
                    ];
                } elseif ($extension === 'glb' || $extension === 'gltf') {
                    $processedUrls[] = "/models/{$submission->id}/{$relativePath}";
                }
            }

            // 2. Convert all .obj files now that all files are present in the folder
            foreach ($objFilesToConvert as $obj) {
                try {
                    exec("npx obj2gltf -i \"{$obj['rawPath']}\" -o \"{$obj['glbPath']}\"");
                    $glbFilename = str_replace('.obj', '.glb', $obj['filename']);
                    $processedUrls[] = "/models/{$submission->id}/{$glbFilename}";
                } catch (\Exception $e) {
                    \Log::error("OBJ Conversion Failed: " . $e->getMessage());
                }
            }

            // Generate a manifest.json for the PLY Viewer to know what folders exist
            $isFolderWise = false;
            if (count($plyFilesToConvert) > 0) {
                $manifestFolders = [];
                foreach ($plyFilesToConvert as $ply) {
                    $dir = dirname($ply['rawPath']);
                    $relativeDir = str_replace($modelDir . '/', '', $dir);
                    if ($relativeDir === $modelDir) $relativeDir = '';
                    
                    if ($relativeDir !== '' && !in_array($relativeDir, $manifestFolders)) {
                        $manifestFolders[] = $relativeDir;
                    }
                }
                file_put_contents($modelDir . '/manifest.json', json_encode([
                    'type' => 'folder_wise_ply',
                    'folders' => array_values($manifestFolders)
                ]));
                
                $isFolderWise = count($manifestFolders) > 0;
            }

            // 3. Convert all .ply Gaussian Splat files into 3D Tiles in the BACKGROUND
            if (count($plyFilesToConvert) > 0) {
                try {
                    $tilesetDir = $modelDir . '/tileset_merged';
                    $nodeScript = base_path('process_splat_upload.js');
                    
                    // Dispatch the job to run in the background
                    \App\Jobs\ConvertSplatJob::dispatch($submission->id, $modelDir, $tilesetDir, $nodeScript);
                    
                    // We always set the URL to tileset.json so the Cesium viewer works.
                    // The Three.js viewer will automatically fallback to manifest.json if it exists.
                    $processedUrls[] = "/models/{$submission->id}/tileset_merged/tileset.json";
                } catch (\Exception $e) {
                    \Log::error("Failed to dispatch PLY Conversion Job: " . $e->getMessage());
                }
            }

            if (!empty($processedUrls)) {
                $submission->update([
                    'processed_data_path' => count($processedUrls) > 1 ? json_encode($processedUrls) : $processedUrls[0]
                ]);
            }
        }

        return redirect()->route('dashboard')->with('success', 'External model registered successfully. It will appear on your dashboard once verified by the Admin.');
    }

    /**
     * Verify Google Drive / SFTP folders before submission.
     */
    public function verifyExternalFolder(Request $request, \App\Services\GoogleDriveService $driveService, \App\Services\OneDriveService $oneDriveService)
    {
        $link = $request->input('google_drive_link');
        if (!$link) {
            return response()->json(['success' => false, 'error' => 'Please provide a valid Google Drive or OneDrive link.'], 400);
        }

        $isOneDrive = str_contains($link, 'onedrive.live.com') || str_contains($link, 'sharepoint.com') || str_contains($link, '1drv.ms');

        if ($isOneDrive) {
            // Scan OneDrive Folder Contents
            $files = $oneDriveService->listFilesInFolder($link);
        } else {
            // 1. Parse Google Drive Folder Link
            $folderId = $driveService->parseFolderId($link);
            if (!$folderId) {
                return response()->json(['success' => false, 'error' => 'Could not extract Folder ID. Make sure it is a valid Google Drive or OneDrive folder link.'], 400);
            }

            // 2. Scan Google Drive Folder Contents
            $files = $driveService->listFilesInFolder($folderId);
        }

        // 3. Get Validation Rules from centralized config
        $rules = $this->getValidationRules();

        $results = [];
        $hasRequired = false;

        // 4. Verify Each File Against the Rules
        foreach ($rules as $key => $rule) {
            $foundFile = null;
            foreach ($files as $file) {
                if (strtolower($file['name']) === strtolower($rule['expected_name'])) {
                    $foundFile = $file;
                    break;
                }
            }

            if ($foundFile) {
                $size = $foundFile['size'] ?? 0;
                $mime = $foundFile['mimeType'] ?? '';

                if ($size < $rule['min_size']) {
                    $results[$key] = [
                        'status' => 'error',
                        'message' => "File is empty or corrupted.",
                        'file' => $foundFile['name'],
                    ];
                }
                // Check Boss Rule 4 (Size Limit check)
                elseif ($size > $rule['max_size']) {
                    $results[$key] = [
                        'status' => 'error',
                        'message' => "Exceeds limit of " . $this->formatBytes($rule['max_size']) . " (Current: " . $this->formatBytes($size) . ")",
                        'file' => $foundFile['name'],
                    ];
                }
                // Check Boss Rule 3 (Format / Anti-Spoofing check)
                elseif (!empty($mime) && !in_array($mime, $rule['allowed_mimes'])) {
                    $results[$key] = [
                        'status' => 'error',
                        'message' => "Spoofing detected! Expected JSON/text, but got: " . $mime,
                        'file' => $foundFile['name'],
                    ];
                } else {
                    $results[$key] = [
                        'status' => 'success',
                        'message' => "Verified (" . $this->formatBytes($size) . ")",
                        'file' => $foundFile['name'],
                    ];
                    if ($rule['required']) {
                        $hasRequired = true;
                    }
                }
            } else {
                if ($rule['required']) {
                    $results[$key] = [
                        'status' => 'error',
                        'message' => "Missing required file: " . $rule['expected_name'],
                        'file' => null,
                    ];
                } else {
                    $results[$key] = [
                        'status' => 'warning',
                        'message' => "Optional file not found",
                        'file' => null,
                    ];
                }
            }
        }

        return response()->json([
            'success' => $hasRequired && !collect($results)->contains('status', 'error'),
            'results' => $results,
            'message' => $hasRequired ? 'Folder verification complete!' : 'Missing core required 3D Tileset (tileset.json) file.'
        ]);
    }

    /**
     * Verify SFTP servers before submission.
     */
    public function verifySftpFolder(Request $request, \App\Services\SftpService $sftpService)
    {
        $request->validate([
            'sftp_host' => 'required|string',
            'sftp_port' => 'nullable|integer',
            'sftp_username' => 'required|string',
            'sftp_password' => 'required|string',
            'sftp_path' => 'nullable|string',
        ]);

        try {
            $files = $sftpService->listFilesInFolder(
                $request->sftp_host,
                $request->sftp_port ?? 22,
                $request->sftp_username,
                $request->sftp_password,
                $request->sftp_path
            );

            // 3. Get Validation Rules from centralized method
            $rules = $this->getValidationRules();

            $results = [];
            $hasRequired = false;

            foreach ($rules as $key => $rule) {
                $foundFile = null;
                foreach ($files as $file) {
                    if (strtolower($file['name']) === strtolower($rule['expected_name'])) {
                        $foundFile = $file;
                        break;
                    }
                }

                if ($foundFile) {
                    $size = $foundFile['size'] ?? 0;
                    $mime = $foundFile['mimeType'] ?? '';

                    if ($size < $rule['min_size']) {
                        $results[$key] = [
                            'status' => 'error',
                            'message' => "File is empty or corrupted.",
                            'file' => $foundFile['name'],
                        ];
                    } elseif ($size > $rule['max_size']) {
                        $results[$key] = [
                            'status' => 'error',
                            'message' => "Exceeds limit of " . $this->formatBytes($rule['max_size']) . " (Current: " . $this->formatBytes($size) . ")",
                            'file' => $foundFile['name'],
                        ];
                    } elseif (!empty($mime) && !in_array($mime, $rule['allowed_mimes'])) {
                        $results[$key] = [
                            'status' => 'error',
                            'message' => "Spoofing detected! Expected JSON/text, but got: " . $mime,
                            'file' => $foundFile['name'],
                        ];
                    } else {
                        $results[$key] = [
                            'status' => 'success',
                            'message' => "Verified (" . $this->formatBytes($size) . ")",
                            'file' => $foundFile['name'],
                        ];
                        if ($rule['required']) {
                            $hasRequired = true;
                        }
                    }
                } else {
                    if ($rule['required']) {
                        $results[$key] = [
                            'status' => 'error',
                            'message' => "Missing required file: " . $rule['expected_name'],
                            'file' => null,
                        ];
                    } else {
                        $results[$key] = [
                            'status' => 'warning',
                            'message' => "Optional file not found",
                            'file' => null,
                        ];
                    }
                }
            }
            // --- BEGIN DEEP SCAN LOGIC ---
            $deepScanResult = null;
            if ($hasRequired) {
                // If the tileset.json was found, perform the Deep Scan remotely!
                $deepScanResult = $sftpService->deepScanTileset(
                    $request->sftp_host,
                    $request->sftp_port ?? 22,
                    $request->sftp_username,
                    $request->sftp_password,
                    $request->sftp_path
                );

                if (!$deepScanResult['success']) {
                    // Inject the deep scan failure into the 3D tileset result
                    $results['3d_tileset'] = [
                        'status' => 'error',
                        'message' => $deepScanResult['error'],
                        'file' => 'tileset.json',
                    ];
                } else {
                    $results['3d_tileset']['message'] .= " (Deep Scan: Verified " . $deepScanResult['total_verified'] . " internal files)";
                }
            }
            // --- END DEEP SCAN LOGIC ---

            return response()->json([
                'success' => $hasRequired && !collect($results)->contains('status', 'error'),
                'results' => $results,
                'message' => $hasRequired 
                    ? (!collect($results)->contains('status', 'error') ? 'Folder & Deep Verification complete!' : 'Deep Scan found corrupted data.')
                    : 'Missing core required 3D Tileset (tileset.json) file.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 400);
        }
    }

    /**
     * Helper to format bytes to human readable sizes.
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get centralized validation rules for file sizes and mimes based on config.
     */
    private function getValidationRules()
    {
        return [
            '3d_tileset' => [
                'expected_name' => 'tileset.json',
                'required' => true,
                'max_size' => config('portal.limits.tileset_mb', 50) * 1024 * 1024,
                'min_size' => 100,
                'label' => '3D Tileset (tileset.json)',
                'allowed_mimes' => ['application/json', 'text/plain'],
            ],
            'terrain' => [
                'expected_name' => 'layer.json',
                'required' => false,
                'max_size' => config('portal.limits.terrain_mb', 10) * 1024 * 1024,
                'min_size' => 100,
                'label' => 'Terrain (layer.json)',
                'allowed_mimes' => ['application/json', 'text/plain'],
            ],
            'buildings' => [
                'expected_name' => 'building.geojson',
                'required' => false,
                'max_size' => config('portal.limits.buildings_mb', 500) * 1024 * 1024,
                'min_size' => 100,
                'label' => 'Buildings (building.geojson)',
                'allowed_mimes' => ['application/json', 'text/plain', 'application/geo+json'],
            ],
            'orthophoto' => [
                'expected_name' => 'ortho.tif',
                'required' => false,
                'max_size' => config('portal.limits.orthophoto_gb', 10) * 1024 * 1024 * 1024,
                'min_size' => 1000,
                'label' => 'Orthophoto (ortho.tif)',
                'allowed_mimes' => ['image/tiff', 'application/octet-stream'],
            ],
        ];
    }
}
