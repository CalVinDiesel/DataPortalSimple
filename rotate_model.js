import fs from 'fs';

const tilesetPath = 'C:\\Users\\User\\Downloads\\TemaDataPortalSimple\\public\\models\\SLABI_working\\tileset.json';
const tileset = JSON.parse(fs.readFileSync(tilesetPath, 'utf8'));

// The SLABI model is Y-up (its height is along the Y axis).
// Penampang is Z-up (standard ENU).
// We need to map the SLABI model's Local Y axis to the Global Up axis.
// Local X -> Global East  [0, 1, 0, 0]
// Local Y -> Global Up    [1, 0, 0, 0]
// Local Z -> Global South [0, 0, -1, 0] (to maintain right-handedness)

tileset.root.transform = [
    0, 1, 0, 0,
    1, 0, 0, 0,
    0, 0, -1, 0,
    6378137, 0, 0, 1
];

fs.writeFileSync(tilesetPath, JSON.stringify(tileset));
console.log('Model successfully rotated to Y-up!');
