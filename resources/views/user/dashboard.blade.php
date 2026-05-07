@extends('layouts.app')

@section('content')
<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
    <h1>My Dashboard</h1>
    <a href="{{ route('user.submit') }}" class="btn btn-primary" style="width: auto; padding: 10px 24px;">
        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="white" viewBox="0 0 256 256"><path d="M228,128a12,12,0,0,1-12,12H140v76a12,12,0,0,1-24,0V140H40a12,12,0,0,1,0-24h76V40a12,12,0,0,1,24,0v76h76A12,12,0,0,1,228,128Z"></path></svg>
        <span>Submit New Raw Data</span>
    </a>
</div>

@if(Auth::user()->submissions->isEmpty())
<div class="card" style="text-align: center; padding: 60px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="var(--text-dim)" viewBox="0 0 256 256"><path d="M208,32H48A16,16,0,0,0,32,48V208a16,16,0,0,0,16,16H208a16,16,0,0,0,16-16V48A16,16,0,0,0,208,32Zm0,176H48V48H208V208ZM160,128a32,32,0,1,1-32-32A32,32,0,0,1,160,128Z"></path></svg>
    <h2 style="margin-top: 20px;">No projects yet</h2>
    <p style="color: var(--text-dim); margin-top: 8px;">Begin by submitting your first raw data package via Google Drive or SFTP Server.</p>
