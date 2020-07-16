/**
 * Initialise map widget
 * @param {String} map ID of div to render map
 * @param {float} center_lat Center latitude
 * @param {float} center_lng Center longitude
 * @param {int} zoom Zoom level: 1-15
 * @param {bool} marker Add marker when true
 * @return {void}
 */
function initMapWidget(map, lat, lng, zoom, marker)
{
    var lat = lat;
    var lng = lng;
    var marker = marker || false;
    map = L.map(map).setView([lat, lng], zoom);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank">OpenStreetMap</a> contributors'
    }).addTo(map);

    if (!marker) return map;

    marker = L.marker([lat, lng]).addTo(map);

    marker.on('click', function()
    {
        window.open('https://www.openstreetmap.org/search?query='+lat+','+lng,'_blank');
    });

    return map;
}
