google.load("maps", "2.x");
var eventoniMap = null;
var eventArray = new Array();

// Call this function when the page has been loaded
function initialize() {
	eventoniMap = new google.maps.Map2(document.getElementById("eventoni_map"));
	eventoniMap.setCenter(new google.maps.LatLng(37.4419, -122.1419), 14);
	eventoniMap.enableScrollWheelZoom();
}

function addMarker( geoLocation ){
	if( geoLocation ){
	    var marker = new GMarker(geoLocation);
	    eventoniMap.addOverlay(marker);
	    GEvent.addListener(marker, "click", function(latlng) {
		eventoniMap.panTo( geoLocation );
	       });
	}
}

function addMarkersGeo( geoLocationData ){
	for( var i = 0; i < geoLocationData.length; i++ ){
        var point = new GLatLng(geoLocationData[i].lat,geoLocationData[i].lng);
        addMarker(point);
	}
}

function addMarkersAddress( addresses ){
	var geoCoder = new GClientGeocoder();
	for( var i = 0; i < addresses.length; i++ ){
		geoCoder.getLatLng(addresses[i], addMarker);
	}
}

function centerMap( gLatLng ){
	if( gLatLng != null ){
		eventoniMap.setCenter(gLatLng,14);
	}
}

function centerMapOnAddress( address ){
	var geoCoder = new GClientGeocoder();
	geoCoder.getLatLng(address, centerMap);
}

function mapPanTo( gLatLng ){
	if( gLatLng != null ){
		eventoniMap.panTo(gLatLng);
	}
}

function mapPanToAddress( address ){
	var geoCoder = new GClientGeocoder();
	geoCoder.getLatLng(address, mapPanTo);
}

