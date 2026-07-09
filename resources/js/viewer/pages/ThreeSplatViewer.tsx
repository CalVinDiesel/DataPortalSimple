import React, { useState, useEffect, Suspense, useRef } from 'react';
import { createRoot } from 'react-dom/client';
import { Canvas, useThree } from '@react-three/fiber';
import { OrbitControls, Bounds } from '@react-three/drei';
import * as GaussianSplats3D from '@mkkellogg/gaussian-splats-3d';
import * as THREE from 'three';

function SplatMesh({ url, controlsRef }: { url: string, controlsRef: any }) {
    const [viewerObj, setViewerObj] = useState<any>(null);
    const { gl, camera } = useThree();

    useEffect(() => {
        let isMounted = true;
        const viewer = new GaussianSplats3D.DropInViewer({
            sphericalHarmonicsDegree: 0,
            halfPrecisionVideoTextures: false,
            ignoreOutlierPoints: true,
            sharedMemoryForWorkers: false, // Prevent Chrome crash
            cameraUp: [0, 0, 1] // Z-up for photogrammetry
        });

        const initSplat = async () => {
            try {
                let actualUrl = url;
                let isFolderWise = false;
                
                // If the URL is tileset.json (from the mega-pipeline), we must convert it to a PLY URL
                if (actualUrl.includes('tileset.json')) {
                    const manifestUrl = actualUrl.replace('/tileset_merged/tileset.json', '/manifest.json');
                    const mergedUrl = actualUrl.replace('/tileset_merged/tileset.json', '/merged_upload.ply');
                    
                    try {
                        const checkManifest = await fetch(manifestUrl, { method: 'HEAD' });
                        if (checkManifest.ok) {
                            actualUrl = manifestUrl;
                        } else {
                            actualUrl = mergedUrl;
                        }
                    } catch (e) {
                        actualUrl = mergedUrl;
                    }
                }

                if (actualUrl.endsWith('manifest.json')) {
                    const baseUrl = actualUrl.replace('manifest.json', '');
                    
                    // NEW HYBRID LOGIC: 
                    // First, check if the global `merged_upload.ply` exists. 
                    // If it does, load that ONE giant file so we get perfectly sharp edges!
                    const globalMergedUrl = actualUrl.replace('manifest.json', 'merged_upload.ply');
                    try {
                        const checkRes = await fetch(globalMergedUrl, { method: 'HEAD' });
                        if (checkRes.ok) {
                            console.log(`[PLY Viewer] Found global merged file! Loading ${globalMergedUrl} for perfect sharpness.`);
                            await viewer.addSplatScene(globalMergedUrl, { showLoadingUI: true });
                            if (isMounted) setViewerObj(viewer);
                            return; // Stop here, we don't need to load the individual folders!
                        }
                    } catch(err) {
                        console.log("[PLY Viewer] Global merged file not found, falling back to folder-wise loading.");
                    }

                    const manifestRes = await fetch(actualUrl);
                    const manifest = await manifestRes.json();
                    
                    if (manifest.type === 'folder_wise_ply' && manifest.folders) {
                        isFolderWise = true;
                        for (const folder of manifest.folders) {
                            try {
                                const metaRes = await fetch(`${baseUrl}${folder}/metadata.xml`);
                                if (!metaRes.ok) continue;
                                const metaText = await metaRes.text();
                                
                                // Quick check if the glued file exists (created by merge_tile_lods.js)
                                const gluedPath = `${baseUrl}${folder}/merged_tile.ply`;
                                try {
                                    const checkRes = await fetch(gluedPath, { method: 'HEAD' });
                                    if (checkRes.ok) {
                                        console.log(`[PLY Viewer] Loading Perfectly Glued Tile: ${gluedPath}`);
                                        await viewer.addSplatScene(gluedPath, {
                                            showLoadingUI: true
                                        });
                                    } else {
                                        throw new Error("Glued file not ready");
                                    }
                                } catch(err) {
                                    console.warn(`[PLY Viewer] Glued tile missing, falling back to point_cloud.xply...`);
                                    await viewer.addSplatScene(`${baseUrl}${folder}/point_cloud.xply`, {
                                        showLoadingUI: true
                                    });
                                }
                            } catch (e) {
                                console.warn(`Could not process metadata for ${folder}`, e);
                            }
                        }
                    }
                } else {
                    await viewer.addSplatScene(actualUrl, {
                        showLoadingUI: true
                    });
                }
                
                if (isMounted) {
                    setViewerObj(viewer);
                }
            } catch (err) {
                console.error("Failed to load Splat Mesh:", err);
            }
        };

        initSplat();

        return () => {
            isMounted = false;
        };
    }, [url]);

    // Manual Raycaster using the engine's custom WebGL Raycaster!
    useEffect(() => {
        if (!viewerObj) return;
        
        const doRaycast = (clientX: number, clientY: number) => {
            const rect = gl.domElement.getBoundingClientRect();
            const mousePosition = new THREE.Vector2(clientX - rect.left, clientY - rect.top);
            const renderDimensions = new THREE.Vector2(rect.width, rect.height);
            
            const outHits: any[] = [];
            const viewerEngine = viewerObj.viewer; 
            if (viewerEngine && viewerEngine.raycaster && viewerEngine.splatMesh) {
                viewerEngine.raycaster.setFromCameraAndScreenPosition(camera, mousePosition, renderDimensions);
                viewerEngine.raycaster.intersectSplatMesh(viewerEngine.splatMesh, outHits);
                
                if (outHits.length > 0) {
                    const hitPoint = outHits[0].origin;
                    if (controlsRef.current) {
                        controlsRef.current.target.copy(hitPoint);
                        controlsRef.current.update();
                    }
                    return true;
                }
            }
            return false;
        };

        const handleDblClick = (e: MouseEvent) => {
            if (doRaycast(e.clientX, e.clientY)) {
                console.log("Double Click: Pivot teleported to surface!");
            }
        };

        // Automatically fire lasers to find the physical model!
        // We shoot a grid of lasers because the dead-center of the screen might be pointing at empty sky.
        let attempts = 0;
        const autoCenterInterval = setInterval(() => {
            attempts++;
            const rect = gl.domElement.getBoundingClientRect();
            
            // A pattern of points to test, starting from center and spiraling out
            const testPoints = [
                [0.5, 0.5],   // Dead center
                [0.5, 0.6],   // Slightly lower (common if model is below horizon)
                [0.5, 0.7],   // Lower
                [0.5, 0.8],   // Very low
                [0.4, 0.6],   // Bottom left
                [0.6, 0.6],   // Bottom right
                [0.5, 0.4],   // Slightly up
                [0.4, 0.5],   // Left
                [0.6, 0.5],   // Right
            ];

            let hit = false;
            for (const [px, py] of testPoints) {
                const testX = rect.left + rect.width * px;
                const testY = rect.top + rect.height * py;
                
                if (doRaycast(testX, testY)) {
                    console.log(`Auto-Center: Pivot snapped to model surface on attempt ${attempts} at screen grid [${px}, ${py}]!`);
                    hit = true;
                    clearInterval(autoCenterInterval);
                    break;
                }
            }

            if (!hit && attempts >= 10) {
                console.log("Auto-Center: Failed to find model surface after 10 attempts.");
                clearInterval(autoCenterInterval);
            }
        }, 500); 

        gl.domElement.addEventListener('dblclick', handleDblClick);
        return () => {
            gl.domElement.removeEventListener('dblclick', handleDblClick);
            clearInterval(autoCenterInterval);
        };
    }, [viewerObj, gl, camera, controlsRef]);

    return viewerObj ? <primitive object={viewerObj} /> : null;
}

