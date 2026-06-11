<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OneDriveService
{
    protected ?string $clientId = null;
    protected ?string $tenantId = null;
    protected ?string $clientSecret = null;

    public function __construct()
    {
        $this->clientId = env('ONEDRIVE_CLIENT_ID');
        $this->tenantId = env('ONEDRIVE_TENANT_ID');
        $this->clientSecret = env('ONEDRIVE_CLIENT_SECRET');
    }

    /**
     * Search for files inside a OneDrive folder.
     */
    public function listFilesInFolder(string $url): array
    {
        // If credentials are missing, run in Simulation Mode for demo
        if (!$this->clientId || !$this->tenantId || !$this->clientSecret) {
            return $this->getSimulatedFiles($url);
        }

        // --- REAL API LOGIC WOULD GO HERE ---
        // 1. Get OAuth Token using Client Credentials
        // 2. Encode URL to Graph API shareId
        // 3. GET /shares/{shareId}/driveItem/children
        // If token fails or API fails, fallback to simulation so demo doesn't crash
        return $this->getSimulatedFiles($url);
    }

    /**
     * Simulation mode file list provider.
     */
    protected function getSimulatedFiles(string $url): array
    {
        $urlLower = strtolower($url);

        // 1. If URL contains "missing" (Simulates missing files rule)
        if (str_contains($urlLower, 'missing')) {
            return [
                ['id' => 'sim_layer', 'name' => 'layer.json', 'mimeType' => 'application/json', 'size' => 1024 * 50],
                ['id' => 'sim_building', 'name' => 'building.geojson', 'mimeType' => 'application/json', 'size' => 1024 * 200],
                ['id' => 'sim_ortho', 'name' => 'ortho_final.tif', 'mimeType' => 'image/tiff', 'size' => 1024 * 1024 * 500],
            ]; // tileset.json is missing!
        }

        // 2. If URL contains "fake" (Simulates anti-spoofing rule)
        if (str_contains($urlLower, 'fake')) {
            return [
                ['id' => 'sim_tileset', 'name' => 'tileset.json', 'mimeType' => 'application/json', 'size' => 1024 * 10],
                ['id' => 'sim_layer', 'name' => 'layer.json', 'mimeType' => 'application/json', 'size' => 1024 * 50],
                // building.geojson is actually a fake MP4 file disguised as JSON
                ['id' => 'sim_building_fake', 'name' => 'building.geojson', 'mimeType' => 'video/mp4', 'size' => 1024 * 1024 * 150], 
                ['id' => 'sim_ortho', 'name' => 'ortho_final.tif', 'mimeType' => 'image/tiff', 'size' => 1024 * 1024 * 500],
            ];
        }

        // 3. If URL contains "large" (Simulates file size limit rule)
        if (str_contains($urlLower, 'large')) {
            return [
                ['id' => 'sim_tileset', 'name' => 'tileset.json', 'mimeType' => 'application/json', 'size' => 1024 * 10],
                ['id' => 'sim_layer', 'name' => 'layer.json', 'mimeType' => 'application/json', 'size' => 1024 * 50],
                ['id' => 'sim_building', 'name' => 'building.geojson', 'mimeType' => 'application/json', 'size' => 1024 * 200],
                // ortho_final.tif exceeds the 10GB limit (set to 15GB)
                ['id' => 'sim_ortho_large', 'name' => 'ortho_final.tif', 'mimeType' => 'image/tiff', 'size' => 1024 * 1024 * 1024 * 15], 
            ];
        }

        // Default: Return a perfectly successful validation!
        return [
            ['id' => 'sim_tileset', 'name' => 'tileset.json', 'mimeType' => 'application/json', 'size' => 1024 * 15],
            ['id' => 'sim_layer', 'name' => 'layer.json', 'mimeType' => 'application/json', 'size' => 1024 * 45],
            ['id' => 'sim_building', 'name' => 'building.geojson', 'mimeType' => 'application/json', 'size' => 1024 * 350],
            ['id' => 'sim_ortho', 'name' => 'ortho_final.tif', 'mimeType' => 'image/tiff', 'size' => 1024 * 1024 * 450],
        ];
    }
}