</div>
@else
<div class="card">
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Project Name</th>
                    <th>Submitted Date</th>
                    <th>Requested Outputs</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach(Auth::user()->submissions as $submission)
                <tr>
                    <td>
                        <strong>{{ $submission->project_name }}</strong>
                        @if($submission->description)
                            <br><small style="color: var(--text-dim)">{{ Str::limit($submission->description, 50) }}</small>
                        @endif
                    </td>
                    <td>{{ $submission->created_at->format('M d, Y') }}</td>
                    <td><small>{{ $submission->output_category }}</small></td>
                    <td>
                        <span class="badge badge-{{ strtolower($submission->status) }}">{{ $submission->status }}</span>
                    </td>
                    <td>
                        @if($submission->status === 'completed')
                        <div style="display: flex; flex-direction: column; align-items: flex-start; gap: 12px;">
                            @if($submission->is_archived)
                                <div style="background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 8px; padding: 12px; font-size: 0.8rem; color: #eab308; display: flex; align-items: center; gap: 8px; width: 100%;">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 256 256"><path d="M216,40H40A16,16,0,0,0,24,56V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,160H40V56H216V200ZM176,120a8,8,0,0,1-8,8H88a8,8,0,0,1,0-16h80A8,8,0,0,1,176,120Z"></path></svg>
                                    <span><strong>Project Archived</strong> - The files have been moved to backup storage to save space. Please contact the Admin to retrieve this data.</span>
                                </div>
                            @else
                            <div style="display: flex; gap: 10px; justify-content: flex-start; align-items: center; flex-wrap: wrap;">
                                @if($submission->admin_drive_link)
                                    <a href="{{ $submission->admin_drive_link }}" target="_blank" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.8rem; width: auto; margin: 0; user-select: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 256 256" style="margin-right: 6px;"><path d="M224,144v64a8,8,0,0,1-8,8H40a8,8,0,0,1-8-8V144a8,8,0,0,1,16,0v56H208V144a8,8,0,0,1,16,0Zm-101.66,5.66a8,8,0,0,0,11.32,0l40-40a8,8,0,0,0-11.32-11.32L136,124.69V40a8,8,0,0,0-16,0v84.69L93.66,98.34A8,8,0,0,0,82.34,109.66Z"></path></svg>
                                        <span>Download</span>
                                    </a>
                                @endif
    
                                @if($submission->processed_data_path)
                                    <a href="{{ route('user.view', $submission) }}" target="_blank" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.8rem; width: auto; margin: 0; background: var(--secondary); user-select: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 256 256" style="margin-right: 6px;"><path d="M247.31,124.76c-.35-.79-8.82-19.74-27.65-38.57C194.57,61.11,162.88,48,128,48S61.43,61.11,36.34,86.19c-18.83,18.83-27.3,37.78-27.65,38.57a16,16,0,0,0,0,10.48c.35.79,8.82,19.74,27.65,38.57C61.43,194.89,93.12,208,128,208s66.57-13.11,91.66-38.19c18.83-18.83,27.3-37.78,27.65-38.57A16,16,0,0,0,247.31,124.76ZM128,192c-30.78,0-59.03-10.87-80-30.63a123.44,123.44,0,0,1-21-24.18c.06-.12,5.84-11.9,16.89-23.33C64.93,93.1,95.17,80,128,80s63.07,13.1,84.11,33.86c11.05,11.43,16.83,23.21,16.89,23.33a123.44,123.44,0,0,1-21,24.18C187.03,181.13,158.78,192,128,192Zm0-112a48,48,0,1,0,48,48A48.05,48.05,0,0,0,128,80Zm0,80a32,32,0,1,1,32-32A32,32,0,0,1,128,160Z"></path></svg>
                                        <span>View Model</span>
                                    </a>

                                    <button id="geoExplorerBtn"
                                            class="btn btn-primary"
                                            style="padding: 10px 20px; font-size: 0.8rem; width: auto; margin: 0; background: #10b981; border: none; display: flex; align-items: center; cursor: pointer;">

                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white"
                                                viewBox="0 0 256 256" style="margin-right:6px;">
                                                <path d="M228.92,49.69a8,8,0,0,0-6.86-1.45L160.93,63.52,99.58,32.84a8,8,0,0,0-7.16,0L27.08,65.69A8,8,0,0,0,24,72.84V200a8,8,0,0,0,11.55,7.16l63.52-31.76,61.35,30.68a8,8,0,0,0,7.16,0l65.34-32.85A8,8,0,0,0,240,167.16V40A8,8,0,0,0,228.92,49.69Z"/>
                                            </svg>

                                            <span>GeoExplorer</span>
                                    </button>
                                @endif

                                @if($submission->processed_data_path && $submission->terrain_path && $submission->building_path)
                                    <a href="https://geo.digitalsabah.gov.my/flood/?terrain={{ urlencode($submission->terrain_path ?? '') }}&tiles3d={{ urlencode($submission->processed_data_path ?? '') }}&buildings={{ urlencode($submission->building_path ?? '') }}" target="_blank" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.8rem; width: auto; margin: 0; background: #06b6d4; user-select: none;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="white" viewBox="0 0 256 256" style="margin-right: 6px;"><path d="M224,144a8,8,0,0,1,0,16c-11.83,0-25.59,4.45-38.64,13.25-16.71,11.27-33.19,18.75-47.36,18.75s-30.65-7.48-47.36-18.75C77.59,164.45,63.83,160,52,160a8,8,0,0,1,0-16c11.83,0,25.59,4.45,38.64,13.25,16.71,11.27,33.19,18.75,47.36,18.75s30.65-7.48,47.36-18.75C198.41,148.45,212.17,144,224,144Zm0-48a8,8,0,0,0,0,16c11.83,0,25.59,4.45,38.64,13.25,16.71,11.27,33.19,18.75,47.36,18.75s30.65-7.48,47.36-18.75C77.59,116.45,63.83,112,52,112a8,8,0,0,0,0-16c11.83,0,25.59,4.45,38.64,13.25,16.71,11.27,33.19,18.75,47.36,18.75s30.65-7.48,47.36-18.75C198.41,100.45,212.17,96,224,96Z"></path></svg>
                                        <span>Flood Simulation</span>
                                    </a>
                                    @endif
                                    @if($submission->sftp_result_path)
                                        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 8px; padding: 10px; font-size: 0.8rem; color: var(--text); display: flex; align-items: center; gap: 8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="#10b981" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm48-88a48,48,0,1,1-48-48A48.05,48.05,0,0,1,176,128Z"></path></svg>
                                            <span><strong>SFTP Path:</strong> {{ $submission->sftp_result_path }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endif

                                <div style="background: rgba(255,255,255,0.02); border: 1px solid var(--border); border-radius: 12px; padding: 12px; min-width: 250px; max-width: 100%; text-align: left; box-shadow: inset 0 0 20px rgba(0,0,0,0.2); display: flex; flex-direction: column; gap: 12px;">

                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <div style="font-size: 0.65rem; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; opacity: 0.7;">3D Tileset URL</div>
                                            <button onclick="copyToClipboard('{{ $submission->processed_data_path }}', this)" style="background: none; border: none; padding: 2px; cursor: pointer; display: flex; align-items: center; color: var(--secondary); opacity: 0.8;" title="Copy to clipboard">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M216,40H88A16,16,0,0,0,72,56V72H56A16,16,0,0,0,40,88V216a16,16,0,0,0,16,16H184a16,16,0,0,0,16-16V200h16a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,144H200V88a16,16,0,0,0-16-16H88V56H216Zm-32,32H56V88H184Z"></path></svg>
                                            </button>
                                        </div>
                                        <a href="{{ $submission->processed_data_path ?: '#' }}" target="_blank" style="font-size: 0.75rem; color: var(--text); text-decoration: none; word-break: break-all; line-height: 1.5; display: block; opacity: 0.9;">
                                            {{ $submission->processed_data_path ?: 'Not provided' }}
                                        </a>
                                    </div>

                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <div style="font-size: 0.65rem; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; opacity: 0.7;">Terrain URL</div>
                                            <button onclick="copyToClipboard('{{ $submission->terrain_path }}', this)" style="background: none; border: none; padding: 2px; cursor: pointer; display: flex; align-items: center; color: var(--secondary); opacity: 0.8;" title="Copy to clipboard">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M216,40H88A16,16,0,0,0,72,56V72H56A16,16,0,0,0,40,88V216a16,16,0,0,0,16,16H184a16,16,0,0,0,16-16V200h16a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,144H200V88a16,16,0,0,0-16-16H88V56H216Zm-32,32H56V88H184Z"></path></svg>
                                            </button>
                                        </div>
                                        <a href="{{ $submission->terrain_path ?: '#' }}" target="_blank" style="font-size: 0.75rem; color: var(--text); text-decoration: none; word-break: break-all; line-height: 1.5; display: block; opacity: 0.9;">
                                            {{ $submission->terrain_path ?: 'Not provided' }}
                                        </a>
                                    </div>

                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <div style="font-size: 0.65rem; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; opacity: 0.7;">Building URL</div>
                                            <button onclick="copyToClipboard('{{ $submission->building_path }}', this)" style="background: none; border: none; padding: 2px; cursor: pointer; display: flex; align-items: center; color: var(--secondary); opacity: 0.8;" title="Copy to clipboard">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M216,40H88A16,16,0,0,0,72,56V72H56A16,16,0,0,0,40,88V216a16,16,0,0,0,16,16H184a16,16,0,0,0,16-16V200h16a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,144H200V88a16,16,0,0,0-16-16H88V56H216Zm-32,32H56V88H184Z"></path></svg>
                                            </button>
                                        </div>
                                        <a href="{{ $submission->building_path ?: '#' }}" target="_blank" style="font-size: 0.75rem; color: var(--text); text-decoration: none; word-break: break-all; line-height: 1.5; display: block; opacity: 0.9;">
                                            {{ $submission->building_path ?: 'Not provided' }}
                                        </a>
                                    </div>

                                    <div>
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                                            <div style="font-size: 0.65rem; color: var(--secondary); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 700; opacity: 0.7;">Orthophoto URL</div>
                                            <button onclick="copyToClipboard('{{ $submission->orthophoto_path }}', this)" style="background: none; border: none; padding: 2px; cursor: pointer; display: flex; align-items: center; color: var(--secondary); opacity: 0.8;" title="Copy to clipboard">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M216,40H88A16,16,0,0,0,72,56V72H56A16,16,0,0,0,40,88V216a16,16,0,0,0,16,16H184a16,16,0,0,0,16-16V200h16a16,16,0,0,0,16-16V56A16,16,0,0,0,216,40Zm0,144H200V88a16,16,0,0,0-16-16H88V56H216Zm-32,32H56V88H184Z"></path></svg>
                                            </button>
                                        </div>
                                        <a href="{{ $submission->orthophoto_path ?: '#' }}" target="_blank" style="font-size: 0.75rem; color: var(--text); text-decoration: none; word-break: break-all; line-height: 1.5; display: block; opacity: 0.9;">
                                            {{ $submission->orthophoto_path ?: 'Not provided' }}
                                        </a>
                                    </div>
                                </div>
                        </div>
                        @elseif($submission->status === 'rejected')
                        <div style="text-align: left;">
                            <span style="color: #ef4444; font-size: 0.8rem; display: block; margin-bottom: 4px;">Rejected</span>
                            @if($submission->admin_remarks)
                                <small style="color: #ef4444; font-style: italic; display: block; max-width: 250px; margin-bottom: 4px;">
                                    Remark: {{ $submission->admin_remarks }}
                                </small>
                            @endif
                            @if($submission->rejection_reason)
                                <small style="color: var(--text-dim); font-style: italic; display: block; max-width: 250px;">
                                    Reason: {{ $submission->rejection_reason }}
                                </small>
                            @endif
                        </div>
                        @else
                        <div style="text-align: left; display: flex; flex-direction: column; gap: 4px;">
                            <div style="display: flex; justify-content: flex-start; align-items: center; gap: 8px;">
                                <span class="pulse-dot"></span>
                                <span style="color: var(--text-dim); font-size: 0.82rem; font-weight: 500; letter-spacing: 0.02em;">
                                    @if($submission->status === 'processing')
                                        {{ $submission->admin_remarks ?: 'Processing Data...' }}
                                    @else
                                        {{ $submission->admin_remarks ?: 'Pending Review' }}
                                    @endif
                                </span>
                            </div>
                        </div>
                        <style>
                            @keyframes pulse {
                                0% { transform: scale(0.95); opacity: 0.5; }
                                50% { transform: scale(1.1); opacity: 1; }
                                100% { transform: scale(0.95); opacity: 0.5; }
                            }
                            .pulse-dot {
                                width: 8px;
                                height: 8px;
                                background-color: var(--primary);
                                border-radius: 50%;
                                display: inline-block;
                                box-shadow: 0 0 10px var(--primary);
                                animation: pulse 2s infinite ease-in-out;
                            }
                        </style>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@push('scripts')
<script>
function copyToClipboard(text, btn) {
    navigator.clipboard.writeText(text).then(() => {
        const originalIcon = btn.innerHTML;
        btn.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="#10b981" viewBox="0 0 256 256"><path d="M229.66,77.66l-128,128a8,8,0,0,1-11.32,0l-56-56a8,8,0,0,1,11.32-11.32L101,194.69,218.34,77.34a8,8,0,0,1,11.32,11.32Z"></path></svg>';
        setTimeout(() => {
            btn.innerHTML = originalIcon;
        }, 2000);
    });
}

document.getElementById('geoExplorerBtn').addEventListener('click', async function () {

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!csrfToken) {
        console.error("Missing CSRF token");
        return;
    }

    try {
        const res = await fetch('https://explorer.digitalsabah.gov.my/api/load', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                url: "https://demo-services.geovidia.my/Penampang/3DTiles/tileset.json",
                type: "load"
            })
        });

        const data = await res.json();
        console.log("POST result:", data);

        // ✅ OPEN IN NEW TAB
        window.open(
            "https://explorer.digitalsabah.gov.my/map",
            "_blank"
        );

    } catch (err) {
        console.error("Load error:", err);
    }
});
</script>
@endpush
@endsection
