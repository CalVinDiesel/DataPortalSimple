@extends('layouts.app')

@section('content')
<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <h2>All Submissions</h2>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Project</th>
                    <th>Camera/Category</th>
                    <th>Outputs</th>
                    <th>Metadata</th>
                    <th>Data Source</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach($submissions as $submission)
                <tr>
                    <td>{{ $submission->user->name }}</td>
                    <td>
                        <strong>{{ $submission->project_name }}</strong><br>
                        <small style="color: var(--text-dim)">{{ $submission->created_at->format('M d, Y') }}</small>
                    </td>
                    <td>
                        {{ $submission->camera_config }}<br>
                        <small style="color: var(--text-dim)">{{ $submission->category }}</small>
                    </td>
                    <td><small>{{ $submission->output_category }}</small></td>
                    <td>
                        {{ $submission->image_metadata }}<br>
                        @if($submission->capture_date)
                            <small style="color: var(--text-dim)">Date: {{ $submission->capture_date }}</small>
                        @endif
                    </td>
                    <td>
                        @if($submission->google_drive_link)
                            <a href="{{ $submission->google_drive_link }}" target="_blank" style="color: var(--primary); text-decoration: none; display: flex; align-items: center; gap: 4px;">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M232,128a104,104,0,1,1-104-104A104.11,104.11,0,0,1,232,128Zm-16,0a88,88,0,1,0-88,88A88.1,88.1,0,0,0,216,128Zm-40,0a48,48,0,1,1-48-48A48.05,48.05,0,0,1,176,128Z"></path></svg>
                                <span>Cloud Storage</span>
                            </a>
                        @elseif($submission->sftp_host)
                            <div style="font-size: 0.75rem; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 8px; border: 1px solid var(--border); color: var(--text); line-height: 1.4;">
                                <div style="display: flex; align-items: center; gap: 4px; color: #10b981; font-weight: bold; margin-bottom: 4px; text-transform: uppercase; font-size: 0.65rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" fill="currentColor" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm48-88a48,48,0,1,1-48-48A48.05,48.05,0,0,1,176,128Z"></path></svg>
                                    <span>SFTP SERVER</span>
                                </div>
                                <strong>Host name:</strong> {{ $submission->sftp_host }}<br>
                                <strong>Port number:</strong> {{ $submission->sftp_port }}<br>
                                <strong>User name:</strong> {{ $submission->sftp_username }}<br>
                                <strong>Password:</strong> {{ $submission->sftp_password }}<br>
                                <strong>Data Path:</strong> {{ $submission->sftp_path ?? '/' }}
                            </div>
                        @else
                            <span style="color: var(--text-dim); font-style: italic;">No source provided</span>
                        @endif
                    </td>
                    <td>
                        <span class="badge badge-{{ strtolower($submission->status) }}">{{ $submission->status }}</span>
                        @if($submission->is_archived)
                            <span class="badge" style="background: rgba(234, 179, 8, 0.1); color: #eab308; border: 1px solid rgba(234, 179, 8, 0.3); font-size: 0.65rem; margin-top: 4px; display: inline-block;">ARCHIVED</span>
                        @endif
                    </td>
                    <td>
                        <form action="{{ route('admin.submissions.update', $submission) }}" method="POST" style="display: flex; flex-direction: column; gap: 8px;">
                            @csrf
                            <div style="display: flex; gap: 8px;">
                                <select name="status" id="status-{{ $submission->id }}" onchange="toggleFields({{ $submission->id }})" style="width: 100px; padding: 6px; font-size: 0.75rem;">
                                    <option value="pending" {{ $submission->status == 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="processing" {{ $submission->status == 'processing' ? 'selected' : '' }}>Processing</option>
                                    <option value="completed" {{ $submission->status == 'completed' ? 'selected' : '' }}>Completed</option>
                                    <option value="rejected" {{ $submission->status == 'rejected' ? 'selected' : '' }}>Rejected</option>
                                </select>
                                <button type="submit" style="background: var(--primary); border: none; color: white; border-radius: 4px; padding: 4px 10px; cursor: pointer; font-size: 0.75rem;">Update</button>
                            </div>
                            
                            <div id="completed-fields-{{ $submission->id }}" style="display: {{ $submission->status == 'completed' ? 'flex' : 'none' }}; flex-direction: column; gap: 8px; margin-top: 8px;">
                                @if(!$submission->sftp_host)
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <label style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;">Google Drive Target Link</label>
                                    <input type="url" name="admin_drive_link" placeholder="Download GDrive Link" value="{{ $submission->admin_drive_link }}" style="width: 100%; padding: 6px; font-size: 0.75rem; border-radius: 4px; border: 1px solid var(--border); background: rgba(0,0,0,0.2); color: white;">
                                </div>
                                @else
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <label style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;">SFTP Result Path (on customer server)</label>
                                    <input type="text" name="sftp_result_path" placeholder="e.g. /home/results/model_v1.zip" value="{{ $submission->sftp_result_path }}" style="width: 100%; padding: 6px; font-size: 0.75rem; border-radius: 4px; border: 1px solid var(--border); background: rgba(0,0,0,0.2); color: white;">
                                </div>
                                @endif
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <label style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;">3D Tiles URL (Main Model)</label>
                                    <input type="url" name="processed_data_path" placeholder="Model Viewer URL (https://...)" value="{{ $submission->processed_data_path }}" style="width: 100%; padding: 6px; font-size: 0.75rem; border-radius: 4px; border: 1px solid var(--border); background: rgba(0,0,0,0.2); color: white;">
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <label style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;">Terrain URL</label>
                                    <input type="url" name="terrain_path" placeholder="https://..." value="{{ $submission->terrain_path }}" style="width: 100%; padding: 6px; font-size: 0.75rem; border-radius: 4px; border: 1px solid var(--border); background: rgba(0,0,0,0.2); color: white;">
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <label style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;">Building URL</label>
                                    <input type="url" name="building_path" placeholder="https://..." value="{{ $submission->building_path }}" style="width: 100%; padding: 6px; font-size: 0.75rem; border-radius: 4px; border: 1px solid var(--border); background: rgba(0,0,0,0.2); color: white;">
                                </div>
                                <div style="display: flex; flex-direction: column; gap: 2px;">
                                    <label style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 0.5px;">Orthophoto URL</label>
                                    <input type="url" name="orthophoto_path" placeholder="https://..." value="{{ $submission->orthophoto_path }}" style="width: 100%; padding: 6px; font-size: 0.75rem; border-radius: 4px; border: 1px solid var(--border); background: rgba(0,0,0,0.2); color: white;">
                                </div>
                            </div>

                            <div id="rejected-fields-{{ $submission->id }}" style="display: {{ $submission->status == 'rejected' ? 'flex' : 'none' }}; flex-direction: column; gap: 8px;">
                                <textarea name="rejection_reason" placeholder="Reason for rejection..." style="width: 100%; padding: 6px; font-size: 0.75rem; border-radius: 8px; background: rgba(0,0,0,0.2); color: white; border: 1px solid var(--border);">{{ $submission->rejection_reason }}</textarea>
                            </div>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@push('scripts')
<script>
function toggleFields(id) {
    const status = document.getElementById('status-' + id).value;
    const completedFields = document.getElementById('completed-fields-' + id);
    const rejectedFields = document.getElementById('rejected-fields-' + id);
    
    if (status === 'completed') {
        completedFields.style.display = 'flex';
        rejectedFields.style.display = 'none';
    } else if (status === 'rejected') {
        completedFields.style.display = 'none';
        rejectedFields.style.display = 'flex';
    } else {
        completedFields.style.display = 'none';
        rejectedFields.style.display = 'none';
    }
}
</script>
@endpush
@endsection
