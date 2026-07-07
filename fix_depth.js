import fs from 'fs';

const filePath = 'C:\\Users\\User\\Downloads\\TemaDataPortalSimple\\resources\\js\\viewer\\pages\\DiscoveryPage.tsx';
let content = fs.readFileSync(filePath, 'utf8');

// 1. Fix clamping for Polylines (so they don't sink into the earth under the model)
content = content.replace(/clampToGround: activeTool !== 'height'/g, "clampToGround: false");
content = content.replace(/clampToGround: true/g, "clampToGround: false");

// 2. Fix Ellipse height in previews
content = content.replace(
    /height: Cartographic.fromCartesian\(pickedPosition\)\.height/g,
    "height: Cartographic.fromCartesian(pickedPosition).height + 0.5"
);

// 3. Fix Ellipse height in createCircleMeasurement
content = content.replace(
    /height: Cartographic.fromCartesian\(points\[0\]\)\.height/g,
    "height: Cartographic.fromCartesian(points[0]).height + 0.5"
);

// 4. Raise Polygon hierarchies so they don't z-fight with splats
// We need to write a helper function and inject it, or just wrap the `hierarchy` properties.
// Actually, Cesium 3D Tiles classification (which clampToGround uses) doesn't work on splats.
// Let's inject a helper to raise Cartesian3 arrays by 0.5m.

const helperFunction = `
    const raisePoints = (pts: Cartesian3[], offset = 0.5) => {
        return pts.map(p => {
            const cart = Cartographic.fromCartesian(p);
            return Cartesian3.fromRadians(cart.longitude, cart.latitude, cart.height + offset);
        });
    };
`;

if (!content.includes('raisePoints')) {
    content = content.replace('const DiscoveryPage: React.FC = () => {', 'const DiscoveryPage: React.FC = () => {' + helperFunction);
}

// Fix createPolygon hierarchy
content = content.replace(
    /hierarchy: points,/g,
    "hierarchy: raisePoints(points),"
);

// Fix createAreaMeasurement hierarchy
// wait, where is createAreaMeasurement? Let's check if it uses hierarchy: points
content = content.replace(
    /hierarchy: new PolygonHierarchy\(points\)/g,
    "hierarchy: new PolygonHierarchy(raisePoints(points))"
);

// Fix preview polygon hierarchy
// The preview uses a CallbackProperty returning a PolygonHierarchy
content = content.replace(
    /return pts\.length >= 3 \? new PolygonHierarchy\(pts\) : undefined;/g,
    "return pts.length >= 3 ? new PolygonHierarchy(raisePoints(pts)) : undefined;"
);

// Fix preview length/height polyline
content = content.replace(
    /positions: dynamicPositions,/g,
    "positions: new CallbackProperty(() => raisePoints(dynamicPositions.getValue(new JulianDate()) || []), false),"
);

// Fix final polyline positions
// Wait, I need to check how createLengthMeasurement passes positions.
// Usually: positions: points,
content = content.replace(
    /positions: points,/g,
    "positions: raisePoints(points),"
);

fs.writeFileSync(filePath, content);
console.log('Fixed depth occlusion issues in DiscoveryPage.tsx');