jQuery(function ($) {
	// Werte für die Umkreissuche
	latitude  = false;
	longitude = false;

	// Ticker Funktionalität für Event-Liste
	function ticker(selected_element){
		if(!hovered) {
			timeout = window.setTimeout(function(){
				var elements = $('#top5_1, #top5_2, #top5_3, #top5_4, #top5_5');
				// zuletzt selektiertes Element verkleinern
				$(elements[selected_element]).removeClass('big').addClass('small');
				selected_element++;
				// bei Element 1 von vorne beginnen, falls Ticker am Ende der Liste angelangt ist
				if(selected_element >= elements.length) {
					selected_element = 0;
				}

				// neu selektiertes Element vergrößern
				$(elements[selected_element]).removeClass('small').addClass('big');

				//Google Maps
				var id = $(elements[selected_element]).attr('id');
				if( typeof eventoniMap != 'undefined' && eventoniMap != null ){
					centerMapOnAddress(eventArray[id.substr(id.length-1,id.length)-1]);
				}

				// rekursiver Aufruf des Ticker-Timers
				ticker(selected_element);
			}, 3000);
		}
	}

	// Ajax-Suche nach Events
	function eventoni_search(data){
		clearTimeout(timeout);
		$('#top5_1, #top5_2, #top5_3, #top5_4, #top5_5').css('display', 'none');
		// Benutzer informieren, dass Events geladen werden
		$('#eventoni_form').append('<div id="eventoni_loading"><img src="'+eventoni_plugin_url+'img/loadinfo.net.gif" /></div>');
		$('#eventoni_status').remove();


		jQuery.post(eventoni_ajax_url, data, function(response) {
			$('#eventoni_loading').remove();
			// Google Maps
			if( eventoniMap != null ){
				eventoniMap.clearOverlays();
			}
			eventArray = new Array();
			response = response.replace(/title>/g, "mytitle>");

			if (jQuery.browser.msie) {
			    var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
			    xmlDoc.loadXML(response);
			    response = xmlDoc;
			}

			// Events in Liste einfügen
			var i = 0;
			$(response).find("event").each(function() {
				i++;
				if(i>5*($('#eventoni_subpage').attr('value')-0)) {
					var marker = $(this);
					var event = new Array();
					event['title'] = marker.find('mytitle').text();
					event['description'] = marker.find('description').text();
					event['start_date'] = marker.find('start_date').text();
					event['start_time'] = marker.find('start_time').text();
					//event['location'] = marker.find('location').html();
					event['location_street'] = marker.find('location').find('street').text();
					event['location_province'] = marker.find('location').find('province').text();
					event['location_name'] = marker.find('location').find('name').text();
					event['location_city'] = marker.find('location').find('city').text();
					event['permalink'] = details_url = marker.find('permalink').text();
					event['thumbnail'] = marker.find('thumbnail_url:first').text();

					// Event einfügen
					var address = event['location_street']+", "+event['location_city']+" "+event['location_province'];
					eventArray.push( address );
					insertEvent(event,i-5*($('#eventoni_subpage').attr('value')-0));
				}

				// es werden genau 5 Events angezeigt
				if(i == 5*(($('#eventoni_subpage').attr('value')-0)+1)){
					return false;
				}
			});

			if($(response).find("event").length == 0){
				$('#eventoni_map').css('display', 'none');
				$('#eventoni_form').append('<div id="eventoni_status">Keine Events gefunden</div>');
			} else {
				$('#eventoni_map').css('display', 'block');
				ticker(0);
				if( eventoniMap != null && eventoniMap !== undefined && eventArray != null){
					centerMapOnAddress(eventArray[0]);
					addMarkersAddress(eventArray);
				}
			}

			// Pagination initialisieren
			var page  = $($(response)[1]).attr('page');
			var pages = $($(response)[1]).attr('pages');
			var total = $($(response)[1]).attr('total');
			paginationDisplay(page, pages, total);


		},'XML');
	}

	function paginationDisplay(page, pages, total){
		if(page == 1 && $('#eventoni_subpage').attr('value') == 0) {
			$('#eventoni_last').css('display', 'none');
		} else {
			$('#eventoni_last').css('display', 'inline');
		}
		if(page == pages && total%10-(($('#eventoni_subpage').attr('value')-0)+1)*5 <= 0 ) {
			$('#eventoni_next').css('display', 'none');
		} else {
			$('#eventoni_next').css('display', 'inline');
		}
	}

	// Event Handler für Pagination (vor/zurück)
	function initPaginationHandlers(){
		$('#eventoni_last').click(function(){
			if($('#eventoni_subpage').attr('value') == 0) {
				$('#eventoni_subpage').attr('value', 1);
			} else {
				$('#eventoni_subpage').attr('value', 0);
				$('#eventoni_page').attr('value', ($('#eventoni_page').attr('value')-0)-1);
			}
			eventoni_search(getSearchData());
		}).hover(function(){
			$(this).css('cursor','pointer');
		});
		$('#eventoni_next').click(function(){
			if($('#eventoni_subpage').attr('value') == 0) {
				$('#eventoni_subpage').attr('value', 1);
			} else {
				$('#eventoni_subpage').attr('value', 0);
				$('#eventoni_page').attr('value', ($('#eventoni_page').attr('value')-0)+1);
			}
			eventoni_search(getSearchData());
		}).hover(function(){
			$(this).css('cursor','pointer');
		});
	}

	// sammelt für die Pagination die nötigen Daten zum Suchen
	function getSearchData(){
		data = '';
		if(longitude != false && latitude != false){
			data = $('#eventoni_form').serialize();
			data += '&longitude='+longitude+'&latitude='+latitude;
			data += '&action=do_eventoni_ajax_search';
		} else {
			data = $('#eventoni_form').serialize();
			data += '&action=do_eventoni_ajax_search';
		}
		return data;
	}

	// Events in den Ticker einfügen
	function insertEvent(event, i) {
		var me = $('#top5_'+i);
		me.css('display', 'block');
		me.find('.link').attr('href', event['permalink']);
		me.find('.image').attr('src', event['thumbnail']);
		me.find('h3 a').html(event['title']);
		me.find('.texte').html(event['start_date']+" , "+event['start_time'].replace(/:[0-9][0-9]$/,'')+" Uhr<br/>"+event['location_name']+" , "+event['location_city']);
		me.find('.facebook_link').attr('href', 'http://www.facebook.com/sharer.php?u='+event['permalink']+'&t=Dieses Event musst Du gesehen haben:' );
		me.find('.twitter_link').attr('href', 'http://twitter.com/home?status=Dieses Event musst Du gesehen haben: '+ event['permalink']);
	}

	// Daten für die Umkreissuche laden
	function getUserCity(){
		$.get(eventoni_ajax_url+'?action=eventoni_get_visitor_location', function(response) {

			city = $(response).find('City').html()

			latitude = $(response).find('Latitude').html();
			longitude = $(response).find('Longitude').html();
			$('#eventoni_umkreis').html(city);
			$('#eventoni_umkreissuche').css('display', 'block');

			eventoni_visitor_location = latitude+':'+longitude;

			$('#eventoni_umkreissuche').click(function(event){
				event.stopPropagation;
				event.preventDefault();

				data = 'longitude='+longitude+'&latitude='+latitude;
				data += '&action=do_eventoni_ajax_search';
				eventoni_search(data);
			});
		});
	}

	var hovered = false;
	var timeout = null;
	var selected = 0;
	$(document).ready(function() {

		eventoniSetDefaultFormValues();

		$('#eventoni_form input[type=text]').focus(function(){
			eventoniUnsetDefaultFormValues();
		}).blur(function(){
			eventoniSetDefaultFormValues();
		});

		$('#eventoni_form').submit(function(){
			eventoniUnsetDefaultFormValues();
		});

		$('#eventoni_erweiterte_suche').hover(function(){
			$(this).css('cursor', 'pointer');
		}).click(function(){
			$('#eventoni_erweitert').toggle();
		});

		if( document.getElementById("eventoni_map") ){
			google.setOnLoadCallback(initialize);
		}

		$('#eventoni_container').css('display','block');
		eventoni_search(getSearchData());
		initPaginationHandlers();

		if( $('#eventoni_umkreissuche').length ){
			getUserCity();
		}

		$('#top5_2, #top5_3, #top5_4, #top5_5').removeClass('big').addClass('small');
		$('#top5_1, #top5_2, #top5_3, #top5_4, #top5_5').hover(
			function(){
				$('#top5_1, #top5_2, #top5_3, #top5_4, #top5_5').removeClass('big').addClass('small');
				$(this).removeClass('small').addClass('big');
				clearTimeout(timeout);
				hovered = true;

				//Google Maps
				var id = $(this).attr('id');
				if( typeof eventoniMap != 'undefined' ){
					centerMapOnAddress(eventArray[id.substr(id.length-1,id.length)-1]);
				}
			}, function(){
				var id = $(this).attr('id');
				selected = id.substr(id.length-1,id.length);
				selected = (selected-0)-1;
				hovered = false;
				ticker(selected);
			}
		);

		// falls Cursor über Karte soll Ticker stehenbleiben
		$('#eventoni_map').hover(
			function(){
				clearTimeout(timeout);
				hovered = true;
			},
			function(){
				var id = $('.top5tsr.big').attr('id');
				selected = id.substr(id.length-1,id.length);
				selected = (selected-0)-1;
				hovered = false;
				ticker(selected);
			}
		);

		// Suche
		$('#eventoni_form').submit(function(event){
			event.stopPropagation;
			event.preventDefault();

			data = $(this).serialize();
			data += '&action=do_eventoni_ajax_search';
			eventoni_search(data);

			//latitude = false;
			//longitude = false;

			return false;
		});
	});

	function eventoniSetDefaultFormValues(){
		var was  = $('#eventoni_was').attr('value');
		var wo   = $('#eventoni_wo').attr('value');
		var wann = $('#eventoni_wann').attr('value');

		if(was == '' && wo == '' && wann == '') {
			$('#eventoni_was').attr('value', 'z.B. Konzert');
			$('#eventoni_wo').attr('value', 'z.B. Erzhausen');
			$('#eventoni_wann').attr('value', 'z.B. heute');
		}
	}

	function eventoniUnsetDefaultFormValues(){
		var was  = $('#eventoni_was').attr('value');
		var wo   = $('#eventoni_wo').attr('value');
		var wann = $('#eventoni_wann').attr('value');
		if(was == 'z.B. Konzert' && wo == 'z.B. Erzhausen' && wann == 'z.B. heute') {
			$('#eventoni_was').attr('value', '');
			$('#eventoni_wo').attr('value', '');
			$('#eventoni_wann').attr('value', '');
		}
	}


});
