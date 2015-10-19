function initDirectoryList() {
	$('.directory-list li:last-child').addClass('last-child');
	$('.directory-list ul ul').hide();
	
	// handle selecting folders
	$('.directory-list .selectable').click(
			function() {
				$('.directory-list .selectedFolder').removeClass(
						'selectedFolder');
				$(this).addClass('selectedFolder');
			});
	
	// handle open close subfolders
	$('.directory-list li:has(ul)').addClass('hassub').find('>span, >a').click(
			function() {
				parentFolder = $(this).parent();

				if (parentFolder.hasClass('expand')) {
					parentFolder.removeClass('expand').find('>ul').slideUp(
							'200');
				} else {
					parentFolder.addClass('expand').find('>ul')
							.slideDown('200');
				}
			});
}
