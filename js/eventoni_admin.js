
jQuery(function ($) {
	write_timeout = null;
	$(document).ready(function(){

		// testweise wird nach 5s Bearbeitungszeit der Request für die Events ausgeführt
		// nur aufrufen wenn nötig
		if( $('#eventoni_widget').length ){
			write_timeout = window.setTimeout(suggestEvents, 5000);

			$('#refresh_events').click( function(event){
				event.preventDefault();
				event.stopPropagation();
				clearTimeout(write_timeout);
				suggestEvents();
			});
			$('#refresh_added_events').click( function(event){
				event.preventDefault();
				event.stopPropagation();
				clearTimeout(write_timeout);
				getAddedEvents();
			});
			$('#eventoni_widget').hover( function(event){
				clearTimeout(write_timeout);
			}, function(event){
				write_timeout = window.setTimeout(suggestEvents, 20000);
			});

			getAddedEvents();
		}
	});

	function getAddedEvents(){
		$('#eventoni_added_events_content').html('<div id="eventoni_loading"><img src="'+eventoni_plugin_url+'img/loadinfo.net.gif" /></div>');
		var postId = $('#post_ID').val();
		if( postId === undefined){
			return;
		}
		data = '&action=get_added_events&post_id='+postId;
		jQuery.post(ajaxurl, data, function(response) {
			$('#eventoni_added_events_content').html('');
			if( response ){
				var counter = 0;
				var eventsString = '';
				for( var i = 0; i < response.length; i++){
					counter++;
					eventsString += response[i]+'-';
					if( counter == 10 || i == response.length-1 ){
						eventsString = eventsString.substring(0, eventsString.length-1);
						getEventsById( eventsString );
						counter = 0;
						eventsString = '';
					}
				}
			}
		},'json');
	}

	function addEventAsCustomField( eventId ){
		var postId = $('#post_ID').val();
		data = '&action=add_event_as_custom_field&post_id='+postId+'&event_id='+eventId;
		jQuery.post(ajaxurl, data, function(response) {
		});
	}

	function deleteEventCustomField( eventId ){
		var postId = $('#post_ID').val();
		data = '&action=delete_event_custom_field&post_id='+postId+'&event_id='+eventId;
		jQuery.post(ajaxurl, data, function(response) {
			getAddedEvents();
		});
	}

	// schlägt dem Autor Events zum Einbinden in den Inhalt vor
	function suggestEvents(){
		content = getContent();
		getEvents(content);
		write_timeout = window.setTimeout(suggestEvents, 20000);
	}


	function getContent(){
		var rich = (typeof tinyMCE != "undefined") && tinyMCE.activeEditor && !tinyMCE.activeEditor.isHidden(), ed, content;
		// checken ob tinyMCE verwendet wird
		if ( rich ) {
			// tinyMCE-Methoden benutzen um Inhalt auszulesen
			ed = tinyMCE.activeEditor;
			if ( !(ed.plugins.spellchecker && ed.plugins.spellchecker.active) ) {
				if ( 'mce_fullscreen' == ed.id )
					tinyMCE.get('content').setContent(ed.getContent({format : 'raw'}), {format : 'raw'});
				content = tinyMCE.get('content').getContent();
			}
		} else {
			// Textarea kann direkt ausgelesen werden
			content =  jQuery("#content").val();
		}

		// Titel zweimal anhängen um für dessen Wörter höhere Relevanz zu erreichen
		content = $('#post #title').val()+' '+content+' '+$('#post #title').val();

		return content;
	}

	function getEventsById( events ){
		data = '&action=get_events_by_id&event_ids='+events;
		jQuery.post(ajaxurl, data, function(response) {
			response = response.replace(/title>/g, "mytitle>");
			if (jQuery.browser.msie) {
			    var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
			    xmlDoc.loadXML(response);
			    response = xmlDoc;
			}
			var i = 0;
			$(response).find("event").each(function() {
				i++;
				var marker = $(this);
				var event = new Array();
				event['id'] = marker.find('id:first').text();
				event['title'] = marker.find('mytitle').text();
				event['description'] = marker.find('description').text();
				event['start_date'] = marker.find('start_date').text();
				event['start_time'] = marker.find('start_time').text();
				event['location_name'] = marker.find('location').find('name').text();
				event['location_city'] = marker.find('location').find('city').text();
				event['permalink'] = details_url = marker.find('permalink').text();
				event['thumbnail'] = marker.find('thumbnail_url:first').text();

				// Event einfügen
				insertEventIntoAddedEventsBox(event,i);

				// es werden 5 Events angezeigt
				if(i == 5) return;
			});
		},"XML");
	}

	function getEvents(content){
		// Ajax-Request zum Auslesen der Events
		var data = '&action=suggest_events&content='+content;
		$('#eventoni_content').html('<div id="eventoni_loading"><img src="'+eventoni_plugin_url+'img/loadinfo.net.gif" /></div>');
		jQuery.post(ajaxurl, data, function(response) {
			response = response.replace(/title>/g, "mytitle>");
			$('#eventoni_content').html('');

			if (jQuery.browser.msie) {
			    var xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
			    xmlDoc.loadXML(response);
			    response = xmlDoc;
			}

			var i = 0;
			$(response).find("event").each(function() {
				i++;
				var marker = $(this);
				var event = new Array();
				event['id'] = marker.find('id:first').text();
				event['title'] = marker.find('mytitle').text();
				event['description'] = marker.find('description').text();
				event['start_date'] = marker.find('start_date').text();
				event['start_time'] = marker.find('start_time').text();
				event['location_name'] = marker.find('location').find('name').text();
				event['location_city'] = marker.find('location').find('city').text();
				event['permalink'] = details_url = marker.find('permalink').text();
				event['thumbnail'] = marker.find('thumbnail_url:first').text();

				// Event einfügen
				insertEvent(event,i);

				// es werden 5 Events angezeigt
				if(i == 5) return;
			});
		},"XML");
	}

	function checkContent(){
		write_timeout = window.setTimeout(getContent, 1000);
	}

	function insertEvent(event,i){
		var eventId = event['id'];
		var data = '<a class="event-item-link" href="#">'
			+ ' <div style="min-height: 60px;" class="event-item" id="event-item-'+event['id']+'">'
			+ '	<img width="60px" height"60px" align="left" src="'+event['thumbnail']+'"/>'
			+ '	 <div class="event-item-content">'
			+ '		<div class="event-item-content-date">'+event['start_date']+', '+event['start_time']+' Uhr</div>'
			+ '		<div class="event-item-content-city">'+event['location_city']+'</div>'
			+ '	 </div>'
			+ '	 <div class="event-item-content-name"><b>'+event['title']+'</b></div>'
			+ '</div>'
		    +'</a>';
		$('#eventoni_content').append(data);
		$('#event-item-'+event['id']).click( function(event){
			event.preventDefault();
			event.stopPropagation();
			addEventAsCustomField(eventId);
		});
	}

	function insertEventIntoAddedEventsBox(event,i){
		var eventId = event['id'];
		var data = '<a class="event-item-link" href="#">'
			+ ' <div style="min-height: 60px;" class="event-item" id="event-item-added-events-'+event['id']+'">'
			+ '	<img width="60px" height"60px" align="left" src="'+event['thumbnail']+'"/>'
			+ '	 <div class="event-item-content">'
			+ '		<div class="event-item-content-date">'+event['start_date']+', '+event['start_time']+' Uhr</div>'
			+ '		<div class="event-item-content-city">'+event['location_city']+'</div>'
			+ '	 </div>'
			+ '	 <div class="event-item-content-name"><b>'+event['title']+'</b></div>'
		    +'</a>';
		$('#eventoni_added_events_content').append(data);

		$('#event-item-added-events-'+event['id']).click( function(event){
			event.preventDefault();
			event.stopPropagation();
			deleteEventCustomField(eventId);
		});
	}
});