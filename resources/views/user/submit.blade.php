@extends('layouts.app')

@section('content')
<div class="glass-card" style="max-width: 600px; margin: 0 auto;">
    <div class="logo-section" style="text-align: center;">
        <h1 style="font-size: 1.8rem; line-height: 1.2; margin-bottom: 8px;">Submit drone or satellite imagery</h1>
        <p>Fill in the details and share your project data link</p>
    </div>

    <form method="POST" action="{{ url('/user/submit') }}">
        @csrf
        <div class="form-group">
            <label for="project_name">Project Name</label>
            <input type="text" name="project_name" id="project_name" value="{{ old('project_name') }}" required placeholder="e.g. Building A Site Scan">
        </div>

        <div class="form-group">
            <label for="description">Description (Optional)</label>
            <textarea name="description" id="description" rows="2" placeholder="Describe the data or specific instructions...">{{ old('description') }}</textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="camera_config">Camera Configuration *</label>
                <select name="camera_config" id="camera_config" required>
                    <option value="Single-Lens" {{ old('camera_config') == 'Single-Lens' ? 'selected' : '' }}>Single-Lens</option>
                    <option value="Multi-Lens" {{ old('camera_config') == 'Multi-Lens' ? 'selected' : '' }}>Multi-Lens</option>
                </select>
            </div>
            <div class="form-group">
                <label for="category">Category *</label>
                <select name="category" id="category" required>
                    <option value="">-- Select --</option>
                    <option value="Agricultural" {{ old('category') == 'Agricultural' ? 'selected' : '' }}>Agricultural</option>
                    <option value="Construction" {{ old('category') == 'Construction' ? 'selected' : '' }}>Construction</option>
                    <option value="Urban" {{ old('category') == 'Urban' ? 'selected' : '' }}>Urban</option>
                    <option value="Industrial" {{ old('category') == 'Industrial' ? 'selected' : '' }}>Industrial</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label>Output Category *</label>
            <div style="display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px; background: rgba(0,0,0,0.1); padding: 15px; border-radius: 12px; border: 1px solid var(--border);">
                <label style="display: flex; align-items: center; gap: 8px; color: var(--text); cursor: default; margin-bottom: 0; opacity: 0.7;" title="Mandatory default output">
                    <input type="checkbox" name="output_category[]" value="3D Tiles" style="width: auto;" checked onclick="return false;"> 3D Tiles
                </label>
                <label style="display: flex; align-items: center; gap: 8px; color: var(--text); cursor: default; margin-bottom: 0; opacity: 0.7;" title="Mandatory default output">
                    <input type="checkbox" name="output_category[]" value="OSGB" style="width: auto;" checked onclick="return false;"> OSGB
                </label>
                <label style="display: flex; align-items: center; gap: 8px; color: var(--text); cursor: pointer; margin-bottom: 0;">
                    <input type="checkbox" name="output_category[]" value="DSM" style="width: auto;"> DSM
                </label>
                <label style="display: flex; align-items: center; gap: 8px; color: var(--text); cursor: pointer; margin-bottom: 0;">
                    <input type="checkbox" name="output_category[]" value="3DGS" style="width: auto;"> 3DGS
                </label>
                <label style="display: flex; align-items: center; gap: 8px; color: var(--text); cursor: pointer; margin-bottom: 0;">
                    <input type="checkbox" name="output_category[]" value="Orthophoto" style="width: auto;"> Orthophoto
                </label>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="image_metadata">Image Metadata *</label>
                <select name="image_metadata" id="image_metadata" required>
                    <option value="EXIF" {{ old('image_metadata') == 'EXIF' ? 'selected' : '' }}>EXIF (embedded)</option>
                    <option value="POS" {{ old('image_metadata') == 'POS' ? 'selected' : '' }}>POS</option>
                    <option value="EXIF & POS" {{ old('image_metadata') == 'EXIF & POS' ? 'selected' : '' }}>EXIF & POS</option>
                </select>
            </div>
            <div class="form-group">
                <label for="capture_date">Capture Date</label>
                <input type="date" name="capture_date" id="capture_date" value="{{ old('capture_date') }}">
            </div>
        </div>

        <div class="form-group" style="margin-top: 30px;">
            <label>Data Source *</label>
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <button type="button" onclick="setDataSource('cloud')" id="btn-cloud" class="btn btn-primary" style="flex: 1; font-size: 0.9rem; background: var(--primary); color: white;">Cloud Storage</button>
                <button type="button" onclick="setDataSource('sftp')" id="btn-sftp" class="btn" style="flex: 1; font-size: 0.9rem; border: 1px solid var(--border); background: rgba(255,255,255,0.05); color: white;">SFTP Server</button>
            </div>

            <!-- Cloud Storage Section -->
            <div id="section-cloud">
                <label for="google_drive_link" style="font-size: 0.85rem; opacity: 0.8;">Cloud Storage Link (Google Drive / OneDrive) *</label>
                <input type="url" name="google_drive_link" id="google_drive_link" value="{{ old('google_drive_link') }}" placeholder="Google Drive, OneDrive, or SharePoint link...">
                
                <div style="margin-top: 15px; padding: 12px 16px; background: rgba(59, 130, 246, 0.1); border: 1px solid rgba(59, 130, 246, 0.3); border-radius: 12px; display: flex; gap: 12px; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#3b82f6" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm16-88a8,8,0,0,1-8,8,16,16,0,1,0,16,16,8,8,0,0,1,16,0,32,32,0,1,1-64,0,8,8,0,0,1,16,0,16,16,0,1,0,16-16,8,8,0,0,1,8-8Zm-24-48a12,12,0,1,1,12,12A12,12,0,0,1,120,80Z"></path></svg>
                    <div style="color: var(--text); font-size: 0.85rem;">
                        Please ensure the link is set to <strong style="color: #3b82f6;">"Anyone with the link"</strong>.
                    </div>
                </div>
            </div>

            <!-- SFTP Section -->
            <div id="section-sftp" style="display: none;">
                <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="sftp_host" style="font-size: 0.85rem; opacity: 0.8;">Host (IP or Domain) *</label>
                        <input type="text" name="sftp_host" id="sftp_host" value="{{ old('sftp_host') }}" placeholder="e.g. 122.45.67.89">
                    </div>
                    <div>
                        <label for="sftp_port" style="font-size: 0.85rem; opacity: 0.8;">Port</label>
                        <input type="number" name="sftp_port" id="sftp_port" value="{{ old('sftp_port', 22) }}" placeholder="22">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label for="sftp_username" style="font-size: 0.85rem; opacity: 0.8;">Username *</label>
                        <input type="text" name="sftp_username" id="sftp_username" value="{{ old('sftp_username') }}" placeholder="sftp_user">
                    </div>
                    <div>
                        <label for="sftp_password" style="font-size: 0.85rem; opacity: 0.8;">Password *</label>
                        <input type="password" name="sftp_password" id="sftp_password" placeholder="••••••••" autocomplete="new-password">
                    </div>
                </div>
                <div>
                    <label for="sftp_path" style="font-size: 0.85rem; opacity: 0.8;">Data Path (Optional)</label>
                    <input type="text" name="sftp_path" id="sftp_path" value="{{ old('sftp_path') }}" placeholder="e.g. /home/data/project1">
                </div>
                <div style="margin-top: 15px; padding: 12px 16px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; display: flex; gap: 12px; align-items: center;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="#10b981" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm48-88a48,48,0,1,1-48-48A48.05,48.05,0,0,1,176,128Zm-16,0a32,32,0,1,0-32,32A32,32,0,0,0,160,128Z"></path></svg>
                    <div style="color: var(--text); font-size: 0.85rem;">
                        Our team will use these details to <strong style="color: #10b981;">Pull</strong> the raw data securely using SFTP.
                    </div>
                </div>
            </div>
        </div>

        <script>
            function setDataSource(type) {
                const cloudSection = document.getElementById('section-cloud');
                const sftpSection = document.getElementById('section-sftp');
                const cloudBtn = document.getElementById('btn-cloud');
                const sftpBtn = document.getElementById('btn-sftp');

                if (type === 'cloud') {
                    cloudSection.style.display = 'block';
                    sftpSection.style.display = 'none';
                    cloudBtn.classList.add('btn-primary');
                    cloudBtn.style.background = 'var(--primary)';
                    cloudBtn.style.color = 'white';
                    sftpBtn.classList.remove('btn-primary');
                    sftpBtn.style.background = 'rgba(255,255,255,0.05)';
                    sftpBtn.style.border = '1px solid var(--border)';
                    sftpBtn.style.color = 'white';
                } else {
                    cloudSection.style.display = 'none';
                    sftpSection.style.display = 'block';
                    sftpBtn.classList.add('btn-primary');
                    sftpBtn.style.background = 'var(--primary)';
                    sftpBtn.style.color = 'white';
                    cloudBtn.classList.remove('btn-primary');
                    cloudBtn.style.background = 'rgba(255,255,255,0.05)';
                    cloudBtn.style.border = '1px solid var(--border)';
                    cloudBtn.style.color = 'white';
                }
            }

            // Set initial state based on old input
            @if(old('sftp_host'))
                setDataSource('sftp');
            @endif
        </script>

        <div style="display: flex; gap: 16px; margin-top: 32px;">
            <a href="{{ route('dashboard') }}" class="btn" style="flex: 1; border: 1px solid var(--border); color: var(--text-dim); text-decoration: none; display: flex; align-items: center; justify-content: center;">Cancel</a>
            <button type="submit" class="btn btn-primary" style="flex: 1;">
                <span>Submit</span>
            </button>
        </div>
    </form>
</div>
@endsection
