import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
const inputDir = process.argv[2]; // directory containing all .ply fragments
const outputTilesetDir = process.argv[3]; // destination for tileset.json

if (!inputDir || !outputTilesetDir) {
    console.error('Usage: node process_splat_upload.js <input_dir> <output_tileset_dir>');
    process.exit(1);
}

try {
    // 1. Merge all .ply files (Recursively find them)
    function getAllPlyFiles(dir, fileList = []) {
        const files = fs.readdirSync(dir);
        for (const file of files) {
            const filePath = path.join(dir, file);
            if (fs.statSync(filePath).isDirectory()) {
                getAllPlyFiles(filePath, fileList);
            } else if (file.endsWith('.ply') && file !== 'merged_upload.ply' && file !== 'merged_tile.ply') {
                fileList.push(filePath);
            }
        }
        return fileList;
    }

    const files = getAllPlyFiles(inputDir);
    if (files.length === 0) {
        throw new Error('No .ply files found in input directory or subdirectories');
    }

    const mergedFilePath = path.join(inputDir, 'merged_upload.ply');
    console.log(`Found ${files.length} .ply fragments. Merging to ${mergedFilePath}...`);

    let totalVertices = 0;
    let headerProperties = [];
    let isFirstFile = true;
    
    // Calculate total vertices
    for (const file of files) {
        const filePath = file; // file is already absolute from getAllPlyFiles
        const buffer = fs.readFileSync(filePath);
        
        const headerEnd = buffer.indexOf('\nend_header\n');
        if (headerEnd === -1) continue;
        
        const headerText = buffer.subarray(0, headerEnd).toString('utf-8');
        const lines = headerText.split('\n');
        
        for (const line of lines) {
            if (line.startsWith('element vertex ')) {
                totalVertices += parseInt(line.split(' ')[2]);
            }
        }
        
        if (isFirstFile) {
            for (const line of lines) {
                if (line.startsWith('property ')) {
                    headerProperties.push(line);
                }
            }
            isFirstFile = false;
        }
    }

    // Write header
    const mergedFd = fs.openSync(mergedFilePath, 'w');
    const newHeader = [
        'ply',
        'format binary_little_endian 1.0',
        `element vertex ${totalVertices}`
    ].concat(headerProperties).concat([
        'end_header'
    ]).join('\n') + '\n';
    
    fs.writeSync(mergedFd, newHeader);

    // Append binary data
    for (const file of files) {
        const filePath = file;
        const buffer = fs.readFileSync(filePath);
        const headerEnd = buffer.indexOf('\nend_header\n') + 12;
        fs.writeSync(mergedFd, buffer.subarray(headerEnd));
    }
    fs.closeSync(mergedFd);
    
    console.log(`Merged successfully. Total vertices: ${totalVertices}`);

    // 2. Convert to 3D Tiles
    console.log(`Converting ${mergedFilePath} to 3D Tiles at ${outputTilesetDir}...`);
    execSync(`npx 3dgs-ply-3dtiles-converter "${mergedFilePath}" "${outputTilesetDir}" --no-open-inspector --coordinate "[0,0,0]"`, {
        stdio: 'inherit'
    });

    // 3. Rotate Y-Up to Z-Up
    const tilesetPath = path.join(outputTilesetDir, 'tileset.json');
    if (fs.existsSync(tilesetPath)) {
        console.log(`Flipping orientation from Y-Up to Z-Up in ${tilesetPath}...`);
        const tileset = JSON.parse(fs.readFileSync(tilesetPath, 'utf8'));
        
        // Transform matrix to rotate 90 degrees around X-axis
        tileset.root.transform = [
            0, 1, 0, 0,
            1, 0, 0, 0,
            0, 0, -1, 0,
            6378137, 0, 0, 1
        ];
        
        fs.writeFileSync(tilesetPath, JSON.stringify(tileset, null, 2));
        console.log('Rotation applied successfully.');
    }

    // 4. Folder-Wise Gluing & Cleanup
    console.log('Checking for folder-wise manifest...');
    const manifestPath = path.join(inputDir, 'manifest.json');
    
    if (fs.existsSync(manifestPath)) {
        console.log('Manifest found! Performing Folder-Wise Gluing...');
        const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
        
        if (manifest.type === 'folder_wise_ply') {
            for (const folder of manifest.folders) {
                const folderPath = path.join(inputDir, folder);
                if (!fs.existsSync(folderPath)) continue;

                const folderFiles = fs.readdirSync(folderPath);
                const plyFiles = folderFiles
                    .filter(f => f.endsWith('.ply') && f !== 'merged_tile.ply' && !f.endsWith('.xply'))
                    .map(f => path.join(folderPath, f));

                if (plyFiles.length === 0) continue;

                // We no longer generate `merged_tile.ply` inside each folder!
                // 1. It saves massive amounts of disk space.
                // 2. The folders remain purely RAW data (just like Project 36).
                // 3. The Three.js Viewer uses `merged_upload.ply` instead.
            }
        }
    } else {
        console.log('No manifest. Skipping cleanup to preserve raw folders...');
        // for (const file of files) {
        //     if (fs.existsSync(file)) {
        //         fs.unlinkSync(file);
        //     }
        // }
    }
    
    // We will NO LONGER delete the global merged file! 
    // The Three.js viewer needs this single massive file to render perfectly sharp edges.
    // if (fs.existsSync(mergedFilePath)) {
    //     fs.unlinkSync(mergedFilePath); 
    // }
    
    console.log('Mega-Pipeline completed successfully!');

} catch (err) {
    console.error('Error in pipeline:', err);
    process.exit(1);
}
