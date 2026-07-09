import fs from 'fs';
import path from 'path';

const inputDir = process.argv[2]; // e.g. public/models/36

if (!inputDir) {
    console.error('Usage: node merge_tile_lods.js <input_dir>');
    process.exit(1);
}

try {
    const manifestPath = path.join(inputDir, 'manifest.json');
    if (!fs.existsSync(manifestPath)) {
        console.log('No manifest.json found. Nothing to merge folder-wise.');
        process.exit(0);
    }

    const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
    if (manifest.type !== 'folder_wise_ply') {
        process.exit(0);
    }

    console.log(`Starting Folder-Wise LOD Merge for ${manifest.folders.length} folders...`);

    for (const folder of manifest.folders) {
        const folderPath = path.join(inputDir, folder);
        if (!fs.existsSync(folderPath)) continue;

        // Find all .ply files in this specific folder
        const files = fs.readdirSync(folderPath);
        const plyFiles = [];
        
        for (const file of files) {
            // Include LOD files, but ignore our output file and the tiny .xply preview
            if (file.endsWith('.ply') && file !== 'merged_tile.ply' && !file.endsWith('.xply')) {
                plyFiles.push(path.join(folderPath, file));
            }
        }

        if (plyFiles.length === 0) continue;

        const mergedFilePath = path.join(folderPath, 'merged_tile.ply');
        console.log(`Merging ${plyFiles.length} PLY files in ${folder} -> merged_tile.ply`);

        let totalVertices = 0;
        let headerProperties = [];
        let isFirstFile = true;

        for (const filePath of plyFiles) {
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
        for (const filePath of plyFiles) {
            const buffer = fs.readFileSync(filePath);
            const headerEnd = buffer.indexOf('\nend_header\n') + 12;
            fs.writeSync(mergedFd, buffer.subarray(headerEnd));
        }
        fs.closeSync(mergedFd);

        // Cleanup fragments (L17-L21) to save disk space
        for (const filePath of plyFiles) {
            fs.unlinkSync(filePath);
        }
    }

    console.log('Folder-Wise Merge Complete!');

} catch (err) {
    console.error('Error in merge_tile_lods:', err);
    process.exit(1);
}
