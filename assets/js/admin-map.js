/**
 * File: assets/js/admin-map.js
 * Deskripsi: Map picker untuk admin menentukan lokasi sekolah
 */

let map;
let marker;

function initAdminMap(initialLat, initialLng) {
    const defaultLat = -0.9471; // Padang default
    const defaultLng = 100.4172;

    // Use initial if valid, else default
    const lat = parseFloat(initialLat) || defaultLat;
    const lng = parseFloat(initialLng) || defaultLng;

    // Init map
    map = L.map('mapPicker').setView([lat, lng], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    // Add marker
    marker = L.marker([lat, lng], {
        draggable: true
    }).addTo(map);

    // Events
    marker.on('dragend', function (event) {
        const position = marker.getLatLng();
        updateInputs(position.lat, position.lng);
    });

    map.on('click', function (e) {
        moveMarker(e.latlng.lat, e.latlng.lng);
    });

    // Handle manual lat/lng input
    document.getElementById('latitude').addEventListener('change', updateMapFromInput);
    document.getElementById('longitude').addEventListener('change', updateMapFromInput);
}

function moveMarker(lat, lng) {
    marker.setLatLng([lat, lng]);
    updateInputs(lat, lng);
}

function updateInputs(lat, lng) {
    document.getElementById('latitude').value = lat.toFixed(6);
    document.getElementById('longitude').value = lng.toFixed(6);
}

function updateMapFromInput() {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(document.getElementById('longitude').value);

    if (!isNaN(lat) && !isNaN(lng)) {
        moveMarker(lat, lng);
        map.panTo([lat, lng]);
    }
}
