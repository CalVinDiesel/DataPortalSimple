@extends('layouts.app')

@section('content')
<div class="glass-card" style="max-width: 600px; margin: 0 auto;">
    <div class="logo-section" style="text-align: center;">
        <h1 style="font-size: 1.8rem; line-height: 1.2; margin-bottom: 8px;">Register Existing 3D Model</h1>
        <p>Provide your existing 3D tileset URLs to use our analysis tools</p>
    </div>

    <form id="uploadForm" method="POST" action="{{ route('user.register_model.submit') }}" enctype="multipart/form-data" onsubmit="submitWithProgress(event)">
        @csrf
        <div class="form-group">
            <label for="project_name">Project Name *</label>
            <input type="text" name="project_name" id="project_name" value="{{ old('project_name') }}" required placeholder="e.g. My External Site Scan">
        </div>

        <div class="form-group">
            <label for="description">Description (Optional)</label>
            <textarea name="description" id="description" rows="2" placeholder="Describe the project...">{{ old('description') }}</textarea>
        </div>

        <div style="background: rgba(255,255,255,0.03); padding: 20px; border-radius: 12px; border: 1px solid rgba(255,255,255,0.1); margin-bottom: 25px;">
            <label style="display: block; font-size: 0.8rem; color: var(--secondary); text-transform: uppercase; letter-spacing: 1px; font-weight: 700; margin-bottom: 15px;">Data Source Method</label>
            <div style="display: flex; gap: 10px;">
                <button type="button" id="btnUpload" onclick="switchMethod('upload')" style="flex: 1; padding: 12px; background: var(--primary); border: none; border-radius: 8px; color: white; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                    Direct File Upload
                </button>
                <button type="button" id="btnUrl" onclick="switchMethod('url')" style="flex: 1; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                    Direct URL
                </button>
                <button type="button" id="btnFiles" onclick="switchMethod('files')" style="flex: 1; padding: 12px; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 8px; color: white; cursor: pointer; font-weight: 600; transition: all 0.3s;">
                    Send Files (G-Drive/SFTP)
                </button>
            </div>
        </div>

        <!-- SECTION 0: UPLOAD DIRECT FILE -->
        <div id="sectionUpload">
            <div style="background: rgba(16, 185, 129, 0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2); margin-bottom: 25px;">
                <h3 style="font-size: 0.75rem; color: #10b981; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    Upload 3D Model File
                </h3>
                <div class="form-group">
                    <div id="upload_ui_box" 
                         ondragover="handleDragOver(event)" 
                         ondragleave="handleDragLeave(event)" 
                         ondrop="handleDrop(event)"
                         onclick="document.getElementById('file_input').click()"
                         style="border: 2px dashed rgba(16, 185, 129, 0.5); padding: 40px 20px; text-align: center; border-radius: 12px; background: rgba(0,0,0,0.2); transition: all 0.3s ease; cursor: pointer;">
                        <svg style="width: 48px; height: 48px; color: #10b981; margin: 0 auto 15px auto;" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <h4 style="color: white; margin-bottom: 10px; font-size: 1.1rem;">Upload your 3D Models</h4>
                        <p style="color: #9ca3af; font-size: 0.85rem; margin-bottom: 5px;">Drag & Drop files or dataset folders here</p>
                        <p style="color: #9ca3af; font-size: 0.75rem; opacity: 0.7;">(or click anywhere in this box to browse files)</p>
                        
                        <p id="file_count_display" style="color: #10b981; margin-top: 20px; font-weight: bold; display: none; font-size: 0.9rem;"></p>
                        <ul id="file_list_container" style="list-style: none; padding: 0; margin-top: 15px; max-height: 200px; overflow-y: auto; text-align: left;"></ul>

                        <input type="file" id="file_input" multiple style="display: none;" onchange="handleFileSelect(this)">
                        <input type="file" id="folder_input" multiple webkitdirectory directory style="display: none;" onchange="handleFileSelect(this)">
                    </div>

                    <script>
                        const box = document.getElementById('upload_ui_box');
                        const display = document.getElementById('file_count_display');
                        const fileInput = document.getElementById('file_input');
                        const folderInput = document.getElementById('folder_input');
                        const listContainer = document.getElementById('file_list_container');

                        // Global DataTransfer to hold all files across multiple drops/clicks
                        let globalDt = new DataTransfer();

                        function formatBytes(bytes, decimals = 2) {
                            if (bytes === 0) return '0 Bytes';
                            const k = 1024;
                            const dm = decimals < 0 ? 0 : decimals;
                            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                            const i = Math.floor(Math.log(bytes) / Math.log(k));
                            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
                        }

                        function renderFileList() {
                            listContainer.innerHTML = '';
                            
                            // Remove old hidden inputs
                            document.querySelectorAll('.hidden-file-path').forEach(el => el.remove());
                            
                            for (let i = 0; i < globalDt.files.length; i++) {
                                const f = globalDt.files[i];
                                const displayPath = f.customPath || f.webkitRelativePath || f.name;
                                
                                // Create hidden input for the PHP backend to read
                                const hiddenInput = document.createElement('input');
                                hiddenInput.type = 'hidden';
                                hiddenInput.name = 'file_paths[]';
                                hiddenInput.value = displayPath;
                                hiddenInput.className = 'hidden-file-path';
                                document.getElementById('upload_ui_box').appendChild(hiddenInput);

                                listContainer.innerHTML += `
                                    <li style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(255,255,255,0.05); margin-bottom: 5px; border-radius: 6px;">
                                        <div style="display: flex; align-items: center; gap: 10px; overflow: hidden;">
                                            <svg style="width: 20px; color: #9ca3af; flex-shrink: 0;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <span style="color: white; font-size: 0.85rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 250px;">${displayPath}</span>
                                            <span style="color: #9ca3af; font-size: 0.75rem;">${formatBytes(f.size)}</span>
                                        </div>
                                        <button type="button" onclick="event.stopPropagation(); removeFile(${i})" style="background: none; border: none; color: #ef4444; cursor: pointer; padding: 5px; border-radius: 4px; transition: background 0.2s;" onmouseover="this.style.background='rgba(239, 68, 68, 0.1)'" onmouseout="this.style.background='none'">
                                            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                        </button>
                                    </li>
                                `;
                            }
                            
                            // Assign to the hidden input
                            fileInput.files = globalDt.files;
                            fileInput.name = 'raw_model_file[]';
                            folderInput.name = ''; // Disable folder input since we merged it
                            
                            const count = globalDt.files.length;
                            if (count > 0) {
                                display.style.display = 'block';
                                display.innerHTML = `✅ <b>${count}</b> file(s) loaded and ready to register!`;
                                box.style.borderColor = '#10b981';
                                box.style.background = 'rgba(16, 185, 129, 0.05)';
                            } else {
                                display.style.display = 'none';
                                box.style.borderColor = 'rgba(16, 185, 129, 0.5)';
                                box.style.background = 'rgba(0,0,0,0.2)';
                            }
                        }

                        function removeFile(index) {
                            globalDt.items.remove(index);
                            renderFileList();
                        }

                        // Handle click browsing
                        function handleFileSelect(input) {
                            for (let i = 0; i < input.files.length; i++) {
                                globalDt.items.add(input.files[i]);
                            }
                            renderFileList();
                        }

                        // Drag and Drop handlers
                        function handleDragOver(e) {
                            e.preventDefault();
                            box.style.borderColor = '#3b82f6';
                            box.style.background = 'rgba(59, 130, 246, 0.1)';
                        }

                        function handleDragLeave(e) {
                            e.preventDefault();
                            renderFileList(); // Resets color based on file count
                        }

                        async function handleDrop(e) {
                            e.preventDefault();
                            display.style.display = 'block';
                            display.innerHTML = `⏳ Processing dropped items...`;
                            box.style.borderColor = '#f59e0b';

                            const items = e.dataTransfer.items;

                            async function getFilesFromEntry(entry, path = '') {
                                if (entry.isFile) {
                                    const file = await new Promise(resolve => entry.file(resolve));
                                    file.customPath = path + file.name;
                                    globalDt.items.add(file);
                                } else if (entry.isDirectory) {
                                    const dirReader = entry.createReader();
                                    const entries = await new Promise(resolve => dirReader.readEntries(resolve));
                                    for (let i = 0; i < entries.length; i++) {
                                        await getFilesFromEntry(entries[i], path + entry.name + '/');
                                    }
                                }
                            }

                            for (let i = 0; i < items.length; i++) {
                                const item = items[i].webkitGetAsEntry();
                                if (item) {
                                    await getFilesFromEntry(item);
                                }
                            }

                            renderFileList();
                        }
                    </script>
                </div>
                <div style="margin-top: 15px; padding: 12px 16px; background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); border-radius: 12px; display: flex; gap: 12px; align-items: center;">
                    <div style="color: var(--text); font-size: 0.85rem;">
                        If it is a raw model (like .obj), our server will automatically convert it to a web-ready format.
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 1: DIRECT URLS -->
        <div id="sectionUrl" style="display: none;">
            <div style="background: rgba(59, 130, 246, 0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(59, 130, 246, 0.2); margin-bottom: 25px;">
                <h3 style="font-size: 0.75rem; color: #3b82f6; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    External Data Links
                </h3>

                <div class="form-group">
                    <label>3D Tiles URL (tileset.json) or Cesium Ion ID <span style="color: #ef4444">*</span></label>
                    <input type="text" name="processed_data_path" placeholder="https://example.com/tiles/tileset.json or Asset ID" value="{{ old('processed_data_path') }}" autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Terrain URL (Optional)</label>
                    <input type="url" name="terrain_path" placeholder="https://example.com/terrain/" value="{{ old('terrain_path') }}" autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Building URL (Optional)</label>
                    <input type="url" name="building_path" placeholder="https://example.com/building.geojson" value="{{ old('building_path') }}" autocomplete="off">
                </div>

                <div class="form-group">
                    <label>Orthophoto URL (Optional)</label>
                    <input type="url" name="orthophoto_path" placeholder="https://example.com/ortho/" value="{{ old('orthophoto_path') }}" autocomplete="off">
                </div>
            </div>
        </div>

        <!-- SECTION 2: SEND FILES -->
        <div id="sectionFiles" style="display: none;">
            <div style="background: rgba(16, 185, 129, 0.05); padding: 20px; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.2); margin-bottom: 25px;">
                <h3 style="font-size: 0.75rem; color: #10b981; text-transform: uppercase; letter-spacing: 1.5px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                    Transfer Processed Files
                </h3>
                
                <p style="color: var(--text-dim); font-size: 0.8rem; margin-bottom: 20px;">Use this option if you have the processed 3D model folder but need us to host it on our servers.</p>

                <div class="form-group">
                    <label>Google Drive Folder Link</label>
                    <div style="display: flex; gap: 10px; margin-bottom: 5px;">
                        <input type="url" id="google_drive_link" name="google_drive_link" placeholder="https://drive.google.com/..." value="{{ old('google_drive_link') }}" autocomplete="off" style="flex: 1;">
                        <button type="button" id="btnVerifyDrive" onclick="verifyGoogleDrive()" style="padding: 10px 20px; background: #3b82f6; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 8px; transition: all 0.3s;">
                            <span id="verifyText">Verify Folder</span>
                            <span id="verifySpinner" style="display: none; width: 14px; height: 14px; border: 2px solid white; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></span>
                        </button>
                    </div>
                    <small style="color: var(--text-dim); display: block; margin-top: 4px;">Please ensure the link is set to <span style="color: #3b82f6; font-weight: bold;">"Anyone with the link"</span>.</small>
                </div>


                <div style="margin: 25px 0; display: flex; align-items: center; gap: 15px;">
                    <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.1);"></div>
                    <span style="font-size: 0.7rem; color: var(--text-dim); text-transform: uppercase; letter-spacing: 1px;">OR USE SFTP</span>
                    <div style="flex: 1; height: 1px; background: rgba(255,255,255,0.1);"></div>
                </div>

                    <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Host (IP or Domain) <span style="color: #ef4444">*</span></label>
                            <input type="text" id="sftp_host" name="sftp_host" placeholder="e.g. 122.45.67.89" value="{{ old('sftp_host') }}" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>Port</label>
                            <input type="number" id="sftp_port" name="sftp_port" placeholder="22" value="{{ old('sftp_port', 22) }}" autocomplete="off">
                        </div>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div class="form-group">
                            <label>Username <span style="color: #ef4444">*</span></label>
                            <input type="text" id="sftp_username" name="sftp_username" placeholder="sftp_user" value="{{ old('sftp_username') }}" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label>Password <span style="color: #ef4444">*</span></label>
                            <input type="password" id="sftp_password" name="sftp_password" placeholder="........" autocomplete="new-password">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Data Path (Optional)</label>
                        <input type="text" id="sftp_path" name="sftp_path" placeholder="e.g. /home/data/project1" value="{{ old('sftp_path') }}" autocomplete="off">
                    </div>
                    
                    <button type="button" id="btnVerifySftp" onclick="verifySftp()" style="width: 100%; padding: 12px; background: #10b981; border: none; border-radius: 8px; color: white; font-weight: 600; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px; transition: all 0.3s; margin-top: 10px;">
                        <span id="verifySftpText">Verify SFTP Server</span>
                        <span id="verifySftpSpinner" style="display: none; width: 14px; height: 14px; border: 2px solid white; border-top-color: transparent; border-radius: 50%; animation: spin 1s linear infinite;"></span>
                    </button>

                    <!-- RECOMMENDED FOLDER STRUCTURE -->
                    <div style="margin-top: 25px; padding: 20px; background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px;">
                        <h4 style="font-size: 0.75rem; color: #3b82f6; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 256 256"><path d="M216,72H130.67L102.93,51.2A16.12,16.12,0,0,0,93.33,48H40A16,16,0,0,0,24,64V200a16,16,0,0,0,16,16H216a16,16,0,0,0,16-16V88A16,16,0,0,0,216,72ZM40,64H93.33l27.74,20.8A16.12,16.12,0,0,0,130.67,88H216v16H40ZM216,200H40V120H216V200Z"></path></svg>
                            Recommended Folder Structure
                        </h4>
                        
                        <p style="color: var(--text-dim); font-size: 0.8rem; margin-bottom: 15px;">To ensure fast processing and compatibility with our GIS tools, please organize your submission into the following subfolders:</p>
                        
                        <div style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; font-family: monospace; font-size: 0.8rem; color: #e2e8f0; line-height: 1.6; margin-bottom: 15px;">
                            <div style="color: #60a5fa; font-weight: bold;">📁 Your_Project_Name/</div>
                            <div style="padding-left: 20px; border-left: 1px dashed rgba(255,255,255,0.1); margin-left: 6px;">
                                <div style="margin-top: 5px;"><span style="color: #34d399;">├── 📁 3D_Model/</span></div>
                                <div style="padding-left: 20px; color: var(--text-dim);">├── 📄 tileset.json <span style="font-size: 0.7rem;">(Max {{ config('portal.limits.tileset_mb', 50) }}MB)</span></div>
                                <div style="padding-left: 20px; color: var(--text-dim);">└── 📁 Data/ <span style="font-size: 0.7rem;">(All .b3dm files)</span></div>
                                
                                <div style="margin-top: 5px;"><span style="color: #fbbf24;">├── 📁 Terrain/</span></div>
                                <div style="padding-left: 20px; color: var(--text-dim);">└── 📄 layer.json <span style="font-size: 0.7rem;">(Max {{ config('portal.limits.terrain_mb', 10) }}MB)</span></div>
                                
                                <div style="margin-top: 5px;"><span style="color: #a78bfa;">├── 📁 Buildings/</span></div>
                                <div style="padding-left: 20px; color: var(--text-dim);">└── 📄 building.geojson <span style="font-size: 0.7rem;">(Max {{ config('portal.limits.buildings_mb', 500) }}MB)</span></div>
                                
                                <div style="margin-top: 5px;"><span style="color: #f472b6;">└── 📁 Orthophoto/</span></div>
                                <div style="padding-left: 20px; color: var(--text-dim);">    └── 📄 ortho.tif <span style="font-size: 0.7rem;">(Max {{ config('portal.limits.orthophoto_gb', 10) }}GB)</span></div>
                            </div>
                        </div>
                        
                        <p style="margin: 0; font-size: 0.7rem; color: var(--text-dim); font-style: italic;">Note: Our robotic deep-scan will still dynamically find your files as long as the master files (like tileset.json) are present and structurally sound.</p>
                    </div>
                    
                    <!-- LIVE VERIFICATION CHECKLIST CARD -->
                    <div id="verificationCard" style="margin-top: 25px; padding: 20px; background: rgba(255,255,255,0.02); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; display: none;">
                        <h4 style="font-size: 0.8rem; color: #3b82f6; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 15px; display: flex; align-items: center; gap: 8px;">
                            <span id="verificationStatusIcon" style="font-size: 1.1rem;">🔍</span>
                            <span id="verificationStatusText" style="font-weight: 700;">Folder Verification Checklist</span>
                        </h4>
                        
                        <div style="display: flex; flex-direction: column; gap: 12px;">
                            <div id="check_3d" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; border-left: 4px solid #94a3b8; transition: all 0.3s;">
                                <div>
                                    <span style="font-weight: 600; font-size: 0.85rem; display: block; color: white;">3D Tileset (tileset.json) <span style="color: #ef4444">*</span></span>
                                    <span class="check-msg" style="font-size: 0.75rem; color: var(--text-dim);">Waiting for verification...</span>
                                </div>
                                <span class="check-badge" style="font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; background: rgba(255,255,255,0.05); color: #94a3b8; font-weight: bold; letter-spacing: 0.5px;">PENDING</span>
                            </div>

                            <div id="check_terrain" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; border-left: 4px solid #94a3b8; transition: all 0.3s;">
                                <div>
                                    <span style="font-weight: 600; font-size: 0.85rem; display: block; color: white;">Terrain (layer.json)</span>
                                    <span class="check-msg" style="font-size: 0.75rem; color: var(--text-dim);">Waiting for verification...</span>
                                </div>
                                <span class="check-badge" style="font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; background: rgba(255,255,255,0.05); color: #94a3b8; font-weight: bold; letter-spacing: 0.5px;">PENDING</span>
                            </div>

                            <div id="check_building" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; border-left: 4px solid #94a3b8; transition: all 0.3s;">
                                <div>
                                    <span style="font-weight: 600; font-size: 0.85rem; display: block; color: white;">Buildings (building.geojson)</span>
                                    <span class="check-msg" style="font-size: 0.75rem; color: var(--text-dim);">Waiting for verification...</span>
                                </div>
                                <span class="check-badge" style="font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; background: rgba(255,255,255,0.05); color: #94a3b8; font-weight: bold; letter-spacing: 0.5px;">PENDING</span>
                            </div>

                            <div id="check_orthophoto" style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: rgba(255,255,255,0.03); border-radius: 8px; border-left: 4px solid #94a3b8; transition: all 0.3s;">
                                <div>
                                    <span style="font-weight: 600; font-size: 0.85rem; display: block; color: white;">Orthophoto (ortho.tif)</span>
                                    <span class="check-msg" style="font-size: 0.75rem; color: var(--text-dim);">Waiting for verification...</span>
                                </div>
                                <span class="check-badge" style="font-size: 0.7rem; padding: 4px 8px; border-radius: 6px; background: rgba(255,255,255,0.05); color: #94a3b8; font-weight: bold; letter-spacing: 0.5px;">PENDING</span>
                            </div>
                        </div>
                        <p id="verificationFeedback" style="margin-top: 15px; margin-bottom: 0; font-size: 0.8rem; line-height: 1.4; color: var(--text-dim); padding-top: 10px; border-top: 1px dashed rgba(255,255,255,0.1);"></p>
                    </div>
            </div>
        </div>

        <div class="form-group" style="margin-bottom: 25px;">
            <label for="remarks">Admin Remarks / File Location Details</label>
            <textarea name="remarks" id="remarks" rows="2" placeholder="e.g. The files are inside the 'Phase_1/Final' subfolder...">{{ old('remarks') }}</textarea>
            <small style="color: var(--text-dim); font-size: 0.7rem;">Provide any extra details to help our Admin or System find your files faster.</small>
        </div>

        <div style="background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 15px; display: flex; gap: 15px; align-items: flex-start; margin-bottom: 25px;">
            <div style="background: rgba(59, 130, 246, 0.1); color: #3b82f6; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 0.8rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 256 256"><path d="M128,24A104,104,0,1,0,232,128,104.11,104.11,0,0,0,128,24Zm0,192a88,88,0,1,1,88-88A88.1,88.1,0,0,1,128,216Zm16-40a8,8,0,0,1-8,8,16,16,0,0,1-16-16V128a8,8,0,0,1,0-16,16,16,0,0,1,16,16v32A8,8,0,0,1,144,176Zm-28-80a12,12,0,1,1,12,12A12,12,0,0,1,116,96Z"></path></svg>
            </div>
            <p style="margin: 0; font-size: 0.75rem; color: var(--text-dim); line-height: 1.5;">
                Your model will be added to your dashboard after a quick verification by our Admin team.
            </p>
        </div>

        <div style="display: flex; gap: 15px;">
            <a href="{{ route('dashboard') }}" class="btn" style="flex: 1; background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); text-align: center; text-decoration: none; padding: 14px; color: white;">Cancel</a>
            <button type="submit" id="btnSubmit" class="btn btn-primary" style="flex: 2; padding: 14px;">Register Model</button>
        </div>
    </form>
