/**
 * File: assets/js/landing-map.js
 * Deskripsi: JavaScript untuk peta interaktif di landing page (index.php)
 * Dependencies: Leaflet.js
 */

// ================================
// GLOBAL VARIABLES
// ================================
let map = null;
let schoolMarkers = [];
let kecamatanLayers = [];

// Custom school icon
const schoolIcon = L.divIcon({
    html: '<div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 3px 10px rgba(0,0,0,0.3);"><i class="fas fa-school" style="transform: rotate(45deg); color: white; font-size: 14px; display: flex; align-items: center; justify-content: center; height: 100%;"></i></div>',
    className: 'school-marker',
    iconSize: [32, 32],
    iconAnchor: [16, 32],
    popupAnchor: [0, -32]
});

// ================================
// INITIALIZE MAP
// ================================
async function initLandingMap(sekolahData) {
    if (!document.getElementById('landingMap')) return;

    // Create map centered on Padang
    map = L.map('landingMap', {
        scrollWheelZoom: true,
        zoomControl: true
    }).setView([-0.9371, 100.3600], 12);

    // Add OpenStreetMap tiles with clean style
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
        maxZoom: 19
    }).addTo(map);

    // Load & Display kecamatan boundaries
    await loadAndDisplayKecamatan();

    // Display school markers
    displaySchoolMarkers(sekolahData);

}

// ================================
// LOAD KECAMATAN GEOJSON
// ================================
async function loadAndDisplayKecamatan() {
    try {
        const response = await fetch('assets/data/kecamatan_boundaries.geojson');
        const data = await response.json();

        // Softer, more professional colors
        const colors = [
            '#6366f1', // Indigo
            '#8b5cf6', // Violet
            '#ec4899', // Pink
            '#f43f5e', // Rose
            '#f97316', // Orange
            '#eab308', // Yellow
            '#22c55e', // Green
            '#14b8a6', // Teal
            '#06b6d4', // Cyan
            '#3b82f6', // Blue
            '#a855f7'  // Purple
        ];

        data.features.forEach((feature, index) => {
            const color = colors[index % colors.length];
            const layer = L.geoJSON(feature, {
                style: {
                    color: color,
                    weight: 2,
                    opacity: 0.8,
                    fillOpacity: 0.15,  // Much lighter fill
                    fillColor: color
                },
                onEachFeature: (feature, layer) => {
                    layer.bindTooltip(`<strong>Kec. ${feature.properties.nama}</strong>`, {
                        permanent: false,
                        direction: 'center',
                        className: 'kecamatan-tooltip'
                    });

                    // Highlight on hover
                    layer.on('mouseover', function () {
                        this.setStyle({
                            weight: 4,
                            fillOpacity: 0.35
                        });
                        this.bringToFront();
                    });

                    layer.on('mouseout', function () {
                        this.setStyle({
                            weight: 2,
                            fillOpacity: 0.15
                        });
                    });
                }
            }).addTo(map);
            kecamatanLayers.push(layer);
        });

    } catch (error) {
        console.warn('⚠️ Failed to load kecamatan data:', error);
    }
}

// ================================
// DISPLAY SCHOOL MARKERS
// ================================
function displaySchoolMarkers(sekolahData) {
    sekolahData.forEach(sekolah => {
        if (!sekolah.latitude || !sekolah.longitude) return;

        // Add marker with custom icon
        const marker = L.marker([sekolah.latitude, sekolah.longitude])
            .bindPopup(createPopupContent(sekolah), {
                maxWidth: 300,
                className: 'school-popup'
            })
            .addTo(map);

        schoolMarkers.push(marker);
    });
}

// ================================
// CREATE POPUP CONTENT
// ================================
function createPopupContent(sekolah) {
    return `
        <div style="min-width: 260px; text-align: left; font-family: 'Inter', sans-serif;">
            <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px; margin: -14px -14px 12px -14px; border-radius: 4px 4px 0 0;">
                <h6 style="margin: 0; font-weight: 600; font-size: 14px;">
                    <i class="fas fa-school me-2"></i>${sekolah.nama_sekolah}
                </h6>
            </div>
            <div style="padding: 0 4px;">
                <div class="mb-2 small d-flex align-items-start">
                    <i class="fas fa-map-marker-alt text-danger me-2" style="margin-top: 3px;"></i>
                    <span>${sekolah.alamat_sekolah || '-'}</span>
                </div>
                <div class="mb-2 small d-flex align-items-center">
                    <i class="fas fa-city text-info me-2"></i>
                    <span>Kec. ${sekolah.kecamatan}</span>
                </div>
                <div class="row g-2 small mt-2 pt-2" style="border-top: 1px solid #eee;">
                    <div class="col-6">
                        <i class="fas fa-users text-primary me-1"></i>
                        <strong>${sekolah.kuota_total || 0}</strong> Kuota
                    </div>
                    <div class="col-6 text-end">
                        <span class="badge" style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);">
                            ${sekolah.akreditasi || 'B'}
                        </span>
                    </div>
                </div>
                <a href="sekolah_detail.php?id=${sekolah.id_sekolah}" 
                   class="btn btn-sm w-100 mt-3" 
                   style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none;">
                    <i class="fas fa-info-circle me-1"></i>Lihat Detail
                </a>
            </div>
        </div>
    `;
}

// ================================
// TOGGLE FUNCTIONS
// ================================
let polygonsVisible = true;

function togglePolygons() {
    const checkbox = document.getElementById('polygonToggle');

    // If called from checkbox, use its state; otherwise toggle
    if (checkbox) {
        polygonsVisible = checkbox.checked;
    } else {
        polygonsVisible = !polygonsVisible;
    }

    kecamatanLayers.forEach(layer => {
        if (polygonsVisible) map.addLayer(layer);
        else map.removeLayer(layer);
    });
}
