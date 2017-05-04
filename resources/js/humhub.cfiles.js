humhub.module('cfiles', function (module, require, $) {

    var client = require('client');
    var modal = require('ui.modal');
    var additions = require('ui.additions');
    var Widget = require('ui.widget').Widget;
    var object = require('util').object;
    var string = require('util').string;
    var loader = require('ui.loader');
    var event = require('event');

    var FolderView = function (node, options) {
        Widget.call(this, node, options);
    };

    object.inherits(FolderView, Widget);

    FolderView.prototype.init = function () {
        this.$fileList = this.$.find('#fileList');
        this.fid = this.$.data('fid');

        this.initFileList();
        this.initEvents();
        this.initContextMenu();
    };

    FolderView.prototype.initEvents = function () {
        var that = this;
        event.on('humhub:file:created.cfiles', function (evt, files) {
            if (!object.isArray(files)) {
                files = [files];
            }

            var data = {guids: []};
            files.forEach(function (file) {
                data.guids.push(file.guid);
            });

            that.loader();
            client.post(that.options.importUrl, {data: data}).then(function (response) {
                if (response.success) {
                    that.reloadFileList();
                } else if (response.error) {
                    module.log.error(response.error, true);
                }
            }).catch(function (e) {
                module.log.error(e, true);
            }).finally(function () {
                that.loader(false);
            });
        });

        event.on('humhub:file:modified.cfiles', function (evt, files) {
            that.reloadFileList().then(function () {
                module.log.success('success.saved');
            });
        });

        this.$.on('change', '.multiselect', function () {
            that.checkButtons();
        });

        this.$.on('change', '.allselect', function () {
            that.$fileList.find('.multiselect').each(function () {
                $(this).prop('checked', $('.allselect').prop('checked'));
            });
            that.checkButtons();
        });

    };

    FolderView.prototype.initFileList = function () {
        additions.observe($('#fileList'));
    };

    FolderView.prototype.initContextMenu = function () {
        var that = this;
        $("#bs-table tr").contextMenu({
            getMenuSelector: function ($invokedOn, settings) {
                var type = $invokedOn.closest('tr').data('cfiles-type');
                switch (type) {
                    case "folder-posted":
                        return '#contextMenuAllPostedFiles';
                    case "folder":
                        return '#contextMenuFolder';
                    case "image":
                        return '#contextMenuImage';
                    default:
                        return '#contextMenuFile';
                }
            },
            menuSelected: function ($invokedOn, selectedMenu, evt) {
                evt.preventDefault();
                var item = that.getItemByNode($invokedOn);

                if (!item) {
                    module.log.error('Could not determine item for given context node', $invokedOn);
                }

                var action = selectedMenu.data('action');

                switch (action) {
                    case 'delete':
                        that.deleteItem(item);
                        break;
                    case 'edit-folder':
                        item.edit();
                        break;
                    case 'edit-file':
                        item.edit();
                        break;
                    case 'download':
                        document.location.href = item.url;
                        break;
                    case 'show-image':
                        item.$.find('.preview-link').click();
                        break;
                    case 'show-post':
                        document.location.href = item.wallUrl;
                        break;
                    case 'move-files':
                        item.move();
                        break;
                    case 'zip':
                        // TODO: Implement
                        var url = that.options.cfilesDownloadArchiveUrl.replace('--folderId--', item.id.split('_')[1]);
                        document.location.href = url;
                        break;
                    default:
                        module.log.warn("Unkown action " + action);
                        break;
                }
            }
        });
    };

    FolderView.prototype.deleteItem = function (item) {
        var that = this;
        this.confirmDelete(1).then(function (confirmed) {
            if (confirmed) {
                item.loader();
                client.post({
                    url: that.options.deleteUrl,
                    dataType: 'html',
                    data: {
                        'selected[]': item.id
                    }
                }).then(function (response) {
                    that.replaceFileList(response.html);
                }).catch(function (e) {
                    module.log.error(e, true);
                }).finally(function () {
                    item.loader(false);
                });
            }
        });
    };

    FolderView.prototype.replaceFileList = function (html) {
        this.$fileList.html(html);
        this.checkButtons();
        this.initContextMenu();
    };

    FolderView.prototype.confirmDelete = function (count) {
        var confirmOptions = {
            'body': string.template(module.text('confirm.delete'), {'number': count}),
            'header': module.text('confirm.delete.header'),
            'confirmText': module.text('confirm.delete.confirmText')
        };

        return modal.confirm(confirmOptions);
    };

    FolderView.prototype.checkButtons = function () {
        // Update selection menu and selection related buttons
        var checkCounter = this.getSelectionCount();
        if (checkCounter) {
            this.$.find('.selectedOnly').show();
            this.$.find('.chkCnt').html(checkCounter);
        } else {
            this.$.find('.selectedOnly').hide();
        }

        // Hide some nodes in case there are no items.
        if (!this.hasItems()) {
            this.$.find('.hasItems').removeClass('visible').addClass('hidden');
        } else {
            this.$.find('.hasItems').removeClass('hidden').addClass('visible');
        }

        if (!$('#folder-dropdown').children('.visible').length) {
            $('#directory-toggle').hide();
        } else {
            $('#directory-toggle').show();
        }

    };

    FolderView.prototype.deleteSelection = function (evt) {
        var that = this;
        this.confirmDelete(that.getSelectionCount()).then(function (confirmed) {
            if (confirmed) {
                that.loader();
                // submit selected item id's to action-url
                client.submit(evt, {'dataType': 'html'}).then(function (response) {
                    that.replaceFileList(response.html);
                }).catch(function (e) {
                    module.log.error(e, true);
                }).finally(function () {
                    that.loader(false);
                });
            }
        });
    };

    FolderView.prototype.zipSelection = function (evt) {
        var $form = $('#cfiles-form');
        $form.attr("action", evt.$trigger.data('action-url'));
        $form.attr("method", "post");
        $form.submit();

        evt.finish();
    };

    FolderView.prototype.reloadFileList = function () {
        var that = this;
        this.loader();
        return client.get(this.options.reloadFileListUrl).then(function (response) {
            that.replaceFileList(response.output);
        }).catch(function (e) {
            module.log.error(e, true);
        }).finally(function () {
            that.loader(false);
        });
    };

    FolderView.prototype.loader = function (show) {
        var $loader = this.$.find('#cfiles-crumb');
        if (show === false) {
            loader.reset($loader);
            return;
        }

        loader.set($loader, {
            'size': '8px',
            'css': {
                'padding': '0px',
                width: '60px'
            },
            'wrapper': '<li></li>'
        });
    };

    FolderView.prototype.hasItems = function () {
        return this.$fileList.find('[data-cfiles-item]').length > 0;
    };

    FolderView.prototype.getSelectionCount = function () {
        return this.$fileList.find('.multiselect:checked').length;
    };

    FolderView.prototype.getSelectedItems = function () {
        var that = this;
        var result = [];
        this.$fileList.find('.multiselect:checked').each(function () {
            var item = that.getItemByNode(this);
            if (item) {
                result.push(item);
            }
        });
    };

    /**
     * Set upload source
     * @param {Upload} uploadComponent
     * @returns {undefined}
     */
    FolderView.prototype.setSource = function (uploadComponent) {
        var that = this;
        this.source = uploadComponent;
        this.source.on('humhub:file:uploadEnd', function (evt, response) {
            that.replaceFileList(response.result.fileList);
            if (response.result.infomessages && response.result.infomessages.length) {
                that.statusInfo(response.result.infomessages);
            }
        });
    };

    FolderView.prototype.reload = function () {
        // TODO
    };

    FolderView.prototype.add = function (file) {
        //Nothing todo here
    };

    FolderView.prototype.getItemByNode = function (node) {
        var $node = (node instanceof $) ? node : $(node);
        var item = Widget.closest($node);
        if (item instanceof FileItem) {
            return item;
        }

        return;
    };

    var FileItem = function (node, options) {
        Widget.call(this, node, options);
    };

    object.inherits(FileItem, Widget);

    FileItem.prototype.init = function () {
        this.id = this.$.data('cfiles-item');
        this.type = this.$.data('cfiles-type');
        this.url = this.$.data('cfiles-url');
        this.wallUrl = this.$.data('cfiles-wall-url');
        this.editUrl = this.$.data('cfiles-edit-url');
        this.moveUrl = this.$.data('cfiles-move-url');
    };

    FileItem.prototype.loader = function (show) {
        var $loader = this.$.find('.title');
        if (show === false) {
            loader.reset($loader);
            return;
        }

        loader.set($loader, {
            'size': '8px',
            'css': {
                'padding': '0px',
                width: '60px'
            }
        });
    };

    FileItem.prototype.edit = function () {
        modal.global.load({'url': this.editUrl});
    };

    FileItem.prototype.move = function () {
        var that = this;
        var fid = $('#cfiles-folderView').data('fid') || 0;

        modal.global.post({
            'url': that.moveUrl,
            'data': {
                'selected[]': that.id
            },
            'dataType': 'html'
        }).then(function () {
            _getDirectoryList().select(fid);
        }).catch(function (e) {
            module.log.error(e, true);
        });
    };

    var _getDirectoryList = function () {
        return Widget.instance('#cfiles-directory-list');
    };

    var move = function (evt) {
        client.submit(evt, {dataType: 'html'}).then(function (response) {
            modal.global.setDialog(response.html);
            var fid = $('#cfiles-folderView').data('fid');
            _getDirectoryList().select(fid);
            modal.global.show();
        }).catch(function (e) {
            module.log.error(e, true);
        });
    };

    var DirectoryList = function (node, options) {
        Widget.call(this, node, options);
    };

    object.inherits(DirectoryList, Widget);

    DirectoryList.prototype.init = function () {
        $('.directory-list li:last-child').addClass('last-child');
        $('.directory-list ul ul').hide();

        // handle selecting folders
        $('.directory-list .selectable').click(function () {
            $('.directory-list .selectedFolder').removeClass('selectedFolder');
            $(this).addClass('selectedFolder');
            $('#input-hidden-selectedFolder').val($(this).attr('id'));
        });

        // handle open close subfolders
        $('.directory-list li:has(ul)')
                .addClass('hassub').find('>span, >a').click(function () {
            var parentFolder = $(this).parent();

            if (parentFolder.hasClass('expand')) {
                parentFolder.removeClass('expand').find('>ul').slideUp(
                        '200');
            } else {
                parentFolder.addClass('expand').find('>ul')
                        .slideDown('200');
            }
        });
    };

    DirectoryList.prototype.select = function (id) {
        this.openDirectory(id);
        this.selectDirectory(id);
    };

    DirectoryList.prototype.openDirectory = function ($id) {
        // optinal $id, set to 0 if undefined
        $id = $id || 0;
        var folder = $('#' + $id).parent();
        do {
            folder.addClass('expand');
            folder.find('>ul').slideDown('100');
            folder = folder.parent().closest('li');
        } while (folder.hasClass('hassub'))
    };

    DirectoryList.prototype.selectDirectory = function ($id) {
        // optinal $id, set to 0 if undefined
        $id = $id || 0;
        var item = $('#' + $id);
        item.addClass('selectedFolder');
        $('#input-hidden-selectedFolder').val($id);
    }

    var unload = function () {
        event.off('humhub:file:created.cfiles');
        event.off('humhub:file:modified.cfiles');
    };

    module.export({
        unload: unload,
        move: move,
        FolderView: FolderView,
        FileItem: FileItem,
        DirectoryList: DirectoryList
    });
});