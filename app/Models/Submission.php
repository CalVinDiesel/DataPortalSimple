<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Submission extends Model
{
    protected $fillable = [
        'user_id',
        'project_name',
        'description',
        'camera_config',
        'category',
        'output_category',
        'image_metadata',
        'capture_date',
        'google_drive_link',
        'status',
        'processed_data_path',
        'admin_drive_link',
        'rejection_reason',
        'terrain_path',
        'building_path',
        'orthophoto_path'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
