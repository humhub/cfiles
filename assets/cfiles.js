/**
 * 
 * @returns {undefined}
 */
function showHideBtns() {
	var checkCounter = 0;
	$('.multiselect').each(function() {
		if ($(this).prop('checked')) {
			checkCounter++;
		}
	});
	if (checkCounter != 0) {
		$('.selectedOnly').show();
		$('.chkCnt').html(checkCounter);
	} else {
		$('.selectedOnly').hide();
	}
}

jQuery.urlParam = function(name){
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results==null){
       return null;
    }
    else{
       return results[1] || 0;
    }
}

/**
 * Inits File List after it's loaded/reloaded
 * 
 * @returns {undefined}
 */
function initFileList() {
	$('.multiselect').change(function() {
		showHideBtns();
	});
	$('.allselect').change(function() {
		$('.multiselect').each(function() {
			$(this).prop('checked', $('.allselect').prop('checked'));
		});
		showHideBtns();
	});
	$("#bs-table tr").contextMenu(
			{
				getMenuSelector : function(invokedOn, settings) {
					itemId = invokedOn.closest('tr').data('type');
					switch (itemId) {
					case "all-posted-files":
						return '#contextMenuAllPostedFiles';
					case "folder":
						return '#contextMenuFolder';
					case "image":
						return '#contextMenuImage';
					default:
						return '#contextMenuFile';
					}
				},
				menuSelected : function(invokedOn, selectedMenu) {

					action = selectedMenu.data('action');
					// file or folder
					itemType = invokedOn.closest('tr').data('type');
					// e.g. file-53
					itemId = invokedOn.closest('tr').data('id');
					parentId = jQuery.urlParam('fid') === null ? 0 : jQuery.urlParam('fid');
					// default if the id is not specified
					itemRealId = undefined;
					if (jQuery.type(itemId) === "string") {
						$temp = itemId.split("\-");
						// id of file or folder
						if ($temp.length >= 2) {
							itemRealId = $temp[1];
						}
					}

					switch (action) {
					case 'delete':
						$.ajax({
							url : cfilesDeleteUrl,
							type : 'POST',
							data : {
								'selected[]' : itemId,
							},
						}).done(function(html) {
							$("#fileList").html(html);
						});
						break;
					case 'edit':
						$('#globalModal').modal(
								{
									remote : cfilesEditFolderUrl.replace(
											'--folderId--', itemRealId)
								});
						break;
					case 'download':
						url = invokedOn.closest('tr').data('url');
						document.location.href = url;
						break;
					case 'move-files':
						$.ajax({
							url : cfilesMoveUrl,
							type : 'POST',
							data : {
								'selected[]' : itemId,
							},
						}).done(function(html) {
							$("#globalModal").html(html);
							$("#globalModal").modal("show");
							openDirectory(parentId);
							selectDirectory(parentId);
						});
						break;
					case 'show':
						previewLink = invokedOn.closest('tr').find('.preview-link');
						previewLink.trigger("click");
						break;
					default:
						alert("Unkown action " + action);
						break;
					}
				}
			});
}

function updateLog(messages) {
	if ($.isArray(messages)) {
		$.each(messages, function(index, message) {
			$('#hiddenLog').append('<li>' + message + '</li>');
		});
	} else {
		$('#hiddenLog').append('<li>' + messages + '</li>');
	}
	$('#log').html($('#hiddenLog').html());
}

$(function() {

	/**
	 * Install uploader
	 */
	$('#fileupload').fileupload({
		url : cfilesUploadUrl,
		dataType : 'json',
		done : function(e, data) {
			$.each(data.result.files, function(index, file) {
				$('#fileList').html(file.fileList);
			});
			if (data.result.log) {
				updateLog(data.result.logmessages);
			}
		},
		fail : function(e, data) {
			updateLog(data.jqXHR.responseJSON.message);
		},
		start : function(e, data) {
			$('#hiddenLog').empty();
		},
		progressall : function(e, data) {
			var progress = parseInt(data.loaded / data.total * 100, 10);
			if (progress != 100) {
				$('#progress').show();
				$('#progress .progress-bar').css('width', progress + '%');
			} else {
				$('#progress').hide();
			}
		}
	}).prop('disabled', !$.support.fileInput).parent().addClass(
			$.support.fileInput ? undefined : 'disabled');

});

/**
 * Context Menu
 */
(function($, window) {

	$.fn.contextMenu = function(settings) {

		return this.each(function() {

			// Open context menu
			$(this).on(
					"contextmenu",
					function(e) {
						// return native menu if pressing control
						if (e.ctrlKey)
							return;

						// Make sure all menus are hidden
						$('.contextMenu').hide();

						menuSelector = settings.getMenuSelector.call(this,
								$(e.target));

						// open menu
						var $menu = $(menuSelector).data("invokedOn",
								$(e.target)).show().css(
								{
									position : "absolute",
									left : getMenuPosition(e.clientX, 'width',
											'scrollLeft'),
									top : getMenuPosition(e.clientY, 'height',
											'scrollTop')
								}).off('click').on(
								'click',
								'a',
								function(e) {
									$menu.hide();

									var $invokedOn = $menu.data("invokedOn");
									var $selectedMenu = $(e.target);

									settings.menuSelected.call(this,
											$invokedOn, $selectedMenu);
								});

						return false;
					});

			// make sure menu closes on any click
			$(document).click(function() {
				$('.contextMenu').hide();
			});
		});

		function getMenuPosition(mouse, direction, scrollDir) {
			var win = $(window)[direction](), scroll = $(window)[scrollDir](), menu = $(settings.menuSelector)[direction]
					(), position = mouse + scroll;

			// opening menu would pass the side of the page
			if (mouse + menu > win && menu < mouse)
				position -= menu;

			return position;
		}

	};
})(jQuery, window);
