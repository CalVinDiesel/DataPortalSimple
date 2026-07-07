const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

// This script replaces your old .ply -> .splat pipeline.
// It uses the 3dgs-ply-3dtiles-converter from the GitHub repo to convert a PLY into a Cesium 3D Tileset.

const inputPlyFile = process.argv[2];
const outputDir = process.argv[3] || './Cesium_3DTiles_Output';

if (!inputPlyFile) {
    console.error("Usage: node convert_pipeline.js <path_to_source.ply> [output_directory]");
    console.error("Note: Please provide your ORIGINAL untiled .ply file, not the already-tiled Data folder.");
    process.exit(1);
}

if (!fs.existsSync(inputPlyFile)) {
    console.error(`Input file not found: ${inputPlyFile}`);
    process.exit(1);
}

console.log(`Starting automated conversion for: ${inputPlyFile}`);
console.log(`Output will be saved to: ${outputDir}`);

try {
    // Check if converter is installed
    console.log("Checking for 3dgs-ply-3dtiles-converter...");
    try {
        execSync('npx 3dgs-ply-3dtiles-converter --help', { stdio: 'ignore' });
    } catch (e) {
        console.log("Installing 3dgs-ply-3dtiles-converter globally...");
        execSync('npm install -g 3dgs-ply-3dtiles-converter', { stdio: 'inherit' });
    }

    // Run the conversion
    console.log("Running conversion to 3D Tiles (this may take a while depending on file size)...");
    
    // Using graphdeco convention since standard photogrammetry/Postshot PLY files use it
    const cmd = `npx 3dgs-ply-3dtiles-converter "${inputPlyFile}" "${outputDir}" --input-convention graphdeco`;
    execSync(cmd, { stdio: 'inherit' });

    console.log("\n=======================================================");
    console.log("SUCCESS! Your .ply file has been converted to 3D Tiles.");
    console.log(`You can find your tileset.json and .glb files in: ${outputDir}`);
    console.log("=======================================================\n");
    console.log("NEXT STEPS FOR CESIUM ION:");
    console.log(`1. Zip the entire '${outputDir}' folder.`);
    console.log("2. Go to your Cesium ion dashboard (ion.cesium.com).");
    console.log("3. Click 'Upload' and upload the zip file.");
    console.log("4. Once processed, use Cesium3DTileset.fromIonAssetId() in your viewer code.");

} catch (error) {
    console.error("An error occurred during conversion:", error.message);
    process.exit(1);
}