</div>

<!-- FULL SCREEN LOADING OVERLAY WITH PROGRESS BAR -->
<div id="loadingOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.85); z-index: 9999; backdrop-filter: blur(5px); flex-direction: column; justify-content: center; align-items: center; text-align: center; color: white;">
    <h2 id="progressTitle" style="font-size: 1.8rem; margin-bottom: 10px; color: #3b82f6; font-weight: 700;">Uploading 3D Data...</h2>
    
    <!-- Progress Bar Container -->
    <div style="width: 80%; max-width: 600px; height: 30px; background: rgba(255,255,255,0.1); border-radius: 15px; margin: 20px 0; overflow: hidden; border: 1px solid rgba(255,255,255,0.2);">
        <div id="progressBar" style="width: 0%; height: 100%; background: linear-gradient(90deg, #3b82f6, #10b981); transition: width 0.3s ease; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 0.85rem; text-shadow: 1px 1px 2px rgba(0,0,0,0.5);">0%</div>
    </div>
    
    <p id="progressDetails" style="font-size: 1rem; color: #9ca3af; margin-bottom: 30px; font-family: monospace;">0 MB / 0 MB uploaded</p>
    
    <div style="background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.3); padding: 12px 20px; border-radius: 8px; display: inline-block;">
        <span style="color: #eab308; font-weight: bold;">⚠️ PLEASE DO NOT CLOSE OR REFRESH THIS TAB</span>
    </div>
