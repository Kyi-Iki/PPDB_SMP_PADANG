/**
 * File: assets/js/school-map.js
 * Deskripsi: JavaScript untuk peta interaktif di halaman pilih sekolah
 * Dependencies: Leaflet.js, Turf.js
 */

// ================================
// GLOBAL VARIABLES
// ================================
let map = null;
let userMarker = null;
let schoolMarkers = [];
let kecamatanLayers = [];
let kecamatanData = null;
let userLocation = null;
let userKecamatan = null;

// ================================
// INITIALIZE MAP
// ================================
async function initSchoolMap(sekolahData) {
    // Load kecamatan data
    await loadKecamatanData();

    // Create map centered on Padang
    map = L.map('schoolMap').setView([-0.9371, 100.3600], 12);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 19
    }).addTo(map);

    // Display kecamatan boundaries
    displayKecamatanBoundaries();

    // Display school markers
    displaySchoolMarkers(sekolahData);

}

// ================================
// LOAD KECAMATAN GEOJSON
// ================================
async function loadKecamatanData() {
    try {
        const basePath = document.querySelector('meta[name="base-path"]')?.content || '';
        const response = await fetch(basePath + '/assets/data/kecamatan_boundaries.geojson');
        kecamatanData = await response.json();
    } catch (error) {
        console.warn('⚠️ Kecamatan data not found:', error);
    }
}

// ================================
// DISPLAY KECAMATAN BOUNDARIES
// ================================
function displayKecamatanBoundaries() {
    if (!kecamatanData) return;

    // Warna yang kontras dan tidak menyatu dengan peta (Vibrant colors)
    const colors = [
        '#e6194b', // Red
        '#3cb44b', // Green
        '#ffe119', // Yellow
        '#4363d8', // Blue
        '#f58231', // Orange
        '#911eb4', // Purple
        '#f032e6', // Magenta
        '#000075', // Navy
        '#800000', // Maroon
        '#808000', // Olive
        '#aaffc3'  // Mint
    ];

    kecamatanData.features.forEach((feature, index) => {
        const layer = L.geoJSON(feature, {
            style: {
                color: colors[index % colors.length],
                weight: 3,
                opacity: 1,
                fillOpacity: 0.5,
                fillColor: colors[index % colors.length]
            },
            onEachFeature: (feature, layer) => {
                layer.bindPopup(`
                    <strong>Kec. ${feature.properties.nama}</strong><br>
                    <small>Luas: ${feature.properties.luas_km2 || '-'} km²</small>
                `);
            }
        }).addTo(map);

        kecamatanLayers.push({ layer, feature });
    });
}

// ================================
// DISPLAY SCHOOL MARKERS
// ================================
function displaySchoolMarkers(sekolahData) {
    schoolMarkers.forEach(m => map.removeLayer(m));
    schoolMarkers = [];

    sekolahData.forEach(sekolah => {
        if (!sekolah.latitude || !sekolah.longitude) return;

        // Add marker
        const marker = L.marker([sekolah.latitude, sekolah.longitude])
            .bindPopup(createSchoolPopup(sekolah))
            .addTo(map);

        marker.schoolId = sekolah.id_sekolah;
        marker.on('click', () => selectSchoolOnMap(sekolah.id_sekolah));

        schoolMarkers.push(marker);
    });
}

// ================================
// CREATE POPUP CONTENT
// ================================
function createSchoolPopup(sekolah) {
    const distance = sekolah.distance ? `<br><strong>Jarak:</strong> ${formatDistance(sekolah.distance)}` : '';

    return `
        <div style="min-width: 200px;">
            <h6 class="fw-bold mb-2">${sekolah.nama_sekolah}</h6>
            <p class="small mb-1">📍 ${sekolah.alamat_sekolah || sekolah.kecamatan}</p>
            <p class="small mb-2">
                <strong>Kuota:</strong> ${sekolah.kuota_total || '-'} siswa
                ${distance}
            </p>
            <button class="btn btn-primary btn-sm w-100 mt-2" onclick="selectSchoolOnMap(${sekolah.id_sekolah})">
                Pilih Sekolah Ini
            </button>
        </div>
    `;
}

// ================================
// GET USER LOCATION
// ================================
function getUserLocation() {
    const btn = document.getElementById('btnGetLocation');
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Mendeteksi...';
    }

    if (!navigator.geolocation) {
        alert('Browser tidak mendukung Geolocation!');
        resetLocationButton();
        return;
    }

    navigator.geolocation.getCurrentPosition(
        position => {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            setUserLocation(lat, lng);

            if (btn) {
                btn.innerHTML = '<i class="fas fa-check-circle"></i> Lokasi Terdeteksi';
                btn.classList.remove('btn-success');
                btn.classList.add('btn-primary');
            }
        },
        error => {
            console.error('Geolocation error:', error);
            alert('Tidak dapat mengakses lokasi. Pastikan izin lokasi diaktifkan.');
            resetLocationButton();
        },
        { enableHighAccuracy: true, timeout: 10000 }
    );
}

function resetLocationButton() {
    const btn = document.getElementById('btnGetLocation');
    if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-crosshairs"></i> Gunakan Lokasi Saya';
    }
}

