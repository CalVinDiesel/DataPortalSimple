import fs from 'fs';
import path from 'path';

const dir = 'C:\\Users\\User\\Downloads\\SLABI_3dgs_3dtiles\\SLABI_3dgs_3dtiles\\Data';
const outFile = 'C:\\Users\\User\\Downloads\\TemaDataPortalSimple\\merged_model.ply';

function getAllFiles(dirPath, arrayOfFiles) {
  const files = fs.readdirSync(dirPath);
  arrayOfFiles = arrayOfFiles || [];
  files.forEach(function(file) {
    if (fs.statSync(dirPath + "/" + file).isDirectory()) {
      arrayOfFiles = getAllFiles(dirPath + "/" + file, arrayOfFiles);
    } else {
      if (file.endsWith('.ply')) {
        arrayOfFiles.push(path.join(dirPath, "/", file));
      }
    }
  });
  return arrayOfFiles;
}

async function main() {
    console.log('Scanning directory for .ply files...');
    const files = getAllFiles(dir);
    console.log(`Found ${files.length} .ply files.`);
    
    let totalVertices = 0;
    const fileInfos = [];
    let headerTemplate = null;

    for (let i = 0; i < files.length; i++) {
        const file = files[i];
        
        // Read header
        const fd = fs.openSync(file, 'r');
        const buffer = Buffer.alloc(4096); // Assuming header is < 4096 bytes
        let bytesRead = 0;
        try {
            bytesRead = fs.readSync(fd, buffer, 0, 4096, 0);
        } catch(e) {
            console.error(`Error reading ${file}`, e);
            fs.closeSync(fd);
            continue;
        }
        
        // Find end_header
        const headerStr = buffer.toString('utf-8', 0, bytesRead);
        const endHeaderMatch = headerStr.match(/end_header\r?\n/);
        
        if (!endHeaderMatch) {
            console.error(`Could not find end_header in ${file}`);
            fs.closeSync(fd);
            continue;
        }
        
        const headerEndOffset = endHeaderMatch.index + endHeaderMatch[0].length;
        
        // Find vertex count
        const vertexMatch = headerStr.match(/element vertex (\d+)/);
        if (!vertexMatch) {
            console.error(`Could not find vertex count in ${file}`);
            fs.closeSync(fd);
            continue;
        }
        
        const vertexCount = parseInt(vertexMatch[1], 10);
        totalVertices += vertexCount;
        
        if (!headerTemplate) {
            // Save the first header to use as our template
            headerTemplate = headerStr.substring(0, headerEndOffset).replace(/element vertex \d+/, 'element vertex {VERTEX_COUNT}');
        }
        
        fileInfos.push({
            path: file,
            offset: headerEndOffset
        });
        
        fs.closeSync(fd);
        
        if (i % 1000 === 0 && i > 0) console.log(`Scanned ${i} files...`);
    }
    
    console.log(`Total vertices: ${totalVertices}`);
    console.log(`Writing to ${outFile}...`);
    
    const finalHeader = headerTemplate.replace('{VERTEX_COUNT}', totalVertices.toString());
    
    const outFd = fs.openSync(outFile, 'w');
    fs.writeSync(outFd, finalHeader);
    
    let processed = 0;
    for (const info of fileInfos) {
        const inFd = fs.openSync(info.path, 'r');
        const stats = fs.statSync(info.path);
        const payloadSize = stats.size - info.offset;
        
        if (payloadSize > 0) {
            // Stream the payload over
            let pos = info.offset;
            let bytesRemaining = payloadSize;
            const chunkBuffer = Buffer.alloc(1024 * 1024 * 5); // 5MB buffer
            
            while(bytesRemaining > 0) {
                const toRead = Math.min(bytesRemaining, chunkBuffer.length);
                const r = fs.readSync(inFd, chunkBuffer, 0, toRead, pos);
                fs.writeSync(outFd, chunkBuffer, 0, r);
                pos += r;
                bytesRemaining -= r;
            }
        }
        fs.closeSync(inFd);
        processed++;
        if (processed % 1000 === 0) console.log(`Merged ${processed}/${fileInfos.length} files...`);
    }
    
    fs.closeSync(outFd);
    console.log('Merge complete! Your single .ply file is ready.');
}

main().catch(console.error);