const ThreeSplatViewer = () => {
    const [modelUrl, setModelUrl] = useState<string | null>(null);
    const [title, setTitle] = useState<string>('Three.js Native Inspector');
    const [error, setError] = useState<string | null>(null);
    const controlsRef = useRef<any>(null);

    useEffect(() => {
        const loadModel = async () => {
            try {
                const params = new URLSearchParams(window.location.search);
                let modelPath = params.get('tileset_url');
                const titleParam = params.get('title');

                if (titleParam) {
                    setTitle(`${titleParam} - Three.js Native Inspector`);
                }

                // First check if a direct ply_url was provided
                modelPath = params.get('ply_url') || modelPath;
                
                // Fallback to tileset_url with legacy guessing
                if (!modelPath) {
                    modelPath = params.get('tileset_url');
                    if (modelPath && modelPath.endsWith('tileset.json')) {
                         const basePath = modelPath.replace(/tileset\.json$/, '').replace('/tileset_merged/', '/').replace('/Data/', '/');
                         const manifestPath = basePath + 'manifest.json';
                         
                         try {
                             const res = await fetch(manifestPath, { method: 'HEAD' });
                             if (res.ok) {
                                 modelPath = manifestPath;
                             } else {
                                 modelPath = basePath + 'merged_upload.ply';
                             }
                         } catch (e) {
                             modelPath = basePath + 'merged_upload.ply';
                         }
                    }
                }

                if (!modelPath) {
                    throw new Error("No model path or PLY file could be found.");
                }

                const response = await fetch(modelPath, { method: 'HEAD' });
                if (!response.ok) {
                    throw new Error(`The raw data does not exist on the server (HTTP ${response.status}).`);
                }

                setModelUrl(modelPath);

            } catch (err: any) {
                console.error("Three.js Viewer Setup Error:", err);
                setError(err.message || "Failed to initialize the viewer.");
            }
        };

        loadModel();
    }, []);

    const handleClose = () => {
        window.close();
    };

    return (
        <div className="w-full h-screen bg-black relative">
            <div className="absolute top-0 left-0 w-full p-4 flex justify-between items-center z-50 bg-gradient-to-b from-black/80 to-transparent">
                <button 
                    onClick={handleClose}
                    className="flex items-center text-white/80 hover:text-white transition-colors text-sm font-medium"
                >
                    <svg className="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                    Close Viewer
                </button>
                <div className="text-white font-semibold tracking-wide text-sm">{title}</div>
            </div>

            {error && (
                <div className="absolute inset-0 flex items-center justify-center z-50 bg-black/90 p-4">
                    <div className="bg-red-900/40 border border-red-500 rounded-lg p-6 max-w-lg w-full shadow-2xl">
                        <div className="flex items-center mb-3">
                            <h3 className="text-red-400 font-bold text-lg">Error Loading Three.js Model</h3>
                        </div>
                        <p className="text-red-200/90 text-sm leading-relaxed mb-4">{error}</p>
                        <button onClick={handleClose} className="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded transition-colors text-sm font-medium w-full">
                            Return to Dashboard
                        </button>
                    </div>
                </div>
            )}

            {!error && modelUrl && (
                <>
                    <Canvas camera={{ position: [238, 307, 41], up: [0, 0, 1], fov: 45 }}>
                        <color attach="background" args={['#000000']} />
                        <Suspense fallback={null}>
                            <Bounds fit clip observe margin={1.2}>
                                <SplatMesh url={modelUrl} controlsRef={controlsRef} />
                            </Bounds>
                        </Suspense>
                        <OrbitControls ref={controlsRef} makeDefault target={[238, 297, 31]} />
                    </Canvas>
                    
                    {/* Subtle UI Hint for Double-Click Pivot */}
                    <div className="absolute bottom-8 left-1/2 -translate-x-1/2 z-50 bg-black/40 backdrop-blur-md border border-white/10 px-5 py-2.5 rounded-full pointer-events-none shadow-2xl transition-opacity duration-1000">
                        <span className="text-white/90 text-xs font-medium tracking-wide flex items-center">
                            <svg className="w-4 h-4 mr-2 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                            </svg>
                            Tip: Double-click anywhere on the model to set a new rotation pivot
                        </span>
                    </div>
                </>
            )}
        </div>
    );
};

const rootElement = document.getElementById('viewer-root');
if (rootElement) {
    createRoot(rootElement).render(<ThreeSplatViewer />);
}

export default ThreeSplatViewer;
