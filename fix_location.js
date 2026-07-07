import fs from 'fs';

const tilesetPath = 'C:\\Users\\User\\Downloads\\TemaDataPortalSimple\\public\\models\\SLABI_working\\tileset.json';
const tileset = JSON.parse(fs.readFileSync(tilesetPath, 'utf8'));

tileset.root.transform = [
    0, 1, 0, 0, 
    0, 0, 1, 0, 
    1, 0, 0, 0, 
    6378137, 0, 0, 1
];

fs.writeFileSync(tilesetPath, JSON.stringify(tileset));
console.log('Added geographical transform to tileset.json successfully!');
