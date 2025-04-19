<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Map Interaktif GIS') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <!-- Tools Panel -->
                    <div class="mb-4 flex space-x-2">
                        <button id="marker-tool" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 active">Marker</button>
                        <button id="line-tool" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-blue-700">Garis</button>
                        <button id="polygon-tool" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-blue-700">Polygon</button>
                        <button id="edit-tool" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-yellow-700">Edit</button>
                        <button id="clear-drawing" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 ml-auto">Batal Gambar</button>
                    </div>

                    <div id="map" style="height: 80vh; width: 100%;"></div>

                    <meta name="csrf-token" content="{{ csrf_token() }}">

                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

                    <!-- Form untuk Marker -->
                    <div id="marker-form" class="hidden fixed bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg z-50 w-96" style="z-index: 9999;">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Tambah Marker Baru</h3>
                        <form id="save-marker-form">
                            <div class="mb-4">
                                <label for="marker-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Marker</label>
                                <input type="text" id="marker-name" name="name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-black" required />
                            </div>
                            <div class="mb-4">
                                <label for="marker-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deskripsi</label>
                                <textarea id="marker-description" name="description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-black"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="button" id="cancel-marker" class="mr-2 px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                                    Batal
                                </button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Form untuk Garis dan Polygon -->
                    <div id="feature-form" class="hidden fixed bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg z-50 w-96" style="z-index: 9999;">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4" id="feature-form-title">Tambah Feature Baru</h3>
                        <form id="save-feature-form">
                            <div class="mb-4">
                                <label for="feature-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nama Feature</label>
                                <input type="text" id="feature-name" name="name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-black" required />
                            </div>
                            <input type="hidden" id="feature-type" name="type" value="line">
                            <div class="flex justify-end">
                                <button type="button" id="cancel-feature" class="mr-2 px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                                    Batal
                                </button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    Simpan
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Form untuk Update Feature -->
                    <div id="update-feature-form" class="hidden fixed bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg z-50 w-96" style="z-index: 9999;">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Update Feature</h3>
                        <form id="update-feature-form-element">
                            <input type="hidden" id="update-feature-id">
                            <div class="flex justify-end">
                                <button type="button" id="cancel-update" class="mr-2 px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                                    Batal
                                </button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>

                    <script>
                        const map = L.map('map').setView([-8.409518, 115.188919], 10); // Pusat di Bali

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        const markerStore = {}; // Store markers by ID
                        const featureStore = {}; // Store features by ID
                        const vertexStore = {}; // Store vertex markers by feature ID

                        // Variabel untuk mode drawing
                        let currentTool = 'marker'; // Default tool
                        let isDrawing = false;
                        let currentDrawing = null;
                        let currentVertices = [];
                        let currentPoints = [];
                        let editingFeatureId = null;

                        // Style untuk fitur
                        const lineStyle = {
                            color: '#3388ff',
                            weight: 4,
                            opacity: 0.7
                        };

                        const polygonStyle = {
                            color: '#3388ff',
                            fillColor: '#3388ff',
                            fillOpacity: 0.2,
                            weight: 3
                        };

                        // Icon untuk vertex
                        const vertexIcon = L.divIcon({
                            className: 'vertex-icon',
                            iconSize: [10, 10],
                            html: '<div style="background-color: red; width: 10px; height: 10px; border-radius: 50%;"></div>'
                        });

                        // Setup tool buttons
                        document.getElementById('marker-tool').addEventListener('click', () => setActiveTool('marker'));
                        document.getElementById('line-tool').addEventListener('click', () => setActiveTool('line'));
                        document.getElementById('polygon-tool').addEventListener('click', () => setActiveTool('polygon'));
                        document.getElementById('edit-tool').addEventListener('click', () => setActiveTool('edit'));
                        document.getElementById('clear-drawing').addEventListener('click', clearCurrentDrawing);

                        function setActiveTool(tool) {
                            // Hide all forms
                            document.getElementById('marker-form').classList.add('hidden');
                            document.getElementById('feature-form').classList.add('hidden');
                            document.getElementById('update-feature-form').classList.add('hidden');

                            // Reset editing mode if changing from edit
                            if (currentTool === 'edit' && tool !== 'edit') {
                                stopEditing();
                            }

                            currentTool = tool;
                            document.getElementById('marker-tool').classList.remove('bg-blue-600', 'active');
                            document.getElementById('marker-tool').classList.add('bg-gray-600');
                            document.getElementById('line-tool').classList.remove('bg-blue-600', 'active');
                            document.getElementById('line-tool').classList.add('bg-gray-600');
                            document.getElementById('polygon-tool').classList.remove('bg-blue-600', 'active');
                            document.getElementById('polygon-tool').classList.add('bg-gray-600');
                            document.getElementById('edit-tool').classList.remove('bg-blue-600', 'active');
                            document.getElementById('edit-tool').classList.add('bg-gray-600');

                            document.getElementById(`${tool}-tool`).classList.remove('bg-gray-600');
                            document.getElementById(`${tool}-tool`).classList.add('bg-blue-600', 'active');

                            if (currentDrawing && (tool !== 'line' && tool !== 'polygon')) {
                                clearCurrentDrawing();
                            }

                            // Enter edit mode
                            if (tool === 'edit') {
                                // Make all features clickable for editing
                                Object.keys(featureStore).forEach(id => {
                                    const feature = featureStore[id];
                                    if (!feature._editClickHandler) {
                                        feature._editClickHandler = function() {
                                            startEditing(id);
                                        };
                                        feature.on('click', feature._editClickHandler);
                                    }
                                });
                            } else {
                                // Remove edit click handlers
                                Object.keys(featureStore).forEach(id => {
                                    const feature = featureStore[id];
                                    if (feature._editClickHandler) {
                                        feature.off('click', feature._editClickHandler);
                                        delete feature._editClickHandler;
                                    }
                                });
                            }
                        }

                        function clearCurrentDrawing() {
                            if (currentDrawing) {
                                map.removeLayer(currentDrawing);
                                currentDrawing = null;
                            }
                            currentPoints = [];
                            isDrawing = false;

                            // Clear current vertices
                            currentVertices.forEach(vertex => {
                                if (vertex) map.removeLayer(vertex);
                            });
                            currentVertices = [];
                        }

                        function addMarker(marker) {
                            const leafletMarker = L.marker([marker.latitude, marker.longitude])
                                .addTo(map)
                                .bindPopup(getPopupContent(marker));

                            markerStore[marker.id] = leafletMarker;
                        }

                        function getPopupContent(marker) {
                            return `
                                <div>
                                    <strong>${marker.name}</strong><br>
                                    ${marker.description || 'No description'}<br>
                                    <button class="delete-marker-btn" data-id="${marker.id}" style="
                                        background-color: red;
                                        color: white;
                                        border: none;
                                        padding: 5px 10px;
                                        cursor: pointer;
                                        margin-top: 5px;
                                    ">
                                        Delete
                                    </button>
                                </div>
                            `;
                        }

                        function getFeaturePopupContent(feature) {
                            return `
                                <div>
                                    <strong>${feature.name}</strong><br>
                                    Type: ${feature.type}<br>
                                    <button class="delete-feature-btn" data-id="${feature.id}" style="
                                        background-color: red;
                                        color: white;
                                        border: none;
                                        padding: 5px 10px;
                                        cursor: pointer;
                                        margin-top: 5px;
                                    ">
                                        Delete
                                    </button>
                                </div>
                            `;
                        }

                        function addLineFeature(feature) {
                            const latlngs = feature.coordinates.map(coord => [coord.lat, coord.lng]);
                            const polyline = L.polyline(latlngs, lineStyle)
                                .addTo(map)
                                .bindPopup(getFeaturePopupContent(feature));
                            
                            featureStore[feature.id] = polyline;
                            
                            // Add vertex markers but keep them hidden initially
                            addVertexMarkers(feature.id, feature.coordinates, feature.type);
                        }

                        function addPolygonFeature(feature) {
                            const latlngs = feature.coordinates.map(coord => [coord.lat, coord.lng]);
                            const polygon = L.polygon(latlngs, polygonStyle)
                                .addTo(map)
                                .bindPopup(getFeaturePopupContent(feature));
                            
                            featureStore[feature.id] = polygon;
                            
                            // Add vertex markers but keep them hidden initially
                            addVertexMarkers(feature.id, feature.coordinates, feature.type);
                        }

                        function addVertexMarkers(featureId, coordinates, type) {
                            if (!vertexStore[featureId]) {
                                vertexStore[featureId] = [];
                            }

                            // Remove existing vertex markers if any
                            vertexStore[featureId].forEach(vertex => {
                                if (vertex) map.removeLayer(vertex);
                            });
                            vertexStore[featureId] = [];

                            // Add new vertex markers
                            coordinates.forEach((coord, index) => {
                                const vertex = L.marker([coord.lat, coord.lng], {
                                    icon: vertexIcon,
                                    draggable: false,
                                    opacity: 0 // Initially hidden
                                }).addTo(map);
                                
                                vertex.on('dragend', function() {
                                    updateFeatureGeometry(featureId, type);
                                });
                                
                                vertexStore[featureId].push(vertex);
                            });
                        }

                        function startEditing(featureId) {
                            // First stop any current editing
                            stopEditing();
                            
                            editingFeatureId = featureId;
                            
                            // Make vertex markers visible and draggable
                            if (vertexStore[featureId]) {
                                vertexStore[featureId].forEach(vertex => {
                                    vertex.setOpacity(1);
                                    vertex.dragging.enable();
                                });
                            }
                            
                            // Show update form
                            const form = document.getElementById('update-feature-form');
                            document.getElementById('update-feature-id').value = featureId;
                            form.classList.remove('hidden');
                        }

                        function stopEditing() {
                            if (editingFeatureId && vertexStore[editingFeatureId]) {
                                vertexStore[editingFeatureId].forEach(vertex => {
                                    vertex.setOpacity(0);
                                    vertex.dragging.disable();
                                });
                                editingFeatureId = null;
                            }
                            
                            document.getElementById('update-feature-form').classList.add('hidden');
                        }

                        function updateFeatureGeometry(featureId, type) {
                            const vertices = vertexStore[featureId];
                            const coordinates = vertices.map(vertex => {
                                const latlng = vertex.getLatLng();
                                return {lat: latlng.lat, lng: latlng.lng};
                            });
                            
                            const feature = featureStore[featureId];
                            const latlngs = coordinates.map(coord => [coord.lat, coord.lng]);
                            
                            feature.setLatLngs(latlngs);

                            // Update coordinates in database
                            return coordinates;
                        }

                        function attachDeleteHandlers() {
                            map.eachLayer(layer => {
                                if (layer instanceof L.Marker) {
                                    layer.on('popupopen', () => {
                                        const btn = document.querySelector('.delete-marker-btn');
                                        if (btn) {
                                            btn.addEventListener('click', function () {
                                                const id = this.getAttribute('data-id');
                                                if (confirm("Anda yakin akan menghapus marker ini?")) {
                                                    fetch(`/delMarkers/${id}`, {
                                                        method: 'DELETE',
                                                        headers: {
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                                        }
                                                    }).then(res => {
                                                        if (res.ok) {
                                                            map.removeLayer(markerStore[id]);
                                                            delete markerStore[id];
                                                        } else {
                                                            alert("Gagal menghapus marker, Coba Lagi.");
                                                        }
                                                    }).catch(err => {
                                                        console.error(err);
                                                    });
                                                }
                                            });
                                        }
                                    });
                                } else if (layer instanceof L.Polyline || layer instanceof L.Polygon) {
                                    layer.on('popupopen', () => {
                                        const btn = document.querySelector('.delete-feature-btn');
                                        if (btn) {
                                            btn.addEventListener('click', function () {
                                                const id = this.getAttribute('data-id');
                                                if (confirm("Anda yakin akan menghapus feature ini?")) {
                                                    fetch(`/delFeatures/${id}`, {
                                                        method: 'DELETE',
                                                        headers: {
                                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                                        }
                                                    }).then(res => {
                                                        if (res.ok) {
                                                            // Remove feature and its vertices
                                                            map.removeLayer(featureStore[id]);
                                                            delete featureStore[id];
                                                            
                                                            if (vertexStore[id]) {
                                                                vertexStore[id].forEach(vertex => {
                                                                    if (vertex) map.removeLayer(vertex);
                                                                });
                                                                delete vertexStore[id];
                                                            }
                                                        } else {
                                                            alert("Gagal menghapus feature, Coba Lagi.");
                                                        }
                                                    }).catch(err => {
                                                        console.error(err);
                                                    });
                                                }
                                            });
                                        }
                                    });
                                }
                            });
                        }

                        // Load existing markers
                        fetch('/allMarkers')
                            .then(res => res.json())
                            .then(markers => {
                                markers.forEach(marker => {
                                    addMarker(marker);
                                });
                                attachDeleteHandlers();
                            });

                        // Load existing features
                        fetch('/allFeatures')
                            .then(res => res.json())
                            .then(features => {
                                features.forEach(feature => {
                                    if (feature.type === 'line') {
                                        addLineFeature(feature);
                                    } else if (feature.type === 'polygon') {
                                        addPolygonFeature(feature);
                                    }
                                });
                                attachDeleteHandlers();
                            });

                        let clickedLat, clickedLng;

                        map.on('click', function (e) {
                            clickedLat = e.latlng.lat;
                            clickedLng = e.latlng.lng;

                            if (currentTool === 'marker') {
                                // Menampilkan form marker
                                const form = document.getElementById('marker-form');
                                const point = map.latLngToContainerPoint(e.latlng);
                                form.style.top = `${point.y + 5}px`;
                                form.style.left = `${point.x + 5}px`;
                                form.classList.remove('hidden');
                            } else if (currentTool === 'line' || currentTool === 'polygon') {
                                // Menambahkan titik ke garis atau polygon
                                currentPoints.push({
                                    lat: e.latlng.lat,
                                    lng: e.latlng.lng
                                });

                                // Tambahkan vertex untuk titik yang di-klik
                                const vertexMarker = L.marker([e.latlng.lat, e.latlng.lng], {
                                    icon: vertexIcon,
                                    draggable: true
                                }).addTo(map);
                                
                                vertexMarker.on('dragend', function() {
                                    const index = currentVertices.indexOf(this);
                                    if (index !== -1) {
                                        const latlng = this.getLatLng();
                                        currentPoints[index] = {
                                            lat: latlng.lat,
                                            lng: latlng.lng
                                        };
                                        updateDrawing();
                                    }
                                });
                                
                                currentVertices.push(vertexMarker);

                                // Memulai atau memperbarui drawing
                                updateDrawing();

                                // Jika ini adalah titik kedua atau lebih dan double-click, akhiri gambar
                                if (currentPoints.length >= 2 && e.originalEvent.detail === 2) {
                                    finishDrawing();
                                }
                            }
                        });

                        function updateDrawing() {
                            if (currentPoints.length < 1) return;

                            if (currentDrawing) {
                                map.removeLayer(currentDrawing);
                            }

                            const latlngs = currentPoints.map(p => [p.lat, p.lng]);

                            if (currentTool === 'line') {
                                currentDrawing = L.polyline(latlngs, lineStyle).addTo(map);
                            } else if (currentTool === 'polygon' && currentPoints.length >= 3) {
                                currentDrawing = L.polygon(latlngs, polygonStyle).addTo(map);
                            } else if (currentTool === 'polygon') {
                                currentDrawing = L.polyline(latlngs, lineStyle).addTo(map);
                            }
                        }

                        function finishDrawing() {
                            if (currentPoints.length < 2) return;
                            
                            // Tampilkan form untuk menyimpan fitur
                            const form = document.getElementById('feature-form');
                            const title = document.getElementById('feature-form-title');
                            const typeField = document.getElementById('feature-type');
                            
                            if (currentTool === 'line') {
                                title.textContent = 'Tambah Garis Baru';
                                typeField.value = 'line';
                            } else {
                                title.textContent = 'Tambah Polygon Baru';
                                typeField.value = 'polygon';
                            }
                            
                            form.classList.remove('hidden');
                        }

                        document.getElementById('cancel-marker').addEventListener('click', () => {
                            document.getElementById('marker-form').classList.add('hidden');
                        });

                        document.getElementById('cancel-feature').addEventListener('click', () => {
                            document.getElementById('feature-form').classList.add('hidden');
                            clearCurrentDrawing();
                        });

                        document.getElementById('cancel-update').addEventListener('click', () => {
                            stopEditing();
                        });

                        document.getElementById('save-marker-form').addEventListener('submit', function (e) {
                            e.preventDefault();
                            const name = document.getElementById('marker-name').value;
                            const description = document.getElementById('marker-description').value;

                            fetch('/markers', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    name,
                                    description,
                                    latitude: clickedLat,
                                    longitude: clickedLng
                                })
                            }).then(res => res.json())
                              .then(marker => {
                                addMarker(marker);
                                attachDeleteHandlers();

                                document.getElementById('marker-form').classList.add('hidden');
                                document.getElementById('marker-name').value = '';
                                document.getElementById('marker-description').value = '';
                            });
                        });

                        document.getElementById('save-feature-form').addEventListener('submit', function (e) {
                            e.preventDefault();
                            const name = document.getElementById('feature-name').value;
                            const type = document.getElementById('feature-type').value;

                            fetch('/features', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    name,
                                    type,
                                    coordinates: currentPoints
                                })
                            }).then(res => res.json())
                              .then(feature => {
                                if (feature.type === 'line') {
                                    addLineFeature(feature);
                                } else if (feature.type === 'polygon') {
                                    addPolygonFeature(feature);
                                }
                                attachDeleteHandlers();

                                document.getElementById('feature-form').classList.add('hidden');
                                document.getElementById('feature-name').value = '';
                                clearCurrentDrawing();
                            });
                        });

                        document.getElementById('update-feature-form-element').addEventListener('submit', function (e) {
                            e.preventDefault();
                            const featureId = document.getElementById('update-feature-id').value;
                            const feature = featureStore[featureId];
                            
                            if (!feature) return;
                            
                            const featureType = feature instanceof L.Polygon ? 'polygon' : 'line';
                            const coordinates = updateFeatureGeometry(featureId, featureType);
                            
                            // Update in database
                            fetch(`/updateFeature/${featureId}`, {
                                method: 'PUT',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    coordinates: coordinates
                                })
                            }).then(res => {
                                if (res.ok) {
                                    stopEditing();
                                } else {
                                    alert("Gagal menyimpan perubahan, Coba Lagi.");
                                }
                            }).catch(err => {
                                console.error(err);
                            });
                        });

                        map.invalidateSize();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>