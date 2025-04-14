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
                    <!-- Peta -->
                    <div id="map" style="height: 500px; width: 100%;"></div>

                    <!-- Meta Tag untuk CSRF Token -->
                    <meta name="csrf-token" content="{{ csrf_token() }}">

                    <!-- Leaflet CSS -->
                    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
                    <!-- Leaflet JS -->
                    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

                    <!-- Form untuk Input Marker -->
                    <div id="marker-form" class="hidden fixed bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg z-50 w-96" style="z-index: 9999;">
                        <h3 class="text-lg font-semibold text-gray-800 dark:text-gray-200 mb-4">Add New Marker</h3>
                        <form id="save-marker-form">
                            <div class="mb-4">
                                <label for="marker-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Marker Name</label>
                                <input type="text" id="marker-name" name="name" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" required />
                            </div>
                            <div class="mb-4">
                                <label for="marker-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea id="marker-description" name="description" rows="3" class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="button" id="cancel-marker" class="mr-2 inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Cancel
                                </button>
                                <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Save
                                </button>
                            </div>
                        </form>
                    </div>

                    <script>
                        // Initialize the map
                        const map = L.map('map').setView([-6.200000, 106.816666], 13); // Koordinat Jakarta

                        // Add tile layer
                        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            attribution: '© OpenStreetMap contributors'
                        }).addTo(map);

                        // Function to add markers to the map
                        function addMarkersToMap(markers) {
                            markers.forEach(marker => {
                                const markerPopup = `
                                    <div>
                                        <strong>${marker.name}</strong><br>
                                        ${marker.description || 'No description'}<br>
                                        <button 
                                            class="delete-marker-btn" 
                                            data-id="${marker.id}" 
                                            style="
                                                background-color: red; 
                                                color: white; 
                                                border: none; 
                                                padding: 5px 10px; 
                                                cursor: pointer; 
                                                position: relative; 
                                                z-index: 9999;
                                            "
                                        >
                                            Delete
                                        </button>
                                    </div>
                                `;
                                L.marker([marker.latitude, marker.longitude])
                                    .addTo(map)
                                    .bindPopup(markerPopup);
                            });

                            // Add event listeners for delete buttons
                            addDeleteButtonListeners();
                        }

                        // Fetch markers from API and display them on the map
                        fetch('/allMarkers')
                            .then(response => response.json())
                            .then(markers => {
                                addMarkersToMap(markers);
                            })
                            .catch(error => {
                                console.error('Error fetching markers:', error);
                            });

                        // Variables to store clicked coordinates
                        let clickedLatitude, clickedLongitude;

                        // Show form when map is clicked
                        map.on('click', function(e) {
                            const { lat, lng } = e.latlng;
                            clickedLatitude = lat;
                            clickedLongitude = lng;

                            // Convert latitude and longitude to pixel position
                            const containerPoint = map.latLngToContainerPoint(e.latlng);

                            // Position the form near the cursor with a small offset
                            const form = document.getElementById('marker-form');
                            form.style.top = `${containerPoint.y + 5}px`; // Offset vertikal kecil
                            form.style.left = `${containerPoint.x + 5}px`; // Offset horizontal kecil
                            form.classList.remove('hidden'); // Show the form
                        });

                        // Handle cancel button
                        document.getElementById('cancel-marker').addEventListener('click', function() {
                            document.getElementById('marker-form').classList.add('hidden');
                        });

                        // Handle save button
                        document.getElementById('save-marker-form').addEventListener('submit', function(e) {
                            e.preventDefault();

                            // Get form data
                            const name = document.getElementById('marker-name').value;
                            const description = document.getElementById('marker-description').value;

                            // Send data to server using Fetch API
                            fetch('/markers', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: JSON.stringify({
                                    name: name,
                                    latitude: clickedLatitude,
                                    longitude: clickedLongitude,
                                    description: description
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                console.log('Marker saved:', data);

                                // Add new marker to the map
                                const markerPopup = `
                                    <div>
                                        <strong>${name}</strong><br>
                                        ${description || 'No description'}<br>
                                        <button 
                                            class="delete-marker-btn" 
                                            data-id="${data.id}" 
                                            style="
                                                background-color: red; 
                                                color: white; 
                                                border: none; 
                                                padding: 5px 10px; 
                                                cursor: pointer; 
                                                position: relative; 
                                                z-index: 9999;
                                            "
                                        >
                                            Delete
                                        </button>
                                    </div>
                                `;
                                L.marker([clickedLatitude, clickedLongitude])
                                    .addTo(map)
                                    .bindPopup(markerPopup);

                                // Add event listener for the new delete button
                                addDeleteButtonListeners();

                                // Hide the form
                                document.getElementById('marker-form').classList.add('hidden');

                                // Clear form fields
                                document.getElementById('marker-name').value = '';
                                document.getElementById('marker-description').value = '';
                            })
                            .catch(error => {
                                console.error('Error saving marker:', error);
                            });
                        });

                        // Function to handle delete button clicks
                        function addDeleteButtonListeners() {
                            document.querySelectorAll('.delete-marker-btn').forEach(button => {
                                button.addEventListener('click', function() {
                                    const markerId = this.getAttribute('data-id'); // Get marker ID from the button's data-id attribute

                                    // Send DELETE request to remove the marker
                                    fetch(`/delMarkers/${markerId}`, {
                                        method: 'DELETE',
                                        headers: {
                                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                        }
                                    })
                                    .then(response => {
                                        if (response.ok) {
                                            console.log('Marker deleted successfully');

                                            // Remove the marker from the map
                                            const markerElement = this.closest('.leaflet-popup-content').parentNode.parentNode;
                                            map.removeLayer(markerElement.__parent);
                                        } else {
                                            console.error('Failed to delete marker:', response.statusText);
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error deleting marker:', error);
                                    });
                                });
                            });
                        }

                        // Invalidate size to ensure map fills the container
                        map.invalidateSize();
                    </script>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>