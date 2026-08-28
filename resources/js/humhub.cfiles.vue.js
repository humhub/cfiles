/*!
 * AUTO-GENERATED FILE — do not edit.
 * Compiled from cfiles/vue/ via `grunt build-vue --module=cfiles`.
 * See docs/develop/ui-js-vuejs.md
 */
(function(vue, vue$1) {
  "use strict";
  const _export_sfc = (sfc, props) => {
    const target = sfc.__vccOpts || sfc;
    for (const [key, val] of props) {
      target[key] = val;
    }
    return target;
  };
  const SORTS = ["name", "size", "updatedAt"];
  const _sfc_main$6 = {
    props: {
      path: { type: Array, default: () => [] },
      sort: { type: String, default: "name" },
      order: { type: String, default: "asc" },
      canWrite: { type: Boolean, default: false },
      /** Which display is in force, one of `FolderListingService::VIEWS`. */
      view: { type: String, default: "list" },
      /**
       * Server-rendered `<li>` entries for the file handlers a module contributed — "new
       * spreadsheet", "import from …". They stay server-rendered because they are menu
       * entries carrying legacy `data-action-click` attributes and build their own URLs from
       * the request; the same arrangement the core's `UploadField` uses.
       */
      createHandlersHtml: { type: String, default: "" },
      /**
       * The crumb currently being dragged over, or `undefined` when nothing is.
       *
       * NOT `null` for "nothing": the top-level crumb's own id IS null, so a null default
       * would make `dropTargetId === crumb.id` permanently true and paint it as an active
       * drop target at all times.
       */
      dropTargetId: { type: Number, default: void 0 },
      folderUrl: { type: Function, required: true }
    },
    emits: [
      "open",
      "sort",
      "view",
      "create-folder",
      "pick-files",
      "crumb-drag-over",
      "crumb-drag-leave",
      "crumb-drop"
    ],
    computed: {
      /**
       * The path the API sends, preceded by the container's top level. That level has no
       * folder record, so it is not something the server could have sent — it is a client
       * entry with a null id, which is exactly what every endpoint takes for "no parent".
       */
      crumbs() {
        return [{ id: null, title: null }].concat(this.path);
      },
      breadcrumbLabel() {
        return vue.i18n.t("CfilesModule.base", "Folder path");
      },
      sortLabel() {
        return vue.i18n.t("CfilesModule.base", "Sort by");
      },
      addFolderLabel() {
        return vue.i18n.t("CfilesModule.base", "Add folder");
      },
      addFilesLabel() {
        return vue.i18n.t("CfilesModule.base", "Add files");
      },
      handlersLabel() {
        return vue.i18n.t("CfilesModule.base", "More ways to add a file");
      },
      rootLabel() {
        return vue.i18n.t("CfilesModule.base", "Files");
      },
      viewLabel() {
        return vue.i18n.t("CfilesModule.base", "View");
      },
      viewOptions() {
        return [
          { value: "list", icon: "list", label: vue.i18n.t("CfilesModule.base", "List") },
          { value: "tiles", icon: "th", label: vue.i18n.t("CfilesModule.base", "Tiles") }
        ];
      },
      sortLabels() {
        return {
          name: vue.i18n.t("CfilesModule.base", "Name"),
          size: vue.i18n.t("CfilesModule.base", "Size"),
          updatedAt: vue.i18n.t("CfilesModule.base", "Updated")
        };
      },
      /** What the trigger reads: the column in force and which way it points. */
      activeSortLabel() {
        return (this.sortLabels[this.sort] ?? this.sortLabels.name) + (this.order === "asc" ? " ↑" : " ↓");
      },
      sortEntries() {
        return SORTS.map((key, index) => ({
          id: "sort-" + key,
          sortOrder: (index + 1) * 10,
          // The active column is marked, so picking it again reads as "reverse it".
          label: this.sortLabels[key] + (this.sort === key ? this.order === "asc" ? " ↑" : " ↓" : ""),
          onClick: () => this.$emit("sort", key)
        }));
      }
    },
    methods: {
      crumbTitle(crumb) {
        return crumb.id === null ? this.rootLabel : crumb.title;
      },
      onCrumbClick(event, crumb) {
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
          return;
        }
        event.preventDefault();
        this.$emit("open", crumb.id);
      },
      onCrumbDragOver(event, crumb, index) {
        if (index === this.crumbs.length - 1) {
          return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
        this.$emit("crumb-drag-over", crumb.id);
      },
      onCrumbDrop(event, crumb, index) {
        if (index === this.crumbs.length - 1) {
          return;
        }
        event.preventDefault();
        event.stopPropagation();
        this.$emit("crumb-drop", crumb.id);
      }
    }
  };
  const _hoisted_1$6 = { class: "cfiles-toolbar d-flex flex-wrap align-items-center gap-2" };
  const _hoisted_2$5 = ["aria-label"];
  const _hoisted_3$5 = { class: "breadcrumb mb-0" };
  const _hoisted_4$4 = ["onDragover", "onDrop"];
  const _hoisted_5$4 = { key: 0 };
  const _hoisted_6$4 = ["href", "onClick"];
  const _hoisted_7$3 = ["aria-label"];
  const _hoisted_8$3 = ["aria-pressed", "title", "aria-label", "onClick"];
  const _hoisted_9$3 = { class: "d-none d-sm-inline ms-1" };
  const _hoisted_10$3 = { class: "btn-group btn-group-sm cfiles-add-files" };
  const _hoisted_11$2 = { class: "d-none d-sm-inline ms-1" };
  const _hoisted_12$1 = {
    type: "button",
    class: "btn btn-accent dropdown-toggle dropdown-toggle-split",
    "data-bs-toggle": "dropdown",
    "aria-haspopup": "true",
    "aria-expanded": "false"
  };
  const _hoisted_13$1 = { class: "visually-hidden" };
  const _hoisted_14 = ["innerHTML"];
  function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_DropdownMenu = vue$1.resolveComponent("DropdownMenu");
    const _directive_additions = vue$1.resolveDirective("additions");
    return vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_1$6, [
      vue$1.createElementVNode("nav", {
        class: "cfiles-breadcrumb flex-grow-1 min-width-0",
        "aria-label": $options.breadcrumbLabel
      }, [
        vue$1.createElementVNode("ol", _hoisted_3$5, [
          (vue$1.openBlock(true), vue$1.createElementBlock(vue$1.Fragment, null, vue$1.renderList($options.crumbs, (crumb, index) => {
            return vue$1.openBlock(), vue$1.createElementBlock("li", {
              key: crumb.id ?? "top",
              class: vue$1.normalizeClass(["breadcrumb-item", { active: index === $options.crumbs.length - 1, "cfiles-crumb-drop": $props.dropTargetId === crumb.id }]),
              onDragover: ($event) => $options.onCrumbDragOver($event, crumb, index),
              onDragleave: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("crumb-drag-leave")),
              onDrop: ($event) => $options.onCrumbDrop($event, crumb, index)
            }, [
              index === $options.crumbs.length - 1 ? (vue$1.openBlock(), vue$1.createElementBlock("span", _hoisted_5$4, vue$1.toDisplayString($options.crumbTitle(crumb)), 1)) : (vue$1.openBlock(), vue$1.createElementBlock("a", {
                key: 1,
                href: $props.folderUrl(crumb.id),
                onClick: ($event) => $options.onCrumbClick($event, crumb)
              }, vue$1.toDisplayString($options.crumbTitle(crumb)), 9, _hoisted_6$4))
            ], 42, _hoisted_4$4);
          }), 128))
        ])
      ], 8, _hoisted_2$5),
      vue$1.createElementVNode("div", {
        class: "btn-group btn-group-sm cfiles-view-switch",
        role: "group",
        "aria-label": $options.viewLabel
      }, [
        (vue$1.openBlock(true), vue$1.createElementBlock(vue$1.Fragment, null, vue$1.renderList($options.viewOptions, (option) => {
          return vue$1.openBlock(), vue$1.createElementBlock("button", {
            key: option.value,
            type: "button",
            class: vue$1.normalizeClass(["btn btn-light", { active: $props.view === option.value }]),
            "aria-pressed": $props.view === option.value ? "true" : "false",
            title: option.label,
            "aria-label": option.label,
            onClick: ($event) => _ctx.$emit("view", option.value)
          }, [
            vue$1.createElementVNode("i", {
              class: vue$1.normalizeClass("fa fa-" + option.icon),
              "aria-hidden": "true"
            }, null, 2)
          ], 10, _hoisted_8$3);
        }), 128))
      ], 8, _hoisted_7$3),
      vue$1.createVNode(_component_DropdownMenu, {
        "menu-id": "cfiles.sort",
        entries: $options.sortEntries,
        "toggle-aria-label": $options.sortLabel,
        "toggle-class": "btn btn-light btn-sm dropdown-toggle",
        "root-class": "cfiles-sort-menu",
        "align-end": true
      }, {
        toggle: vue$1.withCtx(() => [
          vue$1.createTextVNode(vue$1.toDisplayString($options.activeSortLabel), 1)
        ]),
        _: 1
      }, 8, ["entries", "toggle-aria-label"]),
      $props.canWrite ? (vue$1.openBlock(), vue$1.createElementBlock(vue$1.Fragment, { key: 0 }, [
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-light btn-sm",
          onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("create-folder"))
        }, [
          _cache[3] || (_cache[3] = vue$1.createElementVNode("i", {
            class: "fa fa-folder",
            "aria-hidden": "true"
          }, null, -1)),
          vue$1.createElementVNode("span", _hoisted_9$3, vue$1.toDisplayString($options.addFolderLabel), 1)
        ]),
        vue$1.createElementVNode("div", _hoisted_10$3, [
          vue$1.createElementVNode("button", {
            type: "button",
            class: "btn btn-accent",
            onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("pick-files"))
          }, [
            _cache[4] || (_cache[4] = vue$1.createElementVNode("i", {
              class: "fa fa-upload",
              "aria-hidden": "true"
            }, null, -1)),
            vue$1.createElementVNode("span", _hoisted_11$2, vue$1.toDisplayString($options.addFilesLabel), 1)
          ]),
          $props.createHandlersHtml ? (vue$1.openBlock(), vue$1.createElementBlock(vue$1.Fragment, { key: 0 }, [
            vue$1.createElementVNode("button", _hoisted_12$1, [
              vue$1.createElementVNode("span", _hoisted_13$1, vue$1.toDisplayString($options.handlersLabel), 1)
            ]),
            vue$1.withDirectives(vue$1.createElementVNode("ul", {
              class: "dropdown-menu dropdown-menu-end",
              innerHTML: $props.createHandlersHtml
            }, null, 8, _hoisted_14), [
              [_directive_additions]
            ])
          ], 64)) : vue$1.createCommentVNode("", true)
        ])
      ], 64)) : vue$1.createCommentVNode("", true)
    ]);
  }
  const BrowserToolbar = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6]]);
  const SUPPRESSED_CORE_ENTRIES = ["edit", "delete", "permalink", "pin", "move", "archive"];
  const MIME_ICONS = {
    "mime-image": "fa-file-image-o",
    "mime-pdf": "fa-file-pdf-o",
    "mime-archive": "fa-file-archive-o",
    "mime-audio": "fa-file-audio-o",
    "mime-video": "fa-file-video-o",
    "mime-text": "fa-file-text-o",
    "mime-code": "fa-file-code-o",
    "mime-excel": "fa-file-excel-o",
    "mime-word": "fa-file-word-o",
    "mime-powerpoint": "fa-file-powerpoint-o"
  };
  const WEEK_IN_SECONDS = 7 * 24 * 60 * 60;
  const RELATIVE_UNITS = [
    ["day", 24 * 60 * 60],
    ["hour", 60 * 60],
    ["minute", 60],
    ["second", 1]
  ];
  const mimeIconClass = (item) => MIME_ICONS[item.mimeIcon] || "fa-file-o";
  const formatSize = (size) => {
    const units = ["B", "KB", "MB", "GB", "TB"];
    let value = size || 0;
    let unit = 0;
    while (value >= 1024 && unit < units.length - 1) {
      value /= 1024;
      unit++;
    }
    return (unit === 0 ? value : value.toFixed(1)) + " " + units[unit];
  };
  const formatTimestamp = (stamp) => {
    if (!stamp) {
      return "";
    }
    const date = new Date(stamp);
    const locale = vue.getConfig("i18n").language || void 0;
    const seconds = Math.round((Date.now() - date.getTime()) / 1e3);
    if (seconds >= 0 && seconds < WEEK_IN_SECONDS) {
      const [unit, size] = RELATIVE_UNITS.find(([, unitSize]) => seconds >= unitSize) ?? ["second", 1];
      return new Intl.RelativeTimeFormat(locale, { numeric: "auto" }).format(-Math.floor(seconds / size), unit);
    }
    return new Intl.DateTimeFormat(locale, { dateStyle: "medium" }).format(date);
  };
  const itemMeta = (item, { description = true } = {}) => {
    const parts = [];
    if (item.type === "folder") {
      if (typeof item.itemCount === "number") {
        parts.push(vue.i18n.t("CfilesModule.base", "{count, plural, =0{empty} one{# item} other{# items}}", {
          count: item.itemCount
        }));
      }
    } else {
      parts.push(formatSize(item.size));
    }
    parts.push(formatTimestamp(item.updatedAt || item.createdAt));
    if (description && item.description) {
      parts.push(item.description);
    }
    return parts.filter(Boolean).join(" · ");
  };
  const _sfc_main$5 = {
    props: {
      item: { type: Object, required: true },
      selected: { type: Boolean, default: false },
      selectable: { type: Boolean, default: false },
      draggable: { type: Boolean, default: false },
      dropTarget: { type: Boolean, default: false },
      entries: { type: Array, default: () => [] },
      folderUrl: { type: Function, required: true },
      /**
       * `recordId => {total, liked, canLike}` for the whole page, as the listing payload
       * carries it. Empty where the like module is off, which is what hides the button.
       */
      likeStates: { type: Object, default: () => ({}) }
    },
    emits: ["open", "toggle-select", "drag-start", "drag-end", "drop-on"],
    data() {
      return { SUPPRESSED_CORE_ENTRIES };
    },
    computed: {
      isFolder() {
        return this.item.type === "folder";
      },
      isPrivate() {
        return this.item.visibility === 0;
      },
      displayTitle() {
        return this.item.title;
      },
      linkUrl() {
        var _a;
        return this.isFolder ? this.folderUrl(this.item.id) : ((_a = this.item.link) == null ? void 0 : _a.url) || this.item.url || "#";
      },
      /** Attributes the file's link needs — the download hooks, or the modal target. */
      linkAttributes() {
        var _a;
        return this.isFolder ? {} : ((_a = this.item.link) == null ? void 0 : _a.attributes) || {};
      },
      iconClass() {
        return this.isFolder ? "fa fa-folder cfiles-icon-folder" : "fa " + mimeIconClass(this.item) + " cfiles-icon-file";
      },
      meta() {
        return itemMeta(this.item);
      },
      /** This row's like state, or null when there is nothing to render a button from. */
      likeState() {
        const state = this.likeStates[this.item.recordId];
        return state && (state.canLike || state.total > 0) ? state : null;
      },
      privateLabel() {
        return vue.i18n.t("CfilesModule.base", "Private");
      },
      selectLabel() {
        return vue.i18n.t("CfilesModule.base", "Select {name}", { name: this.item.title });
      },
      actionsLabel() {
        return vue.i18n.t("base", "Actions");
      }
    },
    methods: {
      /**
       * The row is one big click target for the item it shows — a file browser where only
       * the name is clickable makes every open a precision exercise.
       *
       * Everything inside the row that means something else keeps its own click: the select
       * checkbox, the context menu, the creator's profile link, and the title link itself,
       * which is also what a click here ends up going through.
       */
      onRowClick(event) {
        if (event.target.closest("a, button, input, label, .dropdown-menu")) {
          return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
          return;
        }
        const selection = window.getSelection ? window.getSelection() : null;
        if (selection && !selection.isCollapsed && this.$el.contains(selection.anchorNode)) {
          return;
        }
        this.openItem();
      },
      /**
       * Raises this item's context menu where the cursor is, the way the platform's legacy
       * `$.fn.contextMenu` did for server-rendered lists (see `humhub.ui.additions.js`).
       */
      onContextMenu(event) {
        if (event.ctrlKey) {
          return;
        }
        if (event.target.closest(".dropdown-menu")) {
          return;
        }
        event.preventDefault();
        this.$refs.controls.open(event);
      },
      openItem() {
        if (this.isFolder) {
          this.$emit("open", this.item);
          return;
        }
        if (this.$refs.titleLink) {
          this.$refs.titleLink.click();
        }
      },
      onOpen(event) {
        if (!this.isFolder) {
          return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
          return;
        }
        event.preventDefault();
        this.$emit("open", this.item);
      },
      onDragStart(event) {
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData("text/plain", this.item.type + ":" + this.item.id);
        this.$emit("drag-start", this.item);
      },
      onDragOver(event) {
        if (!this.isFolder) {
          return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
      },
      onDragLeave() {
        if (this.isFolder) {
          this.$emit("drag-end");
        }
      },
      onDrop(event) {
        if (!this.isFolder) {
          return;
        }
        event.preventDefault();
        event.stopPropagation();
        this.$emit("drop-on", this.item);
      }
    }
  };
  const _hoisted_1$5 = ["draggable"];
  const _hoisted_2$4 = {
    key: 0,
    class: "cfiles-row-select"
  };
  const _hoisted_3$4 = ["checked", "aria-label"];
  const _hoisted_4$3 = { class: "cfiles-row-icon" };
  const _hoisted_5$3 = ["src"];
  const _hoisted_6$3 = { class: "flex-grow-1 min-width-0" };
  const _hoisted_7$2 = { class: "mb-0 d-flex align-items-center gap-1" };
  const _hoisted_8$2 = ["href"];
  const _hoisted_9$2 = ["title", "aria-label"];
  const _hoisted_10$2 = { class: "mb-0 text-truncate cfiles-row-meta" };
  const _hoisted_11$1 = {
    key: 1,
    class: "cfiles-row-social"
  };
  const _hoisted_12 = { class: "cfiles-row-creator" };
  const _hoisted_13 = { class: "cfiles-row-controls" };
  function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_LikeButton = vue$1.resolveComponent("LikeButton");
    const _component_UserImage = vue$1.resolveComponent("UserImage");
    const _component_ContentControls = vue$1.resolveComponent("ContentControls");
    return vue$1.openBlock(), vue$1.createElementBlock("div", {
      class: vue$1.normalizeClass(["cfiles-row d-flex align-items-center gap-2", { "cfiles-row-drop": $props.dropTarget, selected: $props.selected }]),
      draggable: $props.draggable,
      onClick: _cache[2] || (_cache[2] = (...args) => $options.onRowClick && $options.onRowClick(...args)),
      onContextmenu: _cache[3] || (_cache[3] = (...args) => $options.onContextMenu && $options.onContextMenu(...args)),
      onDragstart: _cache[4] || (_cache[4] = (...args) => $options.onDragStart && $options.onDragStart(...args)),
      onDragend: _cache[5] || (_cache[5] = ($event) => _ctx.$emit("drag-end")),
      onDragover: _cache[6] || (_cache[6] = (...args) => $options.onDragOver && $options.onDragOver(...args)),
      onDragleave: _cache[7] || (_cache[7] = (...args) => $options.onDragLeave && $options.onDragLeave(...args)),
      onDrop: _cache[8] || (_cache[8] = (...args) => $options.onDrop && $options.onDrop(...args))
    }, [
      $props.selectable ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_2$4, [
        vue$1.createElementVNode("input", {
          type: "checkbox",
          class: "form-check-input",
          checked: $props.selected,
          "aria-label": $options.selectLabel,
          onChange: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("toggle-select", $props.item))
        }, null, 40, _hoisted_3$4)
      ])) : vue$1.createCommentVNode("", true),
      vue$1.createElementVNode("div", _hoisted_4$3, [
        $props.item.previewUrl ? (vue$1.openBlock(), vue$1.createElementBlock("img", {
          key: 0,
          src: $props.item.previewUrl,
          alt: "",
          class: "cfiles-thumb"
        }, null, 8, _hoisted_5$3)) : (vue$1.openBlock(), vue$1.createElementBlock("i", {
          key: 1,
          class: vue$1.normalizeClass($options.iconClass),
          "aria-hidden": "true"
        }, null, 2))
      ]),
      vue$1.createElementVNode("div", _hoisted_6$3, [
        vue$1.createElementVNode("h4", _hoisted_7$2, [
          vue$1.createElementVNode("a", vue$1.mergeProps({
            ref: "titleLink",
            href: $options.linkUrl
          }, $options.linkAttributes, {
            class: "text-truncate",
            onClick: _cache[1] || (_cache[1] = (...args) => $options.onOpen && $options.onOpen(...args))
          }), vue$1.toDisplayString($options.displayTitle), 17, _hoisted_8$2),
          $options.isPrivate ? (vue$1.openBlock(), vue$1.createElementBlock("i", {
            key: 0,
            class: "fa fa-lock text-muted flex-shrink-0",
            title: $options.privateLabel,
            "aria-label": $options.privateLabel
          }, null, 8, _hoisted_9$2)) : vue$1.createCommentVNode("", true)
        ]),
        vue$1.createElementVNode("h5", _hoisted_10$2, vue$1.toDisplayString($options.meta), 1)
      ]),
      $options.likeState ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_11$1, [
        vue$1.createVNode(_component_LikeButton, {
          "record-id": $props.item.recordId,
          "like-count": $options.likeState.total,
          "current-user-liked": $options.likeState.liked
        }, null, 8, ["record-id", "like-count", "current-user-liked"])
      ])) : vue$1.createCommentVNode("", true),
      vue$1.createElementVNode("div", _hoisted_12, [
        $props.item.creator ? (vue$1.openBlock(), vue$1.createBlock(_component_UserImage, vue$1.mergeProps({ key: 0 }, $props.item.creator, { size: 21 }), null, 16)) : vue$1.createCommentVNode("", true)
      ]),
      vue$1.createElementVNode("div", _hoisted_13, [
        vue$1.createVNode(_component_ContentControls, {
          ref: "controls",
          "content-id": $props.item.contentId,
          "view-context": "browser",
          entries: $props.entries,
          suppress: $data.SUPPRESSED_CORE_ENTRIES,
          context: { item: $props.item },
          "toggle-class": "nav-link dropdown-toggle cfiles-row-toggle",
          "toggle-aria-label": $options.actionsLabel
        }, null, 8, ["content-id", "entries", "suppress", "context", "toggle-aria-label"])
      ])
    ], 42, _hoisted_1$5);
  }
  const ItemRow = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5]]);
  const _sfc_main$4 = {
    props: {
      item: { type: Object, required: true },
      selected: { type: Boolean, default: false },
      selectable: { type: Boolean, default: false },
      draggable: { type: Boolean, default: false },
      dropTarget: { type: Boolean, default: false },
      entries: { type: Array, default: () => [] },
      folderUrl: { type: Function, required: true }
    },
    emits: ["open", "toggle-select", "drag-start", "drag-end", "drop-on"],
    data() {
      return { SUPPRESSED_CORE_ENTRIES };
    },
    computed: {
      isFolder() {
        return this.item.type === "folder";
      },
      isPrivate() {
        return this.item.visibility === 0;
      },
      linkUrl() {
        var _a;
        return this.isFolder ? this.folderUrl(this.item.id) : ((_a = this.item.link) == null ? void 0 : _a.url) || this.item.url || "#";
      },
      /** Attributes the file's link needs — the download hooks, or the modal target. */
      linkAttributes() {
        var _a;
        return this.isFolder ? {} : ((_a = this.item.link) == null ? void 0 : _a.attributes) || {};
      },
      iconClass() {
        return this.isFolder ? "fa fa-folder cfiles-icon-folder" : "fa " + mimeIconClass(this.item) + " cfiles-icon-file";
      },
      /** Without the description: there is no room for it under a thumbnail. */
      meta() {
        return itemMeta(this.item, { description: false });
      },
      privateLabel() {
        return vue.i18n.t("CfilesModule.base", "Private");
      },
      selectLabel() {
        return vue.i18n.t("CfilesModule.base", "Select {name}", { name: this.item.title });
      },
      actionsLabel() {
        return vue.i18n.t("base", "Actions");
      }
    },
    methods: {
      /** Same right-click menu as a row's — see `ItemRow.onContextMenu()`. */
      onContextMenu(event) {
        if (event.ctrlKey) {
          return;
        }
        if (event.target.closest(".dropdown-menu")) {
          return;
        }
        event.preventDefault();
        this.$refs.controls.open(event);
      },
      onOpen(event) {
        if (!this.isFolder) {
          return;
        }
        if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
          return;
        }
        event.preventDefault();
        this.$emit("open", this.item);
      },
      onDragStart(event) {
        event.dataTransfer.effectAllowed = "move";
        event.dataTransfer.setData("text/plain", this.item.type + ":" + this.item.id);
        this.$emit("drag-start", this.item);
      },
      onDragOver(event) {
        if (!this.isFolder) {
          return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = "move";
      },
      onDragLeave() {
        if (this.isFolder) {
          this.$emit("drag-end");
        }
      },
      onDrop(event) {
        if (!this.isFolder) {
          return;
        }
        event.preventDefault();
        event.stopPropagation();
        this.$emit("drop-on", this.item);
      }
    }
  };
  const _hoisted_1$4 = ["draggable"];
  const _hoisted_2$3 = { class: "cfiles-tile-actions" };
  const _hoisted_3$3 = ["checked", "aria-label"];
  const _hoisted_4$2 = ["href", "title"];
  const _hoisted_5$2 = ["src"];
  const _hoisted_6$2 = { class: "cfiles-tile-caption" };
  const _hoisted_7$1 = { class: "cfiles-tile-name d-flex align-items-center gap-1" };
  const _hoisted_8$1 = ["href", "title"];
  const _hoisted_9$1 = ["title", "aria-label"];
  const _hoisted_10$1 = { class: "cfiles-tile-meta" };
  function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_ContentControls = vue$1.resolveComponent("ContentControls");
    return vue$1.openBlock(), vue$1.createElementBlock("div", {
      class: vue$1.normalizeClass(["cfiles-tile", { "cfiles-tile-drop": $props.dropTarget, selected: $props.selected }]),
      draggable: $props.draggable,
      onContextmenu: _cache[3] || (_cache[3] = (...args) => $options.onContextMenu && $options.onContextMenu(...args)),
      onDragstart: _cache[4] || (_cache[4] = (...args) => $options.onDragStart && $options.onDragStart(...args)),
      onDragend: _cache[5] || (_cache[5] = ($event) => _ctx.$emit("drag-end")),
      onDragover: _cache[6] || (_cache[6] = (...args) => $options.onDragOver && $options.onDragOver(...args)),
      onDragleave: _cache[7] || (_cache[7] = (...args) => $options.onDragLeave && $options.onDragLeave(...args)),
      onDrop: _cache[8] || (_cache[8] = (...args) => $options.onDrop && $options.onDrop(...args))
    }, [
      vue$1.createElementVNode("div", _hoisted_2$3, [
        $props.selectable ? (vue$1.openBlock(), vue$1.createElementBlock("input", {
          key: 0,
          type: "checkbox",
          class: "form-check-input",
          checked: $props.selected,
          "aria-label": $options.selectLabel,
          onChange: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("toggle-select", $props.item))
        }, null, 40, _hoisted_3$3)) : vue$1.createCommentVNode("", true),
        vue$1.createVNode(_component_ContentControls, {
          ref: "controls",
          "content-id": $props.item.contentId,
          "view-context": "browser",
          entries: $props.entries,
          suppress: $data.SUPPRESSED_CORE_ENTRIES,
          context: { item: $props.item },
          "toggle-class": "nav-link dropdown-toggle cfiles-tile-toggle",
          "toggle-aria-label": $options.actionsLabel
        }, null, 8, ["content-id", "entries", "suppress", "context", "toggle-aria-label"])
      ]),
      vue$1.createElementVNode("a", vue$1.mergeProps({ href: $options.linkUrl }, $options.linkAttributes, {
        class: "cfiles-tile-preview",
        title: $props.item.title,
        onClick: _cache[1] || (_cache[1] = (...args) => $options.onOpen && $options.onOpen(...args))
      }), [
        $props.item.previewUrl ? (vue$1.openBlock(), vue$1.createElementBlock("img", {
          key: 0,
          src: $props.item.previewUrl,
          alt: ""
        }, null, 8, _hoisted_5$2)) : (vue$1.openBlock(), vue$1.createElementBlock("i", {
          key: 1,
          class: vue$1.normalizeClass($options.iconClass),
          "aria-hidden": "true"
        }, null, 2))
      ], 16, _hoisted_4$2),
      vue$1.createElementVNode("div", _hoisted_6$2, [
        vue$1.createElementVNode("div", _hoisted_7$1, [
          vue$1.createElementVNode("a", vue$1.mergeProps({ href: $options.linkUrl }, $options.linkAttributes, {
            class: "cfiles-tile-title",
            title: $props.item.title,
            onClick: _cache[2] || (_cache[2] = (...args) => $options.onOpen && $options.onOpen(...args))
          }), vue$1.toDisplayString($props.item.title), 17, _hoisted_8$1),
          $options.isPrivate ? (vue$1.openBlock(), vue$1.createElementBlock("i", {
            key: 0,
            class: "fa fa-lock text-muted flex-shrink-0",
            title: $options.privateLabel,
            "aria-label": $options.privateLabel
          }, null, 8, _hoisted_9$1)) : vue$1.createCommentVNode("", true)
        ]),
        vue$1.createElementVNode("span", _hoisted_10$1, vue$1.toDisplayString($options.meta), 1)
      ])
    ], 42, _hoisted_1$4);
  }
  const ItemTile = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4]]);
  const loadItems = (containerId, parent, { sort, order, view, page, pageSize } = {}) => {
    const params = {};
    if (parent) {
      params.parent = parent;
    }
    if (sort) {
      params.sort = sort;
      params.order = order || "asc";
    }
    if (view) {
      params.view = view;
    }
    if (page) {
      params.page = page;
    }
    if (pageSize) {
      params.pageSize = pageSize;
    }
    return vue.client.get(vue.apiUrl("cfiles/" + containerId + "/items", params));
  };
  const createFolder = (containerId, parent, attributes) => vue.client.post(vue.apiUrl("cfiles/" + containerId + "/folders"), {
    data: { ...attributes, parent }
  });
  const updateItem = (item, attributes) => vue.client.put(vue.apiUrl("cfiles/" + item.type + "/" + item.id), { data: attributes });
  const moveItems = (containerId, items, targetFolderId) => vue.client.post(vue.apiUrl("cfiles/items/move"), {
    data: { containerId, items: items.map(descriptor), targetFolderId }
  });
  const deleteItems = (items) => vue.client.post(vue.apiUrl("cfiles/items/delete"), { data: { items: items.map(descriptor) } });
  const uploadFiles = (containerId, parent, files, onProgress) => {
    const form = new FormData();
    Array.prototype.forEach.call(files, (file) => form.append("files[]", file));
    if (parent) {
      form.append("parent", parent);
    }
    return vue.client.post(vue.apiUrl("cfiles/" + containerId + "/files"), {
      data: form,
      // Hand the FormData to the browser untouched: jQuery must neither serialize it nor
      // set a Content-Type, or the multipart boundary is lost.
      processData: false,
      contentType: false,
      dataType: "json",
      xhr: () => {
        const xhr = jQuery.ajaxSettings.xhr();
        if (onProgress && xhr.upload) {
          xhr.upload.addEventListener("progress", (event) => {
            if (event.lengthComputable && event.total > 0) {
              onProgress(Math.round(event.loaded / event.total * 100));
            }
          });
        }
        return xhr;
      }
    });
  };
  const descriptor = (item) => ({ type: item.type, id: item.id });
  const keyOf = (item) => item.type + ":" + item.id;
  const _sfc_main$3 = {
    components: { ItemRow, ItemTile },
    props: {
      items: { type: Array, default: () => [] },
      selection: { type: Array, default: () => [] },
      selectable: { type: Boolean, default: false },
      draggable: { type: Boolean, default: false },
      dropTargetKey: { type: String, default: null },
      hasMore: { type: Boolean, default: false },
      loading: { type: Boolean, default: false },
      loadingMore: { type: Boolean, default: false },
      canWrite: { type: Boolean, default: false },
      view: { type: String, default: "list" },
      entriesFor: { type: Function, required: true },
      folderUrl: { type: Function, required: true },
      /** Handed straight to `ItemRow` — see `socialProps` below. */
      likeStates: { type: Object, default: () => ({}) }
    },
    emits: [
      "open",
      "toggle-select",
      "toggle-all",
      "load-more",
      "drag-start",
      "drag-end",
      "drop-on",
      "move-selection",
      "delete-selection"
    ],
    computed: {
      itemComponent() {
        return this.view === "tiles" ? "ItemTile" : "ItemRow";
      },
      /**
       * The like state map, bound only in the row list.
       *
       * A tile has no room for a like link and `ItemTile` declares no such prop, so binding
       * it there would put a stray attribute on every tile's root element rather than
       * nothing at all.
       */
      socialProps() {
        return this.view === "tiles" ? {} : { likeStates: this.likeStates };
      },
      containerClass() {
        return this.view === "tiles" ? "cfiles-tiles" : "hh-list cfiles-list";
      },
      allSelected() {
        return this.items.length > 0 && this.selection.length === this.items.length;
      },
      someSelected() {
        return this.selection.length > 0 && !this.allSelected;
      },
      selectAllLabel() {
        return vue.i18n.t("CfilesModule.base", "Select all");
      },
      selectionLabel() {
        return vue.i18n.t("CfilesModule.base", "{count, plural, one{# selected} other{# selected}}", {
          count: this.selection.length
        });
      },
      moveLabel() {
        return vue.i18n.t("CfilesModule.base", "Move");
      },
      deleteLabel() {
        return vue.i18n.t("CfilesModule.base", "Delete");
      },
      emptyTitle() {
        return vue.i18n.t("CfilesModule.base", "This folder is empty.");
      },
      emptyHint() {
        return this.canWrite ? vue.i18n.t("CfilesModule.base", "Drop files here or use the buttons above.") : vue.i18n.t("CfilesModule.base", "Unfortunately you have no permission to upload/edit files.");
      },
      moreLabel() {
        return vue.i18n.t("base", "Show more");
      },
      loadingLabel() {
        return vue.i18n.t("base", "Loading...");
      }
    },
    watch: {
      // `indeterminate` is a DOM property, not an attribute, so it cannot be bound in the
      // template.
      someSelected: {
        immediate: true,
        handler(partial) {
          this.$nextTick(() => {
            if (this.$refs.selectAll) {
              this.$refs.selectAll.indeterminate = partial;
            }
          });
        }
      }
    },
    methods: {
      keyOf,
      isSelected(item) {
        return this.selection.indexOf(keyOf(item)) !== -1;
      }
    }
  };
  const _hoisted_1$3 = {
    key: 0,
    class: "cfiles-list-header d-flex align-items-center gap-2"
  };
  const _hoisted_2$2 = ["checked", "aria-label"];
  const _hoisted_3$2 = { class: "text-muted small flex-grow-1" };
  const _hoisted_4$1 = { class: "d-none d-sm-inline ms-1" };
  const _hoisted_5$1 = { class: "d-none d-sm-inline ms-1" };
  const _hoisted_6$1 = {
    key: 1,
    class: "text-muted small"
  };
  const _hoisted_7 = {
    key: 2,
    class: "cfiles-empty text-center text-muted p-4"
  };
  const _hoisted_8 = { class: "mb-0" };
  const _hoisted_9 = { class: "mb-0" };
  const _hoisted_10 = {
    key: 3,
    class: "text-center p-2"
  };
  const _hoisted_11 = ["disabled"];
  function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
    return vue$1.openBlock(), vue$1.createElementBlock("div", null, [
      $props.selectable && $props.items.length ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_1$3, [
        vue$1.createElementVNode("input", {
          ref: "selectAll",
          type: "checkbox",
          class: "form-check-input",
          checked: $options.allSelected,
          "aria-label": $options.selectAllLabel,
          onChange: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("toggle-all"))
        }, null, 40, _hoisted_2$2),
        $props.selection.length ? (vue$1.openBlock(), vue$1.createElementBlock(vue$1.Fragment, { key: 0 }, [
          vue$1.createElementVNode("span", _hoisted_3$2, vue$1.toDisplayString($options.selectionLabel), 1),
          vue$1.createElementVNode("button", {
            type: "button",
            class: "btn btn-light btn-sm",
            onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("move-selection"))
          }, [
            _cache[9] || (_cache[9] = vue$1.createElementVNode("i", {
              class: "fa fa-arrows",
              "aria-hidden": "true"
            }, null, -1)),
            vue$1.createElementVNode("span", _hoisted_4$1, vue$1.toDisplayString($options.moveLabel), 1)
          ]),
          vue$1.createElementVNode("button", {
            type: "button",
            class: "btn btn-danger btn-sm",
            onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("delete-selection"))
          }, [
            _cache[10] || (_cache[10] = vue$1.createElementVNode("i", {
              class: "fa fa-trash",
              "aria-hidden": "true"
            }, null, -1)),
            vue$1.createElementVNode("span", _hoisted_5$1, vue$1.toDisplayString($options.deleteLabel), 1)
          ])
        ], 64)) : (vue$1.openBlock(), vue$1.createElementBlock("span", _hoisted_6$1, vue$1.toDisplayString($options.selectAllLabel), 1))
      ])) : vue$1.createCommentVNode("", true),
      $props.items.length ? (vue$1.openBlock(), vue$1.createElementBlock("div", {
        key: 1,
        class: vue$1.normalizeClass($options.containerClass)
      }, [
        (vue$1.openBlock(true), vue$1.createElementBlock(vue$1.Fragment, null, vue$1.renderList($props.items, (item) => {
          return vue$1.openBlock(), vue$1.createBlock(vue$1.resolveDynamicComponent($options.itemComponent), vue$1.mergeProps({
            key: $options.keyOf(item),
            item,
            selected: $options.isSelected(item),
            selectable: $props.selectable,
            draggable: $props.draggable,
            "drop-target": $props.dropTargetKey === $options.keyOf(item),
            entries: $props.entriesFor(item),
            "folder-url": $props.folderUrl
          }, { ref_for: true }, $options.socialProps, {
            onOpen: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("open", $event)),
            onToggleSelect: _cache[4] || (_cache[4] = ($event) => _ctx.$emit("toggle-select", $event)),
            onDragStart: _cache[5] || (_cache[5] = ($event) => _ctx.$emit("drag-start", $event)),
            onDragEnd: _cache[6] || (_cache[6] = ($event) => _ctx.$emit("drag-end")),
            onDropOn: _cache[7] || (_cache[7] = ($event) => _ctx.$emit("drop-on", $event))
          }), null, 16, ["item", "selected", "selectable", "draggable", "drop-target", "entries", "folder-url"]);
        }), 128))
      ], 2)) : !$props.loading ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_7, [
        vue$1.createElementVNode("p", _hoisted_8, [
          vue$1.createElementVNode("strong", null, vue$1.toDisplayString($options.emptyTitle), 1)
        ]),
        vue$1.createElementVNode("p", _hoisted_9, vue$1.toDisplayString($options.emptyHint), 1)
      ])) : vue$1.createCommentVNode("", true),
      $props.hasMore ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_10, [
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-light btn-sm",
          disabled: $props.loadingMore,
          onClick: _cache[8] || (_cache[8] = ($event) => _ctx.$emit("load-more"))
        }, vue$1.toDisplayString($props.loadingMore ? $options.loadingLabel : $options.moreLabel), 9, _hoisted_11)
      ])) : vue$1.createCommentVNode("", true)
    ]);
  }
  const ItemList = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3]]);
  const _sfc_main$2 = {
    props: {
      show: { type: Boolean, default: false },
      contentContainerId: { type: Number, required: true },
      // Items being moved — they and their descendants are not valid targets.
      items: { type: Array, default: () => [] },
      busy: { type: Boolean, default: false },
      error: { type: String, default: null }
    },
    emits: ["close", "confirm"],
    data() {
      return { nodes: [], selectedId: null };
    },
    watch: {
      show: {
        immediate: true,
        handler(open) {
          if (open) {
            this.selectedId = void 0;
            this.nodes = [{ id: null, title: "", isTop: true, depth: 0, expanded: false, children: null }];
            this.toggle(this.nodes[0]);
          }
        }
      }
    },
    computed: {
      title() {
        return vue.i18n.t("CfilesModule.base", "Move");
      },
      intro() {
        return vue.i18n.t("CfilesModule.base", "Choose the folder to move the selection into.");
      },
      rootLabel() {
        return vue.i18n.t("CfilesModule.base", "Files");
      },
      cancelLabel() {
        return vue.i18n.t("base", "Cancel");
      },
      moveLabel() {
        return vue.i18n.t("CfilesModule.base", "Move");
      },
      movedFolderIds() {
        return this.items.filter((item) => item.type === "folder").map((item) => item.id);
      },
      flatTree() {
        const flatten = (nodes) => nodes.reduce((all, node) => {
          all.push(node);
          if (node.expanded && node.children) {
            all.push(...flatten(node.children));
          }
          return all;
        }, []);
        return flatten(this.nodes);
      }
    },
    methods: {
      select(node) {
        this.selectedId = node.id;
      },
      toggle(node) {
        if (node.expanded) {
          node.expanded = false;
          return;
        }
        node.expanded = true;
        if (node.children !== null) {
          return;
        }
        loadItems(this.contentContainerId, node.id, { pageSize: 200 }).then((payload) => {
          node.children = (payload.results || []).filter((row) => row.type === "folder").filter((row) => this.movedFolderIds.indexOf(row.id) === -1).map((row) => ({
            id: row.id,
            title: row.title,
            isTop: false,
            depth: node.depth + 1,
            expanded: false,
            children: null,
            hasChildren: row.itemCount > 0
          }));
        }).catch((e) => {
          node.children = [];
          vue.log.error(e, true);
        });
      }
    }
  };
  const _hoisted_1$2 = { class: "text-muted" };
  const _hoisted_2$1 = { class: "hh-list cfiles-move-tree" };
  const _hoisted_3$1 = ["onClick", "onKeydown"];
  const _hoisted_4 = ["onClick"];
  const _hoisted_5 = {
    key: 0,
    class: "text-danger mt-2 mb-0"
  };
  const _hoisted_6 = ["disabled"];
  function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_UiModal = vue$1.resolveComponent("UiModal");
    return vue$1.openBlock(), vue$1.createBlock(_component_UiModal, {
      show: $props.show,
      title: $options.title,
      "onUpdate:show": _cache[2] || (_cache[2] = ($event) => _ctx.$emit("close"))
    }, {
      footer: vue$1.withCtx(() => [
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-light",
          onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
        }, vue$1.toDisplayString($options.cancelLabel), 1),
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-primary",
          disabled: $data.selectedId === void 0 || $props.busy,
          onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("confirm", $data.selectedId))
        }, vue$1.toDisplayString($options.moveLabel), 9, _hoisted_6)
      ]),
      default: vue$1.withCtx(() => [
        vue$1.createElementVNode("p", _hoisted_1$2, vue$1.toDisplayString($options.intro), 1),
        vue$1.createElementVNode("div", _hoisted_2$1, [
          (vue$1.openBlock(true), vue$1.createElementBlock(vue$1.Fragment, null, vue$1.renderList($options.flatTree, (node) => {
            return vue$1.openBlock(), vue$1.createElementBlock("div", {
              key: node.id,
              class: vue$1.normalizeClass({ selected: node.id === $data.selectedId && $data.selectedId !== void 0 }),
              style: vue$1.normalizeStyle({ paddingLeft: 10 + node.depth * 18 + "px" }),
              role: "button",
              tabindex: "0",
              onClick: ($event) => $options.select(node),
              onKeydown: [
                vue$1.withKeys(vue$1.withModifiers(($event) => $options.select(node), ["prevent"]), ["enter"]),
                vue$1.withKeys(vue$1.withModifiers(($event) => $options.select(node), ["prevent"]), ["space"])
              ]
            }, [
              vue$1.createElementVNode("i", {
                class: vue$1.normalizeClass(["fa fa-fw", node.expanded ? "fa-caret-down" : node.hasChildren === false ? "" : "fa-caret-right"]),
                "aria-hidden": "true",
                onClick: vue$1.withModifiers(($event) => $options.toggle(node), ["stop"])
              }, null, 10, _hoisted_4),
              _cache[3] || (_cache[3] = vue$1.createElementVNode("i", {
                class: "fa fa-folder text-muted",
                "aria-hidden": "true"
              }, null, -1)),
              vue$1.createTextVNode(" " + vue$1.toDisplayString(node.isTop ? $options.rootLabel : node.title), 1)
            ], 46, _hoisted_3$1);
          }), 128))
        ]),
        $props.error ? (vue$1.openBlock(), vue$1.createElementBlock("p", _hoisted_5, vue$1.toDisplayString($props.error), 1)) : vue$1.createCommentVNode("", true)
      ]),
      _: 1
    }, 8, ["show", "title"]);
  }
  const MoveDialog = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2]]);
  const _sfc_main$1 = {
    components: { BrowserToolbar, ItemList, MoveDialog },
    // A top-level island declares what its whole subtree needs, not only its own messages.
    i18nCategories: ["CfilesModule.base", "base", "ContentModule.base", "UserModule.base"],
    props: {
      /** The first page, embedded by the page controller so the first paint needs no request. */
      listing: { type: Object, required: true },
      canWrite: { type: Boolean, default: false },
      /** Base URL of the browser page — `?fid=` is appended to it. */
      browseUrl: { type: String, required: true },
      /**
       * The container whose tree this is. The API addresses levels by container plus an
       * optional parent folder, because the top level has no folder record to name.
       */
      contentContainerId: { type: Number, required: true },
      /**
       * Key of an item to open the edit dialog for on mount, as `file:<id>` /
       * `folder:<id>`. A stream entry's Edit control links here rather than loading an edit
       * form of its own — this browser owns that dialog, and one form beats two.
       */
      editKey: { type: String, default: null },
      /** See `BrowserToolbar`'s prop of the same name. */
      createHandlersHtml: { type: String, default: "" }
    },
    data() {
      return {
        folder: this.listing.folder,
        path: this.listing.path,
        items: this.listing.results,
        likeStates: this.listing.likeStates || {},
        sort: this.listing.sort,
        order: this.listing.order,
        view: this.listing.view,
        total: this.listing.total,
        page: this.listing.page,
        pages: this.listing.pages,
        loading: false,
        loadingMore: false,
        selection: [],
        showCreate: false,
        showEdit: false,
        editItem: null,
        showMove: false,
        moveItemsList: [],
        moveBusy: false,
        moveError: null,
        dragged: [],
        itemDropTargetKey: null,
        // undefined, not null: null is the top-level crumb's own id (see BrowserToolbar).
        crumbDropTargetId: void 0,
        fileDragDepth: 0,
        uploadProgress: null
      };
    },
    computed: {
      /** The id of the level currently open — null at the top. */
      folderId() {
        return this.folder ? this.folder.id : null;
      },
      selectedItems() {
        return this.items.filter((item) => this.selection.indexOf(keyOf(item)) !== -1);
      },
      fileDragActive() {
        return this.canWrite && this.fileDragDepth > 0;
      },
      createTitle() {
        return vue.i18n.t("CfilesModule.base", "Add folder");
      },
      editTitle() {
        return this.editItem && this.editItem.type === "folder" ? vue.i18n.t("CfilesModule.base", "Edit folder") : vue.i18n.t("CfilesModule.base", "Edit file");
      }
    },
    mounted() {
      window.addEventListener("popstate", this.onPopState);
      this.openRequestedEdit();
      const urlFolderId = this.folderIdFromUrl();
      if (urlFolderId !== null && (urlFolderId || null) !== this.folderId) {
        this.open(urlFolderId || null, { push: false });
      }
    },
    beforeUnmount() {
      window.removeEventListener("popstate", this.onPopState);
    },
    methods: {
      /**
       * Puts the cursor in a dialog's first field once the dialog is actually open.
       *
       * The modal focuses its own dialog element first (so Escape and the tab ring work
       * from the moment it appears), which is why this waits for `opened` rather than
       * focusing on mount.
       */
      focusForm(ref) {
        var _a;
        (_a = this.$refs[ref]) == null ? void 0 : _a.focus();
      },
      /**
       * Opens the edit dialog for the item a deep link asked for.
       *
       * Looked up among the rows already received, so a link to something that is not on
       * this page — or no longer exists — simply opens the folder instead of failing.
       */
      openRequestedEdit() {
        if (!this.editKey) {
          return;
        }
        const match = this.items.find((item) => keyOf(item) === this.editKey);
        if (match) {
          this.openEdit(match);
        }
      },
      folderUrl(folderId) {
        return this.browseUrl + (this.browseUrl.indexOf("?") === -1 ? "?" : "&") + "fid=" + (folderId || 0);
      },
      folderIdFromUrl() {
        const value = new URLSearchParams(window.location.search).get("fid");
        return value === null ? null : parseInt(value, 10) || 0;
      },
      applyPayload(payload) {
        this.folder = payload.folder;
        this.path = payload.path;
        this.items = payload.results;
        this.likeStates = payload.likeStates || {};
        this.sort = payload.sort;
        this.order = payload.order;
        this.view = payload.view;
        this.total = payload.total;
        this.page = payload.page;
        this.pages = payload.pages;
        this.selection = [];
      },
      open(folderId, { push = true } = {}) {
        if (this.loading) {
          return;
        }
        this.loading = true;
        loadItems(this.contentContainerId, folderId, {
          sort: this.sort,
          order: this.order,
          view: this.view
        }).then((payload) => {
          this.applyPayload(payload);
          this.loading = false;
          if (push) {
            window.history.pushState({ cfiles: { folderId } }, "", this.folderUrl(folderId));
          }
        }).catch((e) => {
          this.loading = false;
          vue.log.error(e, true);
        });
      },
      onPopState() {
        const folderId = this.folderIdFromUrl();
        if (folderId === null) {
          return;
        }
        const target = folderId === 0 ? null : folderId;
        if (target !== this.folderId) {
          this.open(target, { push: false });
        }
      },
      reload() {
        this.open(this.folderId, { push: false });
      },
      setSort(sort) {
        this.order = this.sort === sort && this.order === "asc" ? "desc" : "asc";
        this.sort = sort;
        this.reload();
      },
      /**
       * Switches display and reloads, the same way a sort change does.
       *
       * The reload is not just for symmetry: the endpoint is what remembers the preference
       * per user, and a tile grid asks for a bigger page than a row list.
       */
      setView(view) {
        if (view === this.view) {
          return;
        }
        this.view = view;
        this.reload();
      },
      loadMore() {
        if (this.loadingMore || this.page >= this.pages) {
          return;
        }
        this.loadingMore = true;
        loadItems(this.contentContainerId, this.folderId, {
          sort: this.sort,
          order: this.order,
          view: this.view,
          page: this.page + 1
        }).then((payload) => {
          this.items = this.items.concat(payload.results);
          this.likeStates = { ...this.likeStates, ...payload.likeStates || {} };
          this.page = payload.page;
          this.pages = payload.pages;
          this.total = payload.total;
          this.loadingMore = false;
        }).catch((e) => {
          this.loadingMore = false;
          vue.log.error(e, true);
        });
      },
      toggleSelect(item) {
        const key = keyOf(item);
        const at = this.selection.indexOf(key);
        if (at === -1) {
          this.selection.push(key);
        } else {
          this.selection.splice(at, 1);
        }
      },
      /**
       * Selects every LOADED item, or clears the selection when they already are.
       *
       * Deliberately not "everything in this folder": with paging that would arm the delete
       * button with rows the reader has never seen.
       */
      toggleAll() {
        this.selection = this.selection.length === this.items.length ? [] : this.items.map(keyOf);
      },
      // --- context menu ------------------------------------------------------------
      /**
       * The module's own entries. `ContentControls` merges them with what the server's
       * `WallEntryControls` stack resolves and with anything a module registered
       * client-side, so this list is only what cfiles itself contributes.
       */
      entriesFor(item) {
        const isFolder = item.type === "folder";
        return [
          {
            id: "cfiles-open",
            sortOrder: 10,
            label: isFolder ? vue.i18n.t("CfilesModule.base", "Open") : vue.i18n.t("CfilesModule.base", "Download"),
            icon: isFolder ? "folder-open" : "cloud-download",
            url: isFolder ? this.folderUrl(item.id) : item.downloadUrl || item.url,
            onClick: isFolder ? () => this.open(item.id) : void 0
          },
          {
            id: "cfiles-edit",
            sortOrder: 40,
            label: vue.i18n.t("CfilesModule.base", "Edit"),
            icon: "pencil",
            condition: (context) => context.capabilities.canEdit === true,
            onClick: () => this.openEdit(item)
          },
          {
            id: "cfiles-move",
            sortOrder: 50,
            label: vue.i18n.t("CfilesModule.base", "Move"),
            icon: "arrows",
            condition: (context) => this.canWrite && context.capabilities.canEdit === true,
            onClick: () => this.openMove([item])
          },
          {
            id: "cfiles-delete",
            sortOrder: 60,
            label: vue.i18n.t("CfilesModule.base", "Delete"),
            icon: "trash",
            condition: (context) => context.capabilities.canDelete === true,
            onClick: () => this.confirmDelete([item])
          }
        ];
      },
      // --- mutations ---------------------------------------------------------------
      openEdit(item) {
        this.editItem = item;
        this.showEdit = true;
      },
      onCreated() {
        this.showCreate = false;
        this.reload();
      },
      onUpdated() {
        this.showEdit = false;
        this.editItem = null;
        this.reload();
      },
      openMove(items) {
        if (!items.length) {
          return;
        }
        this.moveItemsList = items;
        this.moveError = null;
        this.showMove = true;
      },
      moveTo(targetFolderId) {
        const items = this.moveItemsList.length ? this.moveItemsList : this.dragged;
        this.crumbDropTargetId = void 0;
        this.itemDropTargetKey = null;
        if (!items.length || targetFolderId === this.folderId) {
          this.showMove = false;
          return;
        }
        this.moveBusy = true;
        moveItems(this.contentContainerId, items, targetFolderId).then((response) => {
          this.moveBusy = false;
          this.showMove = false;
          this.moveItemsList = [];
          this.dragged = [];
          this.reportErrors(response.errors);
          this.reload();
        }).catch((response) => {
          this.moveBusy = false;
          const first = response && response.errors && response.errors[0];
          this.moveError = first ? first.message : null;
          if (!this.showMove) {
            vue.log.error(response, true);
          }
        });
      },
      confirmDelete(items) {
        if (!items.length) {
          return;
        }
        vue.modal.confirm({
          header: vue.i18n.t("CfilesModule.base", "<strong>Confirm</strong> delete"),
          body: vue.i18n.t("CfilesModule.base", "Do you really want to delete {count, plural, one{this item} other{these # items}} with all subcontent?", {
            count: items.length
          }),
          confirmText: vue.i18n.t("CfilesModule.base", "Delete")
        }).then((confirmed) => {
          if (!confirmed) {
            return;
          }
          deleteItems(items).then((response) => {
            this.reportErrors(response.errors);
            this.reload();
          }).catch((response) => {
            vue.log.error(response, true);
          });
        });
      },
      reportErrors(errors) {
        (errors || []).forEach((error) => {
          vue.status("error", error.message || error.messages || "");
        });
      },
      // --- upload -----------------------------------------------------------------
      pickFiles() {
        this.$refs.fileInput.click();
      },
      onFilesPicked(event) {
        this.upload(event.target.files);
        event.target.value = "";
      },
      upload(files) {
        if (!files || !files.length || !this.canWrite) {
          return;
        }
        this.uploadProgress = 0;
        uploadFiles(this.contentContainerId, this.folderId, files, (percent) => {
          this.uploadProgress = percent;
        }).then((response) => {
          this.uploadProgress = null;
          this.reportUploadErrors(response.errors);
          this.reload();
        }).catch((response) => {
          this.uploadProgress = null;
          if (response && response.status === 422 && Array.isArray(response.errors)) {
            this.reportUploadErrors(response.errors);
            return;
          }
          vue.log.error(response, true);
        });
      },
      reportUploadErrors(errors) {
        (errors || []).forEach((error) => {
          vue.status("error", error.fileName + ": " + (error.messages || []).join(" "));
        });
      },
      // --- drag & drop ------------------------------------------------------------
      /** Whether a drag carries desktop files rather than one of our own rows. */
      isFileDrag(event) {
        const types = event.dataTransfer ? Array.prototype.slice.call(event.dataTransfer.types) : [];
        return types.indexOf("Files") !== -1;
      },
      onFileDragEnter(event) {
        if (!this.isFileDrag(event)) {
          return;
        }
        this.fileDragDepth++;
      },
      onFileDragOver(event) {
        if (!this.isFileDrag(event) || !this.canWrite) {
          return;
        }
        event.preventDefault();
        event.dataTransfer.dropEffect = "copy";
      },
      onFileDragLeave(event) {
        if (!this.isFileDrag(event)) {
          return;
        }
        this.fileDragDepth = Math.max(0, this.fileDragDepth - 1);
      },
      onFileDrop(event) {
        if (!this.isFileDrag(event)) {
          return;
        }
        event.preventDefault();
        this.fileDragDepth = 0;
        this.upload(event.dataTransfer.files);
      },
      onDropOnFolder(folder) {
        const items = this.dragged.filter((item) => keyOf(item) !== keyOf(folder));
        this.itemDropTargetKey = null;
        if (!items.length) {
          return;
        }
        this.moveItemsList = items;
        this.moveTo(folder.id);
      }
    }
  };
  const _hoisted_1$1 = { class: "panel-body" };
  const _hoisted_2 = {
    key: 0,
    class: "progress cfiles-upload-progress my-2"
  };
  const _hoisted_3 = ["aria-valuenow"];
  function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_BrowserToolbar = vue$1.resolveComponent("BrowserToolbar");
    const _component_ItemList = vue$1.resolveComponent("ItemList");
    const _component_CfilesItemForm = vue$1.resolveComponent("CfilesItemForm");
    const _component_UiModal = vue$1.resolveComponent("UiModal");
    const _component_MoveDialog = vue$1.resolveComponent("MoveDialog");
    return vue$1.openBlock(), vue$1.createElementBlock("div", {
      class: vue$1.normalizeClass(["cfiles-browser", { "cfiles-dropping": $options.fileDragActive }]),
      onDragenter: _cache[17] || (_cache[17] = (...args) => $options.onFileDragEnter && $options.onFileDragEnter(...args)),
      onDragover: _cache[18] || (_cache[18] = (...args) => $options.onFileDragOver && $options.onFileDragOver(...args)),
      onDragleave: _cache[19] || (_cache[19] = (...args) => $options.onFileDragLeave && $options.onFileDragLeave(...args)),
      onDrop: _cache[20] || (_cache[20] = (...args) => $options.onFileDrop && $options.onFileDrop(...args))
    }, [
      vue$1.createElementVNode("div", _hoisted_1$1, [
        vue$1.createVNode(_component_BrowserToolbar, {
          path: $data.path,
          sort: $data.sort,
          order: $data.order,
          "can-write": $props.canWrite,
          view: $data.view,
          "create-handlers-html": $props.createHandlersHtml,
          "drop-target-id": $data.crumbDropTargetId,
          "folder-url": $options.folderUrl,
          onOpen: $options.open,
          onSort: $options.setSort,
          onView: $options.setView,
          onCreateFolder: _cache[0] || (_cache[0] = ($event) => $data.showCreate = true),
          onPickFiles: $options.pickFiles,
          onCrumbDragOver: _cache[1] || (_cache[1] = ($event) => $data.crumbDropTargetId = $event),
          onCrumbDragLeave: _cache[2] || (_cache[2] = ($event) => $data.crumbDropTargetId = void 0),
          onCrumbDrop: _cache[3] || (_cache[3] = ($event) => $options.moveTo($event))
        }, null, 8, ["path", "sort", "order", "can-write", "view", "create-handlers-html", "drop-target-id", "folder-url", "onOpen", "onSort", "onView", "onPickFiles"]),
        $data.uploadProgress !== null ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_2, [
          vue$1.createElementVNode("div", {
            class: "progress-bar",
            role: "progressbar",
            style: vue$1.normalizeStyle({ width: $data.uploadProgress + "%" }),
            "aria-valuenow": $data.uploadProgress,
            "aria-valuemin": "0",
            "aria-valuemax": "100"
          }, vue$1.toDisplayString($data.uploadProgress) + "%", 13, _hoisted_3)
        ])) : vue$1.createCommentVNode("", true),
        vue$1.createVNode(_component_ItemList, {
          items: $data.items,
          selection: $data.selection,
          selectable: $props.canWrite,
          draggable: $props.canWrite,
          "drop-target-key": $data.itemDropTargetKey,
          "has-more": $data.page < $data.pages,
          loading: $data.loading,
          "loading-more": $data.loadingMore,
          "can-write": $props.canWrite,
          view: $data.view,
          "entries-for": $options.entriesFor,
          "folder-url": $options.folderUrl,
          "like-states": $data.likeStates,
          onOpen: _cache[4] || (_cache[4] = ($event) => $options.open($event.id)),
          onToggleSelect: $options.toggleSelect,
          onToggleAll: $options.toggleAll,
          onMoveSelection: _cache[5] || (_cache[5] = ($event) => $options.openMove($options.selectedItems)),
          onDeleteSelection: _cache[6] || (_cache[6] = ($event) => $options.confirmDelete($options.selectedItems)),
          onLoadMore: $options.loadMore,
          onDragStart: _cache[7] || (_cache[7] = ($event) => $data.dragged = [$event]),
          onDragEnd: _cache[8] || (_cache[8] = ($event) => $data.itemDropTargetKey = null),
          onDropOn: $options.onDropOnFolder
        }, null, 8, ["items", "selection", "selectable", "draggable", "drop-target-key", "has-more", "loading", "loading-more", "can-write", "view", "entries-for", "folder-url", "like-states", "onToggleSelect", "onToggleAll", "onLoadMore", "onDropOn"])
      ]),
      vue$1.createElementVNode("input", {
        ref: "fileInput",
        type: "file",
        multiple: "",
        class: "d-none",
        onChange: _cache[9] || (_cache[9] = (...args) => $options.onFilesPicked && $options.onFilesPicked(...args))
      }, null, 544),
      vue$1.createVNode(_component_UiModal, {
        show: $data.showCreate,
        "onUpdate:show": _cache[11] || (_cache[11] = ($event) => $data.showCreate = $event),
        title: $options.createTitle,
        onOpened: _cache[12] || (_cache[12] = ($event) => $options.focusForm("createForm"))
      }, {
        default: vue$1.withCtx(() => [
          $data.showCreate ? (vue$1.openBlock(), vue$1.createBlock(_component_CfilesItemForm, {
            key: 0,
            ref: "createForm",
            "content-container-id": $props.contentContainerId,
            "parent-folder-id": $options.folderId,
            onSaved: $options.onCreated,
            onCancel: _cache[10] || (_cache[10] = ($event) => $data.showCreate = false)
          }, null, 8, ["content-container-id", "parent-folder-id", "onSaved"])) : vue$1.createCommentVNode("", true)
        ]),
        _: 1
      }, 8, ["show", "title"]),
      vue$1.createVNode(_component_UiModal, {
        show: $data.showEdit,
        "onUpdate:show": _cache[14] || (_cache[14] = ($event) => $data.showEdit = $event),
        title: $options.editTitle,
        onOpened: _cache[15] || (_cache[15] = ($event) => $options.focusForm("editForm"))
      }, {
        default: vue$1.withCtx(() => [
          $data.showEdit ? (vue$1.openBlock(), vue$1.createBlock(_component_CfilesItemForm, {
            key: 0,
            ref: "editForm",
            item: $data.editItem,
            onSaved: $options.onUpdated,
            onCancel: _cache[13] || (_cache[13] = ($event) => $data.showEdit = false)
          }, null, 8, ["item", "onSaved"])) : vue$1.createCommentVNode("", true)
        ]),
        _: 1
      }, 8, ["show", "title"]),
      vue$1.createVNode(_component_MoveDialog, {
        show: $data.showMove,
        "content-container-id": $props.contentContainerId,
        items: $data.moveItemsList,
        busy: $data.moveBusy,
        error: $data.moveError,
        onClose: _cache[16] || (_cache[16] = ($event) => $data.showMove = false),
        onConfirm: $options.moveTo
      }, null, 8, ["show", "content-container-id", "items", "busy", "error", "onConfirm"])
    ], 34);
  }
  const C0 = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1]]);
  const _sfc_main = {
    i18nCategories: ["CfilesModule.base", "base"],
    props: {
      /** The serialized item being edited; omit (with `parentFolderId` set) to create. */
      item: { type: Object, default: null },
      /** The container to create in — required together with `parentFolderId`. */
      contentContainerId: { type: Number, default: null },
      /** Set when creating a folder; null creates it at the container's top level. */
      parentFolderId: { type: Number, default: null },
      standalone: { type: Boolean, default: false }
    },
    emits: ["saved", "cancel"],
    data() {
      return {
        busy: false,
        values: {
          title: this.item ? this.item.title : "",
          description: this.item ? this.item.description : "",
          visibility: this.item ? String(this.item.visibility) : "1"
        }
      };
    },
    computed: {
      isFolder() {
        return this.item ? this.item.type === "folder" : true;
      },
      isCreate() {
        return this.item === null;
      },
      titleLabel() {
        return this.isFolder ? vue.i18n.t("CfilesModule.base", "Title") : vue.i18n.t("CfilesModule.base", "File name");
      },
      descriptionLabel() {
        return vue.i18n.t("CfilesModule.base", "Description");
      },
      visibilityLabel() {
        return vue.i18n.t("CfilesModule.base", "Visibility");
      },
      visibilityHint() {
        return vue.i18n.t("CfilesModule.base", "Note: Changes of the folders visibility, will be inherited by all contained files and folders.");
      },
      visibilityOptions() {
        return [
          { value: "1", label: vue.i18n.t("CfilesModule.base", "Public") },
          { value: "0", label: vue.i18n.t("CfilesModule.base", "Private") }
        ];
      },
      saveLabel() {
        return vue.i18n.t("base", "Save");
      },
      cancelLabel() {
        return vue.i18n.t("base", "Cancel");
      }
    },
    methods: {
      /**
       * Focuses the title field — what the dialog this form sits in calls once it is open,
       * so creating a folder is type-and-enter instead of click-then-type.
       */
      focus() {
        this.$refs.form.focusFirstField();
      },
      submit() {
        if (this.busy) {
          return;
        }
        this.busy = true;
        this.$refs.form.clearErrors();
        const attributes = {
          title: this.values.title,
          description: this.values.description,
          visibility: Number(this.values.visibility)
        };
        const request = this.isCreate ? createFolder(this.contentContainerId, this.parentFolderId, attributes) : updateItem(this.item, attributes);
        request.then((saved) => {
          this.busy = false;
          if (this.standalone) {
            window.location.reload();
            return;
          }
          this.$emit("saved", saved);
        }).catch((response) => {
          this.busy = false;
          if (response && response.status === 422 && response.errors) {
            this.$refs.form.setErrors({ errors: response.errors });
            return;
          }
          vue.log.error(response, true);
        });
      }
    }
  };
  const _hoisted_1 = { class: "d-flex justify-content-end gap-2" };
  function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_TextField = vue$1.resolveComponent("TextField");
    const _component_TextareaField = vue$1.resolveComponent("TextareaField");
    const _component_SelectField = vue$1.resolveComponent("SelectField");
    const _component_SubmitButton = vue$1.resolveComponent("SubmitButton");
    const _component_HumHubForm = vue$1.resolveComponent("HumHubForm");
    return vue$1.openBlock(), vue$1.createBlock(_component_HumHubForm, {
      ref: "form",
      busy: $data.busy,
      onSubmit: $options.submit
    }, {
      default: vue$1.withCtx(() => [
        vue$1.createVNode(_component_TextField, {
          attribute: "title",
          modelValue: $data.values.title,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.values.title = $event),
          label: $options.titleLabel,
          required: true
        }, null, 8, ["modelValue", "label"]),
        vue$1.createVNode(_component_TextareaField, {
          attribute: "description",
          modelValue: $data.values.description,
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.values.description = $event),
          label: $options.descriptionLabel,
          rows: 3
        }, null, 8, ["modelValue", "label"]),
        vue$1.createVNode(_component_SelectField, {
          attribute: "visibility",
          modelValue: $data.values.visibility,
          "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.values.visibility = $event),
          label: $options.visibilityLabel,
          options: $options.visibilityOptions,
          hint: $options.isFolder ? $options.visibilityHint : null
        }, null, 8, ["modelValue", "label", "options", "hint"]),
        vue$1.createElementVNode("div", _hoisted_1, [
          vue$1.createElementVNode("button", {
            type: "button",
            class: "btn btn-light",
            onClick: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("cancel"))
          }, vue$1.toDisplayString($options.cancelLabel), 1),
          vue$1.createVNode(_component_SubmitButton, { class: "btn btn-primary" }, {
            default: vue$1.withCtx(() => [
              vue$1.createTextVNode(vue$1.toDisplayString($options.saveLabel), 1)
            ]),
            _: 1
          })
        ])
      ]),
      _: 1
    }, 8, ["busy", "onSubmit"]);
  }
  const C1 = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
  vue.register("CfilesFileBrowser", C0);
  vue.register("CfilesItemForm", C1);
})(humhub.modules.vue, Vue);
//# sourceMappingURL=humhub.cfiles.vue.js.map
