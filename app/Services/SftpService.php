<?php

namespace App\Services;

use phpseclib3\Net\SFTP;
use Exception;
use Illuminate\Support\Facades\Log;
use finfo;

class SftpService
{
    /**
     * Connect to SFTP server, navigate to path, and return file details.
     */
    public function listFilesInFolder(string $host, int $port, string $username, string $password, ?string $path = null): array
    {
        try {
            // 1. Connect and Login
            $sftp = new SFTP($host, $port);
            if (!$sftp->login($username, $password)) {
                throw new Exception('Login failed. Please check your credentials.');
            }

            // 2. Navigate to specific path if provided (with Smart Path Resolver for absolute paths)
            if (!empty($path)) {
                $path = str_replace('\\', '/', $path); // Convert Windows backslashes
                
                if (!$sftp->chdir($path)) {
                    // If absolute path fails, try stripping folders from the left (to find the relative path)
                    $parts = explode('/', trim($path, '/'));
                    $success = false;
                    
                    while (count($parts) > 1) {
                        array_shift($parts); // Remove the top-most folder (e.g. 'C:', then 'Users', etc.)
                        $fallback = implode('/', $parts);
                        
                        if ($sftp->chdir($fallback)) {
                            $success = true;
                            break;
                        }
                    }
                    
                    if (!$success) {
                        throw new Exception("Could not navigate to path: {$path}. Please make sure the folder exists.");
                    }
                }
            }

            // 3. Get all files in the current folder
            $rawList = $sftp->rawlist();
            if (!$rawList) {
                return [];
            }

            $files = [];
            $finfo = new finfo(FILEINFO_MIME_TYPE);

            foreach ($rawList as $name => $details) {
                if ($name === '.' || $name === '..') {
                    continue;
                }

                // Check if it's a file (type 1) and not a directory (type 2)
                if (isset($details['type']) && $details['type'] !== 1) {
                    continue;
                }

                $size = $details['size'] ?? 0;
                $mimeType = 'application/octet-stream';

                // Anti-Spoofing: Read the first 512 bytes of the file securely over the network to determine its TRUE format.
                // We do NOT download the whole file, saving massive amounts of bandwidth!
                if ($size > 0 && $size <= 10 * 1024 * 1024 * 1024) { // Don't try to read bytes if it's somehow over 10GB
                    $headerBytes = $sftp->get($name, false, 0, 512);
                    if ($headerBytes !== false) {
                        $detectedMime = $finfo->buffer($headerBytes);
                        if ($detectedMime) {
                            $mimeType = $detectedMime;
                        }
                    }
                }

                // Normalization: finfo sometimes sees JSON as "text/plain". We'll allow it as JSON if the extension matches.
                if (str_contains($mimeType, 'text/plain') && (str_ends_with(strtolower($name), '.json') || str_ends_with(strtolower($name), '.geojson'))) {
                    $mimeType = 'application/json';
                }

                $files[] = [
                    'id' => $name, // SFTP doesn't have IDs, so we use the filename
                    'name' => $name,
                    'size' => $size,
                    'mimeType' => $mimeType,
                ];
            }

            return $files;

        } catch (Exception $e) {
            Log::error('SFTP Service Error: ' . $e->getMessage());
            throw $e; // Re-throw so the controller can send the error message to the frontend
        }
    }

    /**
     * Perform a deep scan: Download tileset.json, parse it, and verify every referenced file exists on the SFTP server.
     */
    public function deepScanTileset(string $host, int $port, string $username, string $password, ?string $path = null): array
    {
        try {
            $sftp = new SFTP($host, $port);
            if (!$sftp->login($username, $password)) {
                throw new Exception('Login failed. Please check your credentials.');
            }

            if (!empty($path)) {
                $path = str_replace('\\', '/', $path);
                if (!$sftp->chdir($path)) {
                    // Try fallback
                    $parts = explode('/', trim($path, '/'));
                    $success = false;
                    while (count($parts) > 1) {
                        array_shift($parts);
                        $fallback = implode('/', $parts);
                        if ($sftp->chdir($fallback)) {
                            $success = true;
                            break;
                        }
                    }
                    if (!$success) {
                        throw new Exception("Could not navigate to path: {$path}.");
                    }
                }
            }

            // Get tileset.json content into memory
            $jsonContent = $sftp->get('tileset.json');
            if ($jsonContent === false) {
                return ['success' => false, 'error' => 'tileset.json not found.'];
            }

            $tileset = json_decode($jsonContent, true);
            if (empty($tileset)) {
                return ['success' => false, 'error' => 'tileset.json is empty, invalid, or corrupted.'];
            }

            // Extract all URIs format-agnostically
            $urisToFind = [];
            $this->extractUris($tileset['root'] ?? [], $urisToFind);
            $urisToFind = array_unique($urisToFind);

            $missingFiles = [];
            $checkedCount = 0;
            
            // Check existence of each URI over SFTP
            foreach ($urisToFind as $uri) {
                $cleanUri = explode('?', $uri)[0];
                $cleanUri = explode('#', $cleanUri)[0];
                // Use stat to check if the file exists on the remote SFTP server
                // We use rawurldecode because urldecode converts '+' to space, which breaks filenames that actually contain '+'
                $cleanUri = rawurldecode($cleanUri);

                if (!$sftp->stat($cleanUri)) {
                    $missingFiles[] = $cleanUri;
                    if (count($missingFiles) >= 3) {
                        break; // Stop after finding a few missing files to fail fast
                    }
                }
                
                $checkedCount++;
                // Limit to prevent PHP timeout on massive 10,000+ file datasets
                if ($checkedCount >= 1000) {
                    break;
                }
            }

            if (count($missingFiles) > 0) {
                return [
                    'success' => false, 
                    'error' => "Deep Scan Failed! The tileset.json requires files that are missing on your server. Examples of missing files: " . implode(', ', $missingFiles)
                ];
            }

            return ['success' => true, 'total_verified' => count($urisToFind)];

        } catch (Exception $e) {
            Log::error('SFTP Deep Scan Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Recursively extract URIs from a tileset.json node.
     */
    private function extractUris(array $node, array &$uris)
    {
        if (isset($node['content'])) {
            if (isset($node['content']['uri'])) $uris[] = $node['content']['uri'];
            elseif (isset($node['content']['url'])) $uris[] = $node['content']['url'];
        }
        if (isset($node['contents']) && is_array($node['contents'])) {
            foreach ($node['contents'] as $c) {
                if (isset($c['uri'])) $uris[] = $c['uri'];
                elseif (isset($c['url'])) $uris[] = $c['url'];
            }
        }
        if (isset($node['children']) && is_array($node['children'])) {
            foreach ($node['children'] as $child) {
                $this->extractUris($child, $uris);
            }
        }
    }
}