</div>

<script>
    function submitWithProgress(event) {
        event.preventDefault();
        
        const form = document.getElementById('uploadForm');
        const formData = new FormData(form);
        
        // Show Overlay
        document.getElementById('loadingOverlay').style.display = 'flex';
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('btnSubmit').innerHTML = 'Uploading...';
        
        const progressBar = document.getElementById('progressBar');
        const progressDetails = document.getElementById('progressDetails');
        const progressTitle = document.getElementById('progressTitle');
        
        const xhr = new XMLHttpRequest();
        xhr.open('POST', form.action, true);
        
        xhr.upload.onprogress = function(e) {
            if (e.lengthComputable) {
                const percentComplete = Math.round((e.loaded / e.total) * 100);
                progressBar.style.width = percentComplete + '%';
                progressBar.textContent = percentComplete + '%';
                
                const loadedMB = (e.loaded / (1024 * 1024)).toFixed(2);
                const totalMB = (e.total / (1024 * 1024)).toFixed(2);
                progressDetails.textContent = `${loadedMB} MB / ${totalMB} MB uploaded`;
                
                if (percentComplete === 100) {
                    progressTitle.textContent = "Processing on Server... Please Wait";
                    progressBar.style.background = "#10b981"; // Turn green
                }
            }
        };
        
        xhr.onload = function() {
            // Laravel redirects on success, so we just redirect the browser to the dashboard
            window.location.href = "{{ route('dashboard') }}";
        };
        
        xhr.onerror = function() {
            alert('An error occurred during the upload. Please try again or check your file size limits.');
            document.getElementById('loadingOverlay').style.display = 'none';
            document.getElementById('btnSubmit').disabled = false;
        };
        
        xhr.send(formData);
    }
