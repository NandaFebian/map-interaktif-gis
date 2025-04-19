<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Interactive Map') }}
        </h2>
    </x-slot>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div id="map" style="height: 80vh; width: 100%;"></div>

                    <meta name="csrf-token" content="{{ csrf_token() }}">

                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

                    <div id="marker-form" class="hidden fixed bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg z-50 w-96" style="z-index: 9999;">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Add New Marker</h3>
                        <form id="save-marker-form">
                            <div class="mb-4">
                                <label for="marker-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marker Name</label>
                                <input type="text" id="marker-name" name="name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-black" required />
                            </div>
                            <div class="mb-4">
                                <label for="marker-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea id="marker-description" name="description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm text-black"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="button" id="cancel-marker" class="mr-2 px-4 py-2 border border-gray-300 rounded-md text-sm text-gray-700 bg-white hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 border border-transparent rounded-md text-sm text-white bg-indigo-600 hover:bg-indigo-700">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Modal Konfirmasi Hapus -->
                    <div class="modal fade" id="deleteMarkerModal" tabindex="-1" aria-labelledby="deleteMarkerModalLabel" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="deleteMarkerModalLabel">Konfirmasi Hapus Marker</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    Apakah Anda yakin ingin menghapus marker ini?
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                    <button type="button" class="btn btn-danger" id="confirmDeleteMarkerBtn">Hapus</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <script>
                        const map = L.map('map').setView([-6.2, 106.816666], 13);

                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        const markerStore = {}; // Store markers by ID

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

                        function attachDeleteHandlers() {
                            map.eachLayer(layer => {
                                if (layer instanceof L.Marker) {
                                    layer.on('popupopen', () => {
                                        const btn = document.querySelector('.delete-marker-btn');
                                        if (btn) {
                                            btn.addEventListener('click', function () {
                                                const id = this.getAttribute('data-id');
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
                                                        alert("Failed to delete marker.");
                                                    }
                                                }).catch(err => {
                                                    console.error(err);
                                                });
                                            });
                                        }
                                    });
                                }
                            });
                        }

                        fetch('/allMarkers')
                            .then(res => res.json())
                            .then(markers => {
                                markers.forEach(marker => {
                                    addMarker(marker);
                                });
                                attachDeleteHandlers();
                            });

                        let clickedLat, clickedLng;

                        map.on('click', function (e) {
                            clickedLat = e.latlng.lat;
                            clickedLng = e.latlng.lng;

                            const form = document.getElementById('marker-form');
                            const point = map.latLngToContainerPoint(e.latlng);
                            form.style.top = `${point.y + 5}px`;
                            form.style.left = `${point.x + 5}px`;
                            form.classList.remove('hidden');
                        });

                        document.getElementById('cancel-marker').addEventListener('click', () => {
                            document.getElementById('marker-form').classList.add('hidden');
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

                        map.invalidateSize();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
