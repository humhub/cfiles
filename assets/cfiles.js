
/**
 * 
 * @returns {undefined}
 */
function showHideBtns() {
    var checkCounter = 0;
    $('.multiselect').each(function () {
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

/**
 * Inits File List after it's loaded/reloaded
 *  
 * @returns {undefined}
 */
function initFileList() {
    $('.multiselect').change(function () {
        showHideBtns();
    });
    $('.allselect').change(function () {
        $('.multiselect').each(function () {
            $(this).prop('checked', $('.allselect').prop('checked'));
        });
        showHideBtns();
    });
    $("#bs-table tr").contextMenu({
        getMenuSelector: function (invokedOn, settings) {
            itemId = invokedOn.closest('tr').data('id');
            if (itemId.indexOf("folder-") != -1) {
                return '#contextMenuFolder';
            } else {
                return '#contextMenuFile';
            }

        },
        menuSelected: function (invokedOn, selectedMenu) {
            action = selectedMenu.data('action');

            // e.g. file-53
            itemId = invokedOn.closest('tr').data('id');

            $temp = itemId.split("\-");

            // file or folder
            itemType = $temp[0];

            // Id of Folder / File
            itemRealId = $temp[1];


            if (action == 'delete') {
                $.ajax({
                    url: cfilesDeleteUrl,
                    type: 'POST',
                    data: {
                        'selected[]': itemId,
                    },
                }).done(function (html) {
                    $("#fileList").html(html);
                });
            } else if (action == 'edit' && itemType == 'folder') {
                $('#globalModal').modal({
                    remote: cfilesEditFolderUrl.replace('--folderId--', itemRealId)
                });
            } else if (action == 'download') {
                url = invokedOn.closest('tr').data('url');
                document.location.href = url;
            } else {
                alert("Unkown action " + action);
            }
        }
    });

}

$(function () {
    /**
     * Install uploader
     */
    $('#fileupload').fileupload({
        url: cfilesUploadUrl,
        dataType: 'json',
        done: function (e, data) {
        	console.log(data);
            $.each(data.result.files, function (index, file) {
                $('#fileList').html(file.fileList);
            	$('#errorList').html($('#errorListHidden').html());
            });
        },
        fail: function (e, data) {
           	$('#errorListHidden').append('<li>'+data.jqXHR.responseJSON.message+'</li>');
           	$('#errorList').html($('#errorListHidden').html());
        },
        start: function (e, data) {
           	$('#errorListHidden').empty();
        },
        progressall: function (e, data) {
            var progress = parseInt(data.loaded / data.total * 100, 10);
            if (progress != 100) {
                $('#progress').show();
                $('#progress .progress-bar').css(
                        'width',
                        progress + '%'
                        );
            } else {
                $('#progress').hide();
            }
        }
    }).prop('disabled', !$.support.fileInput)
            .parent().addClass($.support.fileInput ? undefined : 'disabled');
});


/**
 *  Context Menu
 */
(function ($, window) {

    $.fn.contextMenu = function (settings) {

        return this.each(function () {

            // Open context menu
            $(this).on("contextmenu", function (e) {
                // return native menu if pressing control
                if (e.ctrlKey)
                    return;

                // Make sure all menus are hidden
                $('.contextMenu').hide();

                menuSelector = settings.getMenuSelector.call(this, $(e.target));

                //open menu
                var $menu = $(menuSelector)
                        .data("invokedOn", $(e.target))
                        .show()
                        .css({
                            position: "absolute",
                            left: getMenuPosition(e.clientX, 'width', 'scrollLeft'),
                            top: getMenuPosition(e.clientY, 'height', 'scrollTop')
                        })
                        .off('click')
                        .on('click', 'a', function (e) {
                            $menu.hide();

                            var $invokedOn = $menu.data("invokedOn");
                            var $selectedMenu = $(e.target);

                            settings.menuSelected.call(this, $invokedOn, $selectedMenu);
                        });

                return false;
            });

            //make sure menu closes on any click
            $(document).click(function () {
                $('.contextMenu').hide();
            });
        });

        function getMenuPosition(mouse, direction, scrollDir) {
            var win = $(window)[direction](),
                    scroll = $(window)[scrollDir](),
                    menu = $(settings.menuSelector)[direction](),
                    position = mouse + scroll;

            // opening menu would pass the side of the page
            if (mouse + menu > win && menu < mouse)
                position -= menu;

            return position;
        }

    };
})(jQuery, window);
