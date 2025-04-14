
// tracking for current image
var mirador;
var currentManifestID = '';
var currentCanvasIndex = -1;

// process image request and decide what response is needed
function requestImage(requestedManifestID, requestedCanvasIndex) {
	// show MS viewer
	$('#msViewer').removeClass('d-none');
	$('footer').addClass('withMsViewer');

	// new manifest (MS) requested
	if (requestedManifestID != currentManifestID) {
		currentManifestID = requestedManifestID;
		showMsViewer(requestedManifestID, requestedCanvasIndex);
	}
	// new canvas index (page) requested
	else if (requestedCanvasIndex != currentCanvasIndex) {
		// update canvasIndex only
		currentCanvasIndex = requestedCanvasIndex;

		/*
		NEED TO FIGURE OUT HOW TO CHANGE CANVAS WITHOUT RELOADING WINDOW
		*/
		showMsViewer(requestedManifestID, requestedCanvasIndex);

	}
	// otherwise do nothing
	else {
		return false;
	}
}

// load a new manifest
function showMsViewer(newManifestID, newCanvasIndex) {

	// set up Mirador instance
	mirador = Mirador.viewer({
		id: "miradorViewer",
		window: {
			allowClose: false,
			allowFullscreen: true,
			allowMaximize: false,
			allowTopMenuButton: false,
			defaultView: 'single',
			sideBarPanel: 'annotations',
			highlightAllAnnotations: true
		},
		windows: [
			{ 
			id: 'mainViewer',
			manifestId: newManifestID, 
			canvasIndex: newCanvasIndex,
			maximized: true,
			thumbnailNavigationPosition: 'off',
			view: 'single'
			}	
		],
		  workspace: {
			showZoomControls: true
		},
		workspaceControlPanel: {
			 enabled: false
		}
	
	});
}

// hide MS viewer
function hideMsViewer() {
	$('#msViewer').addClass('d-none');
	$('footer').removeClass('withMsViewer');
}

// check all/none in collection lists
function check(baseTextID, action) {
	console.log('here')
	$('#collections' + baseTextID + ' input:checkbox').prop('checked', action);
	highlightUpdateButton();
}

// activate the update button (after a change of form values)
function highlightUpdateButton() {
	$('#btnUpdateCollections').removeClass('btn-secondary');
	$('#btnUpdateCollections').addClass('btn-primary');
}