// ================================
// SET USER LOCATION
// ================================
function setUserLocation(lat, lng) {
    userLocation = { lat, lng };

    // Remove old marker
    if (userMarker) {
        map.removeLayer(userMarker);
    }

    // Add user marker (green)
    userMarker = L.marker([lat, lng], {
        icon: L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-green.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/0.7.7/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        })
    }).addTo(map);

    userMarker.bindPopup('<strong>📍 Lokasi Anda</strong>').openPopup();

    // Pan to user location
    map.setView([lat, lng], 14);

    // Detect kecamatan
    detectUserKecamatan(lat, lng);

    // Calculate distance for all schools
    calculateAllDistances();
}

// ================================
// DETECT USER KECAMATAN
// ================================
function detectUserKecamatan(lat, lng) {
    if (!kecamatanData || !window.turf) {
        userKecamatan = null;
        return;
    }

    const point = turf.point([lng, lat]); // GeoJSON uses [lng, lat]

    for (const feature of kecamatanData.features) {
        if (turf.booleanPointInPolygon(point, feature)) {
            userKecamatan = feature.properties.nama;

            // Update UI
            const infoEl = document.getElementById('userLocationInfo');
            if (infoEl) {
                infoEl.style.display = 'block';
                infoEl.innerHTML = `
                    <i class="fas fa-map-marker-alt text-success"></i>
                    <strong>Kecamatan:</strong> ${userKecamatan} | 
                    <strong>Koordinat:</strong> ${lat.toFixed(5)}, ${lng.toFixed(5)}
                `;
            }

            // Highlight kecamatan
            highlightUserKecamatan(feature.properties.nama);

            return;
        }
    }

    userKecamatan = 'Di luar Padang';
    const infoEl = document.getElementById('userLocationInfo');
    if (infoEl) {
        infoEl.style.display = 'block';
        infoEl.innerHTML = `
            <i class="fas fa-exclamation-triangle text-warning"></i>
            Lokasi Anda di luar area Kota Padang
        `;
    }
}

function highlightUserKecamatan(kecamatanName) {
    kecamatanLayers.forEach(item => {
        if (item.feature.properties.nama === kecamatanName) {
            item.layer.setStyle({ weight: 4, fillOpacity: 0.25, color: '#198754' });
            item.layer.bringToFront();
        } else {
            item.layer.setStyle({ weight: 2, fillOpacity: 0.1 });
        }
    });
}

// ================================
// CALCULATE DISTANCES
// ================================
function calculateAllDistances() {
    if (!userLocation || !window.sekolahDataGlobal) return;

    window.sekolahDataGlobal.forEach(sekolah => {
        if (!sekolah.latitude || !sekolah.longitude) return;

        const distance = hitungJarak(
            userLocation.lat, userLocation.lng,
            parseFloat(sekolah.latitude), parseFloat(sekolah.longitude)
        );

        sekolah.distance = distance;

        // Update school card
        updateSchoolCardDistance(sekolah);
    });

    // Refresh map markers
    displaySchoolMarkers(window.sekolahDataGlobal);
}

function updateSchoolCardDistance(sekolah) {
    const card = document.querySelector(`[data-school-id="${sekolah.id_sekolah}"]`);
    if (!card) return;

    // Add distance
    const distanceEl = card.querySelector('.school-distance');
    if (distanceEl) {
        distanceEl.textContent = formatDistance(sekolah.distance);
        distanceEl.style.display = 'inline';
    }

    // Set data-distance attribute for sorting
    card.setAttribute('data-distance', sekolah.distance);
}

// ================================
// SELECT SCHOOL FROM MAP
// ================================
function selectSchoolOnMap(schoolId) {
    // Find and select the radio button
    const radio = document.querySelector(`input[name="id_sekolah"][value="${schoolId}"]`);
    if (radio) {
        radio.checked = true;

        // Scroll to the school in list
        const card = document.querySelector(`[data-school-id="${schoolId}"]`);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Highlight briefly
            card.classList.add('bg-primary-subtle');
            setTimeout(() => card.classList.remove('bg-primary-subtle'), 2000);
        }
    }

    // Zoom to school on map
    const school = window.sekolahDataGlobal?.find(s => s.id_sekolah == schoolId);
    if (school && school.latitude && school.longitude) {
        map.flyTo([school.latitude, school.longitude], 15);

        // Open popup
        const marker = schoolMarkers.find(m => m.schoolId == schoolId);
        if (marker) marker.openPopup();
    }
}

// ================================
// HELPER FUNCTIONS
// ================================
function hitungJarak(lat1, lon1, lat2, lon2) {
    const R = 6371000; // Earth radius in meters
    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;
    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}

function formatDistance(meters) {
    if (meters < 1000) {
        return Math.round(meters) + ' m';
    }
    return (meters / 1000).toFixed(2) + ' km';
}

// Toggle map visibility
function toggleSchoolMap() {
    const mapContainer = document.getElementById('mapContainer');
    const toggleBtn = document.getElementById('toggleMapBtn');

    if (mapContainer.style.display === 'none') {
        mapContainer.style.display = 'block';
        toggleBtn.innerHTML = '<i class="fas fa-map"></i> Sembunyikan Peta';

        // Re-render map if needed
        setTimeout(() => map?.invalidateSize(), 100);
    } else {
        mapContainer.style.display = 'none';
        toggleBtn.innerHTML = '<i class="fas fa-map"></i> Tampilkan Peta';
    }
}

// Backward compatibility for calculateAllEligibility (used by pilih_sekolah.php)
function calculateAllEligibility() {
    calculateAllDistances();
}