</script>

<style>
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
</style>

<script>
    let currentMethod = 'upload';
    let isFileTransferVerified = false;

    function switchMethod(method) {
        currentMethod = method;
        const btnUpload = document.getElementById('btnUpload');
        const btnUrl = document.getElementById('btnUrl');
        const btnFiles = document.getElementById('btnFiles');
        
        const sectionUpload = document.getElementById('sectionUpload');
        const sectionUrl = document.getElementById('sectionUrl');
        const sectionFiles = document.getElementById('sectionFiles');
        
        const btnSubmit = document.getElementById('btnSubmit');

        [btnUpload, btnUrl, btnFiles].forEach(btn => {
            btn.style.background = 'rgba(255,255,255,0.05)';
            btn.style.border = '1px solid rgba(255,255,255,0.1)';
        });
        
        [sectionUpload, sectionUrl, sectionFiles].forEach(sec => sec.style.display = 'none');

        if (method === 'upload') {
            btnUpload.style.background = 'var(--primary)';
            btnUpload.style.border = 'none';
            sectionUpload.style.display = 'block';
            
            btnSubmit.disabled = false;
            btnSubmit.style.opacity = '1';
            btnSubmit.style.cursor = 'pointer';
        } else if (method === 'url') {
            btnUrl.style.background = 'var(--primary)';
            btnUrl.style.border = 'none';
            sectionUrl.style.display = 'block';

            btnSubmit.disabled = false;
            btnSubmit.style.opacity = '1';
            btnSubmit.style.cursor = 'pointer';
        } else {
            btnFiles.style.background = '#10b981';
            btnFiles.style.border = 'none';
            sectionFiles.style.display = 'block';

            if (!isFileTransferVerified) {
                btnSubmit.disabled = true;
                btnSubmit.style.opacity = '0.5';
                btnSubmit.style.cursor = 'not-allowed';
            } else {
                btnSubmit.disabled = false;
                btnSubmit.style.opacity = '1';
                btnSubmit.style.cursor = 'pointer';
            }
        }
    }

    async function verifyGoogleDrive() {
        const linkInput = document.getElementById('google_drive_link');
        const link = linkInput.value.trim();

        if (!link) {
            alert('Please paste a Google Drive folder link first.');
            return;
        }

        const btnVerify = document.getElementById('btnVerifyDrive');
        const verifyText = document.getElementById('verifyText');
        const verifySpinner = document.getElementById('verifySpinner');
        const verificationCard = document.getElementById('verificationCard');
        const btnSubmit = document.getElementById('btnSubmit');

        // Loading State
        btnVerify.disabled = true;
        verifyText.textContent = 'Scanning...';
        verifySpinner.style.display = 'inline-block';
        
        verificationCard.style.display = 'block';
        document.getElementById('verificationStatusText').textContent = '🤖 Robotic Scanning In Progress...';
        document.getElementById('verificationStatusIcon').textContent = '⚡';
        
        // Reset checklist visual states to scanning/loading
        const checklistKeys = ['3d', 'terrain', 'building', 'orthophoto'];
        checklistKeys.forEach(k => {
            const el = document.getElementById('check_' + k);
            el.style.borderLeftColor = '#3b82f6';
            el.querySelector('.check-msg').textContent = 'Verifying...';
            el.querySelector('.check-msg').style.color = 'var(--text-dim)';
            el.querySelector('.check-badge').textContent = 'SCANNING';
            el.querySelector('.check-badge').style.background = 'rgba(59, 130, 246, 0.1)';
            el.querySelector('.check-badge').style.color = '#3b82f6';
        });

        // Simulate small delay for cool robot effect
        await new Promise(r => setTimeout(r, 1200));

        try {
            const response = await fetch('{{ route("user.register_model.verify") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ google_drive_link: link })
            });

            const data = await response.json();

            if (response.status === 200 || data.results) {
                // Parse results
                updateChecklistItem('3d', data.results['3d_tileset']);
                updateChecklistItem('terrain', data.results['terrain']);
                updateChecklistItem('building', data.results['building']);
                updateChecklistItem('orthophoto', data.results['orthophoto']);

                const feedback = document.getElementById('verificationFeedback');
                if (data.success) {
                    document.getElementById('verificationStatusText').textContent = '✅ Verification Successful!';
                    document.getElementById('verificationStatusIcon').textContent = '🎉';
                    feedback.textContent = 'All mandatory naming, file format, and size rules have been successfully verified! You are authorized to register this model.';
                    feedback.style.color = '#10b981';
                    
                    isFileTransferVerified = true;
                    // Enable Submit Button!
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                    btnSubmit.style.cursor = 'pointer';
                } else {
                    document.getElementById('verificationStatusText').textContent = '❌ Verification Failed';
                    document.getElementById('verificationStatusIcon').textContent = '⚠️';
                    feedback.textContent = data.message || 'One or more required validation checks failed. Please inspect the list and update the files in your folder.';
                    feedback.style.color = '#ef4444';
                    
                    isFileTransferVerified = false;
                    // Disable Submit Button
                    btnSubmit.disabled = true;
                    btnSubmit.style.opacity = '0.5';
                    btnSubmit.style.cursor = 'not-allowed';
                }
            } else {
                throw new Error(data.error || 'Server error occurred during scan.');
            }
        } catch (error) {
            document.getElementById('verificationStatusText').textContent = '❌ Scan Error';
            document.getElementById('verificationStatusIcon').textContent = '🚨';
            document.getElementById('verificationFeedback').textContent = error.message || 'Failed to scan the Google Drive folder. Ensure the link is shared as "Anyone with the link can view".';
            document.getElementById('verificationFeedback').style.color = '#ef4444';

            isFileTransferVerified = false;
            // Reset checklist to error state
            checklistKeys.forEach(k => {
                const el = document.getElementById('check_' + k);
                el.style.borderLeftColor = '#ef4444';
                el.querySelector('.check-msg').textContent = 'Scan aborted due to connection error.';
                el.querySelector('.check-msg').style.color = '#ef4444';
                el.querySelector('.check-badge').textContent = 'FAILED';
                el.querySelector('.check-badge').style.background = 'rgba(239, 68, 68, 0.1)';
                el.querySelector('.check-badge').style.color = '#ef4444';
            });
            
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = '0.5';
            btnSubmit.style.cursor = 'not-allowed';
        } finally {
            btnVerify.disabled = false;
            verifyText.textContent = 'Verify Folder';
            verifySpinner.style.display = 'none';
        }
    }

    async function verifySftp() {
        const host = document.getElementById('sftp_host').value.trim();
        const port = document.getElementById('sftp_port').value.trim();
        const username = document.getElementById('sftp_username').value.trim();
        const password = document.getElementById('sftp_password').value.trim();
        const path = document.getElementById('sftp_path').value.trim();

        if (!host || !username || !password) {
            alert('Please fill in Host, Username, and Password.');
            return;
        }

        const btnVerify = document.getElementById('btnVerifySftp');
        const verifyText = document.getElementById('verifySftpText');
        const verifySpinner = document.getElementById('verifySftpSpinner');
        const verificationCard = document.getElementById('verificationCard');
        const btnSubmit = document.getElementById('btnSubmit');

        btnVerify.disabled = true;
        verifyText.textContent = 'Connecting & Scanning...';
        verifySpinner.style.display = 'inline-block';
        
        verificationCard.style.display = 'block';
        document.getElementById('verificationStatusText').textContent = '🤖 Robotic Scanning In Progress...';
        document.getElementById('verificationStatusIcon').textContent = '⚡';
        
        const checklistKeys = ['3d', 'terrain', 'building', 'orthophoto'];
        checklistKeys.forEach(k => {
            const el = document.getElementById('check_' + k);
            el.style.borderLeftColor = '#3b82f6';
            el.querySelector('.check-msg').textContent = 'Verifying...';
            el.querySelector('.check-msg').style.color = 'var(--text-dim)';
            el.querySelector('.check-badge').textContent = 'SCANNING';
            el.querySelector('.check-badge').style.background = 'rgba(59, 130, 246, 0.1)';
            el.querySelector('.check-badge').style.color = '#3b82f6';
        });

        try {
            const response = await fetch('{{ route("user.register_model.verify_sftp") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ sftp_host: host, sftp_port: port, sftp_username: username, sftp_password: password, sftp_path: path })
            });

            const data = await response.json();

            if (response.status === 200 || data.results) {
                updateChecklistItem('3d', data.results['3d_tileset']);
                updateChecklistItem('terrain', data.results['terrain']);
                updateChecklistItem('building', data.results['building']);
                updateChecklistItem('orthophoto', data.results['orthophoto']);

                const feedback = document.getElementById('verificationFeedback');
                if (data.success) {
                    document.getElementById('verificationStatusText').textContent = '✅ SFTP Verification Successful!';
                    document.getElementById('verificationStatusIcon').textContent = '🎉';
                    feedback.textContent = 'All mandatory naming, file format, and size rules have been successfully verified on the server! You are authorized to register this model.';
                    feedback.style.color = '#10b981';
                    
                    isFileTransferVerified = true;
                    btnSubmit.disabled = false;
                    btnSubmit.style.opacity = '1';
                    btnSubmit.style.cursor = 'pointer';
                } else {
                    document.getElementById('verificationStatusText').textContent = '❌ Verification Failed';
                    document.getElementById('verificationStatusIcon').textContent = '⚠️';
                    feedback.textContent = data.message || data.error || 'One or more required validation checks failed. Please inspect the list and update the files in your folder.';
                    feedback.style.color = '#ef4444';
                    
                    isFileTransferVerified = false;
                    btnSubmit.disabled = true;
                    btnSubmit.style.opacity = '0.5';
                    btnSubmit.style.cursor = 'not-allowed';
                }
            } else {
                throw new Error(data.error || 'Server error occurred during scan.');
            }
        } catch (error) {
            document.getElementById('verificationStatusText').textContent = '❌ Scan Error';
            document.getElementById('verificationStatusIcon').textContent = '🚨';
            document.getElementById('verificationFeedback').textContent = error.message || 'Failed to connect to the SFTP server. Check your credentials.';
            document.getElementById('verificationFeedback').style.color = '#ef4444';

            isFileTransferVerified = false;
            checklistKeys.forEach(k => {
                const el = document.getElementById('check_' + k);
                el.style.borderLeftColor = '#ef4444';
                el.querySelector('.check-msg').textContent = 'Scan aborted due to connection error.';
                el.querySelector('.check-msg').style.color = '#ef4444';
                el.querySelector('.check-badge').textContent = 'FAILED';
                el.querySelector('.check-badge').style.background = 'rgba(239, 68, 68, 0.1)';
                el.querySelector('.check-badge').style.color = '#ef4444';
            });
            
            btnSubmit.disabled = true;
            btnSubmit.style.opacity = '0.5';
            btnSubmit.style.cursor = 'not-allowed';
        } finally {
            btnVerify.disabled = false;
            verifyText.textContent = 'Verify SFTP Server';
            verifySpinner.style.display = 'none';
        }
    }

    function updateChecklistItem(key, result) {
        const el = document.getElementById('check_' + key);
        const msgEl = el.querySelector('.check-msg');
        const badgeEl = el.querySelector('.check-badge');

        if (!result) return;

        if (result.status === 'success') {
            el.style.borderLeftColor = '#10b981';
            msgEl.textContent = result.message;
            msgEl.style.color = '#10b981';
            badgeEl.textContent = 'PASSED';
            badgeEl.style.background = 'rgba(16, 185, 129, 0.1)';
            badgeEl.style.color = '#10b981';
        } else if (result.status === 'error') {
            el.style.borderLeftColor = '#ef4444';
            msgEl.textContent = result.message;
            msgEl.style.color = '#ef4444';
            badgeEl.textContent = 'FAILED';
            badgeEl.style.background = 'rgba(239, 68, 68, 0.1)';
            badgeEl.style.color = '#ef4444';
        } else if (result.status === 'warning') {
            el.style.borderLeftColor = '#eab308';
            msgEl.textContent = result.message;
            msgEl.style.color = '#eab308';
            badgeEl.textContent = 'OPTIONAL';
            badgeEl.style.background = 'rgba(234, 179, 8, 0.1)';
            badgeEl.style.color = '#eab308';
        }
    }
</script>
@endsection
