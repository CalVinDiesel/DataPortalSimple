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
        'sftp_host',
        'sftp_port',
        'sftp_username',
        'sftp_password',
        'sftp_path',
        'sftp_result_path',
        'status',
        'is_archived',
        'processed_data_path',
        'admin_drive_link',
        'rejection_reason',
        'terrain_path',
        'building_path',
        'orthophoto_path'
    ];

    protected $casts = [
        'is_archived' => 'boolean',
        'capture_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
