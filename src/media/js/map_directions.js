/**
* Renders a map, but also does fun stuff with driving directions
**/
$(document).ready(function() {
    var directionsService = new google.maps.DirectionsService();
    var geocoder = new google.maps.Geocoder();

    $('.directions').each(function() {
        var $map = $(this).find('.directions-map');
        var $list = $(this).find('.directions-list');
        var $frm = $(this).find('.directions-form');
        var $frmTxt = $(this).find('.directions-txt');
        var $frmBtn = $(this).find('.directions-btn');
        var address = $(this).attr('data-address');
        var zoom = parseInt($(this).attr('data-zoom'), 10);

        $map.height($map.width() / 4 * 3);
        $list.hide();

        var map = new google.maps.Map($map[0], {
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            zoom: zoom,
            center: new google.maps.LatLng(0, 0),
            scrollwheel: false,
            streetViewControl: false,
        });

        geocoder.geocode({'address': address}, function(results, status) {
            if (status == google.maps.GeocoderStatus.OK) {
                map.setCenter(results[0].geometry.location);
                new google.maps.Marker({
                    map: map,
                    position: results[0].geometry.location
                });
            } else {
                alert('Unable to find address');
            }
        });

        var directionsDisplay = new google.maps.DirectionsRenderer();
        directionsDisplay.setMap(map);

        $frm.submit(function() {
            request = {
                origin: $frmTxt.val(),
                destination: address,
                travelMode: google.maps.DirectionsTravelMode.DRIVING,
                region: 'au'
            };

            directionsService.route(request, function(response, status) {
                if (status != google.maps.DirectionsStatus.OK) {
                    alert('Unable to determine directions');
                    return;
                }

                directionsDisplay.setDirections(response);

                var html = '<ol>';
                $.each(response.routes[0].legs[0].steps, function(k, step) {
                    html += '<li>' + step.instructions + '</li>';
                });
                html += '</ol>';

                $list.html(html).show();
            });

            return false;
        });
    });
});
