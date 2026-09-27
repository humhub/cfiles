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
  const _sfc_main$6 = {
    props: {
      /** Server-rendered `<li>` markup for the file handlers a module contributed. */
      handlersHtml: { type: String, default: "" }
    },
    emits: ["upload", "create-folder"],
    computed: {
      addLabel() {
        return vue.i18n.t("CfilesModule.base", "Add");
      },
      uploadLabel() {
        return vue.i18n.t("CfilesModule.base", "Upload files");
      },
      folderLabel() {
        return vue.i18n.t("CfilesModule.base", "New folder");
      }
    }
  };
  const _hoisted_1$6 = { class: "dropdown cfiles-add-menu" };
  const _hoisted_2$5 = ["aria-label", "title"];
  const _hoisted_3$5 = { class: "dropdown-menu dropdown-menu-end" };
  const _hoisted_4$5 = { key: 0 };
  const _hoisted_5$5 = { key: 1 };
  const _hoisted_6$5 = ["innerHTML"];
  function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
    const _directive_additions = vue$1.resolveDirective("additions");
    return vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_1$6, [
      vue$1.createElementVNode("button", {
        type: "button",
        class: "btn btn-accent c-icon-button",
        "data-bs-toggle": "dropdown",
        "aria-haspopup": "true",
        "aria-expanded": "false",
        "aria-label": $options.addLabel,
        title: $options.addLabel
      }, [..._cache[2] || (_cache[2] = [
        vue$1.createElementVNode("i", {
          class: "ti ti-plus",
          "aria-hidden": "true"
        }, null, -1)
      ])], 8, _hoisted_2$5),
      vue$1.createElementVNode("ul", _hoisted_3$5, [
        vue$1.createElementVNode("li", null, [
          vue$1.createElementVNode("a", {
            href: "#",
            class: "dropdown-item d-flex align-items-center gap-2 cfiles-add-menu__entry",
            onClick: _cache[0] || (_cache[0] = vue$1.withModifiers(($event) => _ctx.$emit("upload"), ["prevent"]))
          }, [
            _cache[3] || (_cache[3] = vue$1.createElementVNode("i", {
              class: "ti ti-upload",
              "aria-hidden": "true"
            }, null, -1)),
            vue$1.createTextVNode(vue$1.toDisplayString($options.uploadLabel), 1)
          ])
        ]),
        vue$1.createElementVNode("li", null, [
          vue$1.createElementVNode("a", {
            href: "#",
            class: "dropdown-item d-flex align-items-center gap-2 cfiles-add-menu__entry",
            onClick: _cache[1] || (_cache[1] = vue$1.withModifiers(($event) => _ctx.$emit("create-folder"), ["prevent"]))
          }, [
            _cache[4] || (_cache[4] = vue$1.createElementVNode("i", {
              class: "ti ti-folder-plus",
              "aria-hidden": "true"
            }, null, -1)),
            vue$1.createTextVNode(vue$1.toDisplayString($options.folderLabel), 1)
          ])
        ]),
        $props.handlersHtml ? (vue$1.openBlock(), vue$1.createElementBlock("li", _hoisted_4$5, [..._cache[5] || (_cache[5] = [
          vue$1.createElementVNode("hr", { class: "dropdown-divider" }, null, -1)
        ])])) : vue$1.createCommentVNode("", true),
        $props.handlersHtml ? (vue$1.openBlock(), vue$1.createElementBlock("li", _hoisted_5$5, [
          vue$1.withDirectives(vue$1.createElementVNode("ul", {
            class: "list-unstyled m-0",
            innerHTML: $props.handlersHtml
          }, null, 8, _hoisted_6$5), [
            [_directive_additions]
          ])
        ])) : vue$1.createCommentVNode("", true)
      ])
    ]);
  }
  const AddMenu = /* @__PURE__ */ _export_sfc(_sfc_main$6, [["render", _sfc_render$6]]);
  const SUPPRESSED_CORE_ENTRIES = ["edit", "delete", "permalink", "pin", "move", "archive"];
  const CONTROLS_VIEW_CONTEXT = "detail";
  const WEEK_IN_SECONDS = 7 * 24 * 60 * 60;
  const RELATIVE_UNITS = [
    ["day", 24 * 60 * 60],
    ["hour", 60 * 60],
    ["minute", 60],
    ["second", 1]
  ];
  const fileIcon = (item) => "ti-" + (item.icon || "file");
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
  const tileMeta = (item) => item.type === "folder" ? typeof item.itemCount === "number" ? vue.i18n.t("CfilesModule.base", "{count, plural, =0{empty} one{# item} other{# items}}", {
    count: item.itemCount
  }) : "" : formatSize(item.size);
  const itemLocation = (item) => {
    const path = item.path || [];
    if (!path.length) {
      return null;
    }
    const parent = path[path.length - 1];
    return {
      folder: { type: "folder", id: parent.id, title: parent.title },
      label: vue.i18n.t("CfilesModule.base", "in {path}", { path: path.map((level) => level.title).join(" › ") })
    };
  };
  const isPlainClick = (event) => !(event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button > 0);
  const _sfc_main$5 = {
    props: {
      items: { type: Array, default: () => [] },
      selection: { type: Array, default: () => [] },
      selectable: { type: Boolean, default: false },
      draggable: { type: Boolean, default: false },
      canDrop: { type: Function, default: () => false },
      // `null` is no target (TileGrid's convention, unlike PathBar's `dropTargetId`).
      dropTargetKey: { type: [String, Number], default: null },
      hasMore: { type: Boolean, default: false },
      loading: { type: Boolean, default: false },
      loadingMore: { type: Boolean, default: false },
      level: { type: [String, Number], default: 0 },
      direction: { type: String, default: "forward" },
      entriesFor: { type: Function, required: true },
      folderUrl: { type: Function, required: true }
    },
    emits: ["open", "toggle-select", "drag-start", "drag-end", "drag-over", "drag-leave", "drop-on", "load-more"],
    data() {
      return { CONTROLS_VIEW_CONTEXT, SUPPRESSED_CORE_ENTRIES };
    },
    computed: {
      privateLabel() {
        return vue.i18n.t("CfilesModule.base", "Private");
      },
      actionsLabel() {
        return vue.i18n.t("base", "Actions");
      }
    },
    created() {
      this.controls = {};
    },
    methods: {
      fileIcon,
      itemLocation,
      tileMeta,
      labelFor(item) {
        return item.title ?? "";
      },
      linkUrl(item) {
        var _a;
        return item.type === "folder" ? this.folderUrl(item.id) : ((_a = item.link) == null ? void 0 : _a.url) || item.url || "#";
      },
      linkAttributes(item) {
        var _a;
        return item.type === "folder" ? {} : ((_a = item.link) == null ? void 0 : _a.attributes) || {};
      },
      setControls(item, el) {
        if (el) {
          this.controls[item.key] = el;
        } else {
          delete this.controls[item.key];
        }
      },
      onOpen(item, event) {
        if (item.type !== "folder" || !isPlainClick(event)) {
          return;
        }
        event.preventDefault();
        this.$emit("open", item);
      },
      /** The folder a hit of a result list lies in, opened like a folder tile. */
      onOpenLocation(item, event) {
        if (!isPlainClick(event)) {
          return;
        }
        event.preventDefault();
        this.$emit("open", itemLocation(item).folder);
      },
      onContextMenu(item, event) {
        var _a, _b, _c;
        if ((_b = (_a = event.target).closest) == null ? void 0 : _b.call(_a, ".dropdown-menu")) {
          return;
        }
        (_c = this.controls[item.key]) == null ? void 0 : _c.open(event.button === 2 ? event : null);
      },
      onDragStart(item, event) {
        if (event.dataTransfer) {
          event.dataTransfer.effectAllowed = "move";
          event.dataTransfer.setData("text/plain", item.key);
        }
        this.$emit("drag-start", item, event);
      }
    }
  };
  const _hoisted_1$5 = {
    key: 0,
    class: "ti ti-folder-filled cfiles-tile__folder"
  };
  const _hoisted_2$4 = ["src"];
  const _hoisted_3$4 = {
    key: 2,
    class: "cfiles-tile__doc"
  };
  const _hoisted_4$4 = { key: 0 };
  const _hoisted_5$4 = ["href", "title", "onClick"];
  const _hoisted_6$4 = ["aria-label"];
  const _hoisted_7$1 = ["href", "title", "onClick"];
  function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_ContentControls = vue$1.resolveComponent("ContentControls");
    const _component_TileGrid = vue$1.resolveComponent("TileGrid");
    return vue$1.openBlock(), vue$1.createBlock(_component_TileGrid, {
      items: $props.items,
      "item-key": "key",
      selectable: $props.selectable,
      selection: $props.selection,
      draggable: $props.draggable,
      "can-drop": $props.canDrop,
      "drop-target-key": $props.dropTargetKey,
      loading: $props.loading,
      "has-more": $props.hasMore,
      "loading-more": $props.loadingMore,
      level: $props.level,
      direction: $props.direction,
      "label-for": $options.labelFor,
      onToggleSelect: _cache[0] || (_cache[0] = (item, flags) => _ctx.$emit("toggle-select", item, flags)),
      onContextMenu: $options.onContextMenu,
      onDragStart: $options.onDragStart,
      onDragEnd: _cache[1] || (_cache[1] = (item, event) => _ctx.$emit("drag-end", item, event)),
      onDragOver: _cache[2] || (_cache[2] = (item, event) => _ctx.$emit("drag-over", item, event)),
      onDragLeave: _cache[3] || (_cache[3] = (item, event) => _ctx.$emit("drag-leave", item, event)),
      onDropOn: _cache[4] || (_cache[4] = (item, event) => _ctx.$emit("drop-on", item, event)),
      onLoadMore: _cache[5] || (_cache[5] = ($event) => _ctx.$emit("load-more"))
    }, {
      thumb: vue$1.withCtx(({ item }) => [
        item.type === "folder" ? (vue$1.openBlock(), vue$1.createElementBlock("i", _hoisted_1$5)) : item.previewUrl ? (vue$1.openBlock(), vue$1.createElementBlock("img", {
          key: 1,
          src: item.previewUrl,
          alt: "",
          draggable: "false",
          class: "cfiles-tile__image"
        }, null, 8, _hoisted_2$4)) : (vue$1.openBlock(), vue$1.createElementBlock("span", _hoisted_3$4, [
          vue$1.createElementVNode("i", {
            class: vue$1.normalizeClass("ti " + $options.fileIcon(item))
          }, null, 2)
        ]))
      ]),
      name: vue$1.withCtx(({ item }) => [
        item.type === "upload" ? (vue$1.openBlock(), vue$1.createElementBlock("span", _hoisted_4$4, vue$1.toDisplayString(item.title), 1)) : (vue$1.openBlock(), vue$1.createElementBlock("a", vue$1.mergeProps({
          key: 1,
          href: $options.linkUrl(item)
        }, $options.linkAttributes(item), {
          draggable: "false",
          title: item.title,
          onClick: ($event) => $options.onOpen(item, $event)
        }), [
          vue$1.createTextVNode(vue$1.toDisplayString(item.title), 1),
          item.visibility === 0 ? (vue$1.openBlock(), vue$1.createElementBlock("i", {
            key: 0,
            class: "ti ti-lock ms-1 text-muted",
            role: "img",
            "aria-label": $options.privateLabel
          }, null, 8, _hoisted_6$4)) : vue$1.createCommentVNode("", true)
        ], 16, _hoisted_5$4))
      ]),
      meta: vue$1.withCtx(({ item }) => [
        vue$1.createTextVNode(vue$1.toDisplayString(item.type === "upload" ? (item.progress || 0) + "%" : $options.tileMeta(item)), 1),
        item.type !== "upload" && $options.itemLocation(item) ? (vue$1.openBlock(), vue$1.createElementBlock("a", {
          key: 0,
          class: "cfiles-location cfiles-tile__location",
          href: $props.folderUrl($options.itemLocation(item).folder.id),
          title: $options.itemLocation(item).label,
          draggable: "false",
          onClick: ($event) => $options.onOpenLocation(item, $event)
        }, vue$1.toDisplayString($options.itemLocation(item).label), 9, _hoisted_7$1)) : vue$1.createCommentVNode("", true)
      ]),
      actions: vue$1.withCtx(({ item }) => [
        item.type !== "upload" ? (vue$1.openBlock(), vue$1.createBlock(_component_ContentControls, {
          key: 0,
          ref: (el) => $options.setControls(item, el),
          "content-id": item.contentId,
          "view-context": $data.CONTROLS_VIEW_CONTEXT,
          entries: $props.entriesFor(item),
          suppress: $data.SUPPRESSED_CORE_ENTRIES,
          context: { item },
          "root-class": "nav",
          "toggle-class": "btn c-icon-button c-icon-button--ghost cfiles-tile__toggle",
          "toggle-aria-label": $options.actionsLabel
        }, {
          toggle: vue$1.withCtx(() => [..._cache[6] || (_cache[6] = [
            vue$1.createElementVNode("i", {
              class: "ti ti-dots-vertical",
              "aria-hidden": "true"
            }, null, -1)
          ])]),
          _: 1
        }, 8, ["content-id", "view-context", "entries", "suppress", "context", "toggle-aria-label"])) : vue$1.createCommentVNode("", true)
      ]),
      empty: vue$1.withCtx(() => [
        vue$1.renderSlot(_ctx.$slots, "empty")
      ]),
      _: 3
    }, 8, ["items", "selectable", "selection", "draggable", "can-drop", "drop-target-key", "loading", "has-more", "loading-more", "level", "direction", "label-for", "onContextMenu", "onDragStart"]);
  }
  const FileTiles = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5]]);
  const _sfc_main$4 = {
    props: {
      item: { type: Object, required: true },
      selected: { type: Boolean, default: false },
      selectable: { type: Boolean, default: false },
      draggable: { type: Boolean, default: false },
      dropTarget: { type: Boolean, default: false },
      /** `(item, event) => bool` — whether a drag may land on this row (TileGrid's `canDrop`). */
      canDrop: { type: Function, default: () => false },
      entries: { type: Array, default: () => [] },
      folderUrl: { type: Function, required: true },
      /**
       * `recordId => {total, liked, canLike}` for the whole page, as the listing payload
       * carries it. Empty where the like module is off, which is what hides the button.
       */
      likeStates: { type: Object, default: () => ({}) }
    },
    emits: ["open", "toggle-select", "drag-start", "drag-end", "drag-over", "drag-leave", "drop-on"],
    data() {
      return { CONTROLS_VIEW_CONTEXT, SUPPRESSED_CORE_ENTRIES };
    },
    created() {
      this.dragging = false;
    },
    computed: {
      isUpload() {
        return !!this.item.uploading;
      },
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
        return this.isFolder ? "ti ti-folder-filled cfiles-icon-folder" : "ti " + fileIcon(this.item) + " cfiles-icon-file";
      },
      meta() {
        return itemMeta(this.item, { description: !this.location });
      },
      /** Where a hit of a result list lies — null for an item directly in the open folder. */
      location() {
        return itemLocation(this.item);
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
      uploadingLabel() {
        return vue.i18n.t("base", "Uploading...");
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
        var _a;
        if (this.isUpload) {
          return;
        }
        if (event.ctrlKey) {
          return;
        }
        if (event.target.closest(".dropdown-menu")) {
          return;
        }
        event.preventDefault();
        (_a = this.$refs.controls) == null ? void 0 : _a.open(event.button === 2 ? event : null);
      },
      onCheck(event) {
        this.$emit("toggle-select", this.item, { range: event.shiftKey });
        this.$nextTick(() => {
          event.target.checked = this.selected;
        });
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
      /**
       * Opens the folder a hit of a result list lies in (`open` with that folder, as a
       * folder row emits it); any other click follows the link.
       */
      onOpenLocation(event) {
        if (!isPlainClick(event)) {
          return;
        }
        event.preventDefault();
        this.$emit("open", this.location.folder);
      },
      onDragStart(event) {
        if (!this.draggable || this.item.uploading) {
          return;
        }
        if (event.dataTransfer) {
          event.dataTransfer.effectAllowed = "move";
          event.dataTransfer.setData("text/plain", this.item.type + ":" + this.item.id);
        }
        this.dragging = true;
        this.$emit("drag-start", this.item, event);
      },
      onDragEnd(event) {
        if (this.dragging) {
          this.dragging = false;
          this.$emit("drag-end", this.item, event);
        }
      },
      onDragOver(event) {
        if (this.canDrop(this.item, event)) {
          event.preventDefault();
          this.$emit("drag-over", this.item, event);
        }
      },
      onDragLeave(event) {
        if (!event.currentTarget.contains(event.relatedTarget)) {
          this.$emit("drag-leave", this.item, event);
        }
      },
      onDrop(event) {
        if (!this.canDrop(this.item, event)) {
          return;
        }
        event.preventDefault();
        event.stopPropagation();
        this.$emit("drop-on", this.item, event);
      }
    }
  };
  const _hoisted_1$4 = ["draggable"];
  const _hoisted_2$3 = { class: "cfiles-row-icon" };
  const _hoisted_3$3 = { class: "flex-grow-1 min-width-0" };
  const _hoisted_4$3 = { class: "mb-0 text-truncate" };
  const _hoisted_5$3 = { class: "progress cfiles-row__progress" };
  const _hoisted_6$3 = ["aria-valuenow", "aria-label"];
  const _hoisted_7 = {
    key: 0,
    class: "cfiles-row-select"
  };
  const _hoisted_8 = ["checked", "aria-label"];
  const _hoisted_9 = { class: "cfiles-row-icon" };
  const _hoisted_10 = ["src"];
  const _hoisted_11 = { class: "flex-grow-1 min-width-0" };
  const _hoisted_12 = { class: "mb-0 d-flex align-items-center gap-1" };
  const _hoisted_13 = ["href"];
  const _hoisted_14 = ["title", "aria-label"];
  const _hoisted_15 = { class: "mb-0 text-truncate cfiles-row-meta" };
  const _hoisted_16 = ["href"];
  const _hoisted_17 = { class: "cfiles-row-controls" };
  function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_LikeButton = vue$1.resolveComponent("LikeButton");
    const _component_UserImage = vue$1.resolveComponent("UserImage");
    const _component_ContentControls = vue$1.resolveComponent("ContentControls");
    return vue$1.openBlock(), vue$1.createElementBlock("div", {
      class: vue$1.normalizeClass(["cfiles-row d-flex align-items-center gap-2", { "is-drop-target": $props.dropTarget, "is-selected": $props.selected, "is-uploading": $props.item.uploading }]),
      draggable: $props.draggable && !$props.item.uploading,
      onClick: _cache[5] || (_cache[5] = (...args) => $options.onRowClick && $options.onRowClick(...args)),
      onContextmenu: _cache[6] || (_cache[6] = (...args) => $options.onContextMenu && $options.onContextMenu(...args)),
      onDragstart: _cache[7] || (_cache[7] = (...args) => $options.onDragStart && $options.onDragStart(...args)),
      onDragend: _cache[8] || (_cache[8] = (...args) => $options.onDragEnd && $options.onDragEnd(...args)),
      onDragover: _cache[9] || (_cache[9] = (...args) => $options.onDragOver && $options.onDragOver(...args)),
      onDragleave: _cache[10] || (_cache[10] = (...args) => $options.onDragLeave && $options.onDragLeave(...args)),
      onDrop: _cache[11] || (_cache[11] = (...args) => $options.onDrop && $options.onDrop(...args))
    }, [
      $options.isUpload ? (vue$1.openBlock(), vue$1.createElementBlock(vue$1.Fragment, { key: 0 }, [
        vue$1.createElementVNode("div", _hoisted_2$3, [
          vue$1.createElementVNode("i", {
            class: vue$1.normalizeClass($options.iconClass),
            "aria-hidden": "true"
          }, null, 2)
        ]),
        vue$1.createElementVNode("div", _hoisted_3$3, [
          vue$1.createElementVNode("h4", _hoisted_4$3, vue$1.toDisplayString($options.displayTitle), 1),
          vue$1.createElementVNode("div", _hoisted_5$3, [
            vue$1.createElementVNode("div", {
              class: "progress-bar",
              role: "progressbar",
              style: vue$1.normalizeStyle({ width: ($props.item.progress || 0) + "%" }),
              "aria-valuenow": $props.item.progress || 0,
              "aria-valuemin": "0",
              "aria-valuemax": "100",
              "aria-label": $options.uploadingLabel
            }, null, 12, _hoisted_6$3)
          ])
        ])
      ], 64)) : (vue$1.openBlock(), vue$1.createElementBlock(vue$1.Fragment, { key: 1 }, [
        $props.selectable ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_7, [
          vue$1.createElementVNode("input", {
            type: "checkbox",
            class: "form-check-input",
            checked: $props.selected,
            "aria-label": $options.selectLabel,
            onClick: _cache[0] || (_cache[0] = (...args) => $options.onCheck && $options.onCheck(...args))
          }, null, 8, _hoisted_8)
        ])) : vue$1.createCommentVNode("", true),
        vue$1.createElementVNode("div", _hoisted_9, [
          $props.item.previewUrl ? (vue$1.openBlock(), vue$1.createElementBlock("img", {
            key: 0,
            src: $props.item.previewUrl,
            alt: "",
            draggable: "false",
            class: "cfiles-thumb"
          }, null, 8, _hoisted_10)) : (vue$1.openBlock(), vue$1.createElementBlock("i", {
            key: 1,
            class: vue$1.normalizeClass($options.iconClass),
            "aria-hidden": "true"
          }, null, 2))
        ]),
        vue$1.createElementVNode("div", _hoisted_11, [
          vue$1.createElementVNode("h4", _hoisted_12, [
            vue$1.createElementVNode("a", vue$1.mergeProps({
              ref: "titleLink",
              href: $options.linkUrl
            }, $options.linkAttributes, {
              draggable: "false",
              class: "text-truncate",
              onClick: _cache[1] || (_cache[1] = (...args) => $options.onOpen && $options.onOpen(...args))
            }), vue$1.toDisplayString($options.displayTitle), 17, _hoisted_13),
            $options.isPrivate ? (vue$1.openBlock(), vue$1.createElementBlock("i", {
              key: 0,
              class: "ti ti-lock text-muted flex-shrink-0",
              title: $options.privateLabel,
              "aria-label": $options.privateLabel
            }, null, 8, _hoisted_14)) : vue$1.createCommentVNode("", true)
          ]),
          vue$1.createElementVNode("h5", _hoisted_15, [
            vue$1.createTextVNode(vue$1.toDisplayString($options.meta), 1),
            $options.location ? (vue$1.openBlock(), vue$1.createElementBlock(vue$1.Fragment, { key: 0 }, [
              _cache[12] || (_cache[12] = vue$1.createTextVNode(" · ", -1)),
              vue$1.createElementVNode("a", {
                class: "cfiles-location",
                href: $props.folderUrl($options.location.folder.id),
                draggable: "false",
                onClick: _cache[2] || (_cache[2] = (...args) => $options.onOpenLocation && $options.onOpenLocation(...args))
              }, vue$1.toDisplayString($options.location.label), 9, _hoisted_16)
            ], 64)) : vue$1.createCommentVNode("", true)
          ])
        ]),
        $options.likeState ? (vue$1.openBlock(), vue$1.createElementBlock("div", {
          key: 1,
          class: "cfiles-row-social",
          onDragstart: _cache[3] || (_cache[3] = vue$1.withModifiers(() => {
          }, ["prevent", "stop"]))
        }, [
          vue$1.createVNode(_component_LikeButton, {
            "record-id": $props.item.recordId,
            "like-count": $options.likeState.total,
            "current-user-liked": $options.likeState.liked
          }, null, 8, ["record-id", "like-count", "current-user-liked"])
        ], 32)) : vue$1.createCommentVNode("", true),
        vue$1.createElementVNode("div", {
          class: "cfiles-row-creator",
          onDragstart: _cache[4] || (_cache[4] = vue$1.withModifiers(() => {
          }, ["prevent", "stop"]))
        }, [
          $props.item.creator ? (vue$1.openBlock(), vue$1.createBlock(_component_UserImage, vue$1.mergeProps({ key: 0 }, $props.item.creator, { size: 21 }), null, 16)) : vue$1.createCommentVNode("", true)
        ], 32),
        vue$1.createElementVNode("div", _hoisted_17, [
          vue$1.createVNode(_component_ContentControls, {
            ref: "controls",
            "content-id": $props.item.contentId,
            "view-context": $data.CONTROLS_VIEW_CONTEXT,
            entries: $props.entries,
            suppress: $data.SUPPRESSED_CORE_ENTRIES,
            context: { item: $props.item },
            "toggle-class": "nav-link dropdown-toggle cfiles-row-toggle",
            "toggle-aria-label": $options.actionsLabel
          }, null, 8, ["content-id", "view-context", "entries", "suppress", "context", "toggle-aria-label"])
        ])
      ], 64))
    ], 42, _hoisted_1$4);
  }
  const ItemRow = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4]]);
  const loadItems = (containerId, parent, { sort, page, pageSize, filters = {} } = {}) => {
    const params = {};
    if (parent) {
      params.parent = parent;
    }
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== "" && value !== null && value !== void 0) {
        params[key] = value;
      }
    });
    if (sort) {
      params.sort = sort;
    }
    if (page) {
      params.page = page;
    }
    if (pageSize) {
      params.pageSize = pageSize;
    }
    return vue.client.get(vue.apiUrl("cfiles/" + containerId + "/items", params));
  };
  const savePreferences = ({ view }) => vue.client.patch(vue.apiUrl("cfiles/preferences"), { data: { view } });
  const createFolder = (containerId, parent, attributes) => vue.client.post(vue.apiUrl("cfiles/" + containerId + "/folders"), {
    data: { ...attributes, parent }
  });
  const updateItem = (item, attributes) => vue.client.patch(vue.apiUrl("cfiles/" + item.type + "/" + item.id), { data: attributes });
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
    components: { ItemRow },
    props: {
      items: { type: Array, default: () => [] },
      selection: { type: Array, default: () => [] },
      selectable: { type: Boolean, default: false },
      draggable: { type: Boolean, default: false },
      canDrop: { type: Function, default: () => false },
      dropTargetKey: { type: [String, Number], default: null },
      hasMore: { type: Boolean, default: false },
      loading: { type: Boolean, default: false },
      loadingMore: { type: Boolean, default: false },
      canWrite: { type: Boolean, default: false },
      entriesFor: { type: Function, required: true },
      folderUrl: { type: Function, required: true },
      /** `recordId => {total, liked, canLike}`, handed straight to `ItemRow`. */
      likeStates: { type: Object, default: () => ({}) }
    },
    emits: ["open", "toggle-select", "drag-start", "drag-end", "drag-over", "drag-leave", "drop-on", "load-more"],
    computed: {
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
    methods: {
      keyOf,
      isSelected(item) {
        return this.selection.indexOf(keyOf(item)) !== -1;
      }
    }
  };
  const _hoisted_1$3 = {
    key: 0,
    class: "hh-list cfiles-list"
  };
  const _hoisted_2$2 = {
    key: 1,
    class: "cfiles-empty text-center text-muted p-4"
  };
  const _hoisted_3$2 = { class: "mb-0" };
  const _hoisted_4$2 = { class: "mb-0" };
  const _hoisted_5$2 = {
    key: 2,
    class: "text-center p-2"
  };
  const _hoisted_6$2 = ["disabled"];
  function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_ItemRow = vue$1.resolveComponent("ItemRow");
    return vue$1.openBlock(), vue$1.createElementBlock("div", null, [
      $props.items.length ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_1$3, [
        (vue$1.openBlock(true), vue$1.createElementBlock(vue$1.Fragment, null, vue$1.renderList($props.items, (item) => {
          return vue$1.openBlock(), vue$1.createBlock(_component_ItemRow, {
            key: $options.keyOf(item),
            item,
            selected: $options.isSelected(item),
            selectable: $props.selectable,
            draggable: $props.draggable,
            "can-drop": $props.canDrop,
            "drop-target": $props.dropTargetKey !== null && $props.dropTargetKey === $options.keyOf(item),
            entries: $props.entriesFor(item),
            "folder-url": $props.folderUrl,
            "like-states": $props.likeStates,
            onOpen: (item2) => _ctx.$emit("open", item2),
            onToggleSelect: (item2, flags) => _ctx.$emit("toggle-select", item2, flags),
            onDragStart: (item2, event) => _ctx.$emit("drag-start", item2, event),
            onDragEnd: (item2, event) => _ctx.$emit("drag-end", item2, event),
            onDragOver: (item2, event) => _ctx.$emit("drag-over", item2, event),
            onDragLeave: (item2, event) => _ctx.$emit("drag-leave", item2, event),
            onDropOn: (item2, event) => _ctx.$emit("drop-on", item2, event)
          }, null, 8, ["item", "selected", "selectable", "draggable", "can-drop", "drop-target", "entries", "folder-url", "like-states", "onOpen", "onToggleSelect", "onDragStart", "onDragEnd", "onDragOver", "onDragLeave", "onDropOn"]);
        }), 128))
      ])) : !$props.loading ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_2$2, [
        vue$1.renderSlot(_ctx.$slots, "empty", {}, () => [
          vue$1.createElementVNode("p", _hoisted_3$2, [
            vue$1.createElementVNode("strong", null, vue$1.toDisplayString($options.emptyTitle), 1)
          ]),
          vue$1.createElementVNode("p", _hoisted_4$2, vue$1.toDisplayString($options.emptyHint), 1)
        ])
      ])) : vue$1.createCommentVNode("", true),
      $props.hasMore ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_5$2, [
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-light btn-sm",
          disabled: $props.loadingMore,
          onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("load-more"))
        }, vue$1.toDisplayString($props.loadingMore ? $options.loadingLabel : $options.moreLabel), 9, _hoisted_6$2)
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
  const _hoisted_4$1 = ["onClick"];
  const _hoisted_5$1 = {
    key: 0,
    class: "text-danger mt-2 mb-0"
  };
  const _hoisted_6$1 = ["disabled"];
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
        }, vue$1.toDisplayString($options.moveLabel), 9, _hoisted_6$1)
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
                class: vue$1.normalizeClass(["ti icon-fw", node.expanded ? "ti-caret-down-filled" : node.hasChildren === false ? "" : "ti-caret-right-filled"]),
                "aria-hidden": "true",
                onClick: vue$1.withModifiers(($event) => $options.toggle(node), ["stop"])
              }, null, 10, _hoisted_4$1),
              _cache[3] || (_cache[3] = vue$1.createElementVNode("i", {
                class: "ti ti-folder-filled text-muted",
                "aria-hidden": "true"
              }, null, -1)),
              vue$1.createTextVNode(" " + vue$1.toDisplayString(node.isTop ? $options.rootLabel : node.title), 1)
            ], 46, _hoisted_3$1);
          }), 128))
        ]),
        $props.error ? (vue$1.openBlock(), vue$1.createElementBlock("p", _hoisted_5$1, vue$1.toDisplayString($props.error), 1)) : vue$1.createCommentVNode("", true)
      ]),
      _: 1
    }, 8, ["show", "title"]);
  }
  const MoveDialog = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2]]);
  let uploadSeq = 0;
  const PAGE_SIZES = { list: 50, tiles: 96 };
  const SORT_DEFAULT = "default";
  const _sfc_main$1 = {
    components: { AddMenu, FileTiles, ItemList, MoveDialog },
    // A top-level island declares what its whole subtree needs, not only its own messages.
    i18nCategories: ["CfilesModule.base", "base", "ContentModule.base", "LikeModule.base", "UserModule.base"],
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
      /**
       * Server-rendered `<li>` markup of the file handlers modules contributed ("New
       * spreadsheet", "Import from …"), appended to the `AddMenu`.
       */
      createHandlersHtml: { type: String, default: "" },
      /** The FilterBar definitions (`FolderList::definitions()`): the filters and the sort. */
      filters: { type: Array, default: () => [] },
      /**
       * The values of the list's filters the first page was built with, by key (`''` = not
       * set) — the page URL's (`BrowseController::firstListing()`).
       */
      initialFilters: { type: Object, default: () => ({}) },
      /** The container's cfiles settings page, or null for those who may not change them. */
      settingsUrl: { type: String, default: null }
    },
    data() {
      return {
        folder: this.listing.folder,
        path: this.listing.path,
        items: this.listing.results,
        likeStates: this.listing.likeStates || {},
        // A key of the list's sorts, `default` for none chosen.
        sort: this.listing.sort || SORT_DEFAULT,
        // The values of the list's filters by key, `''` = not set; sent with every load.
        query: { ...this.initialFilters },
        // Whether the items are the filters' hits in the folder and its subfolders.
        resultsMode: this.listing.resultsMode === true,
        // The payload's `view` is the stored preference; from here on the island owns it.
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
        // What an item drag in flight carries: the selection, or the one item dragged.
        dragged: [],
        // Two values, two conventions: TileGrid's/ItemList's `null` is "no target", while
        // PathBar's `null` is the root crumb and `undefined` is "no target".
        itemDropTargetKey: null,
        crumbDropTargetId: void 0,
        // Placeholders for the files being uploaded into the open level.
        uploads: [],
        // Folders shown here that files are being uploaded into: key => { batch => progress }.
        uploadTargets: {},
        // The item the last plain selection click was on, where a Shift range starts.
        anchorKey: null,
        // The level the tiles show, and which way the last change went (for the slide).
        level: this.listing.folder ? this.listing.folder.id : 0,
        direction: "forward"
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
      /** The items with their grid key, the uploads in progress appended. */
      displayItems() {
        const items = this.items.map((item) => {
          const key = keyOf(item);
          const batches = this.uploadTargets[key];
          return batches ? { ...item, key, uploading: true, progress: Math.min(...Object.values(batches)) } : { ...item, key };
        });
        const uploads = this.uploads.filter((upload) => upload.parent === this.folderId);
        return items.concat(uploads.map((upload) => ({ ...upload, key: keyOf(upload) })));
      },
      /** What both views take; each view only what it declares. */
      viewProps() {
        const common = {
          items: this.displayItems,
          selection: this.selection,
          selectable: this.canWrite,
          draggable: this.canWrite,
          canDrop: this.canDropOnItem,
          dropTargetKey: this.itemDropTargetKey,
          hasMore: this.page < this.pages,
          loading: this.loading,
          loadingMore: this.loadingMore,
          entriesFor: this.entriesFor,
          folderUrl: this.folderUrl
        };
        return this.view === "tiles" ? { ...common, level: this.level, direction: this.direction } : { ...common, canWrite: this.canWrite, likeStates: this.likeStates };
      },
      crumbs() {
        return (this.path || []).map((crumb) => ({ ...crumb, url: this.folderUrl(crumb.id) }));
      },
      /**
       * The bar's values: the filters, and the sort — the select shows an offered key;
       * `default` (and a key it does not offer) is its label.
       */
      filterValues() {
        const select = this.filters.find((filter) => filter.key === "sort");
        const offered = ((select == null ? void 0 : select.options) || []).some((option) => option.value === this.sort);
        return { ...this.query, sort: offered ? this.sort : "" };
      },
      selectionEntries() {
        return [
          { id: "select-all", sortOrder: 10, icon: "checks", label: vue.i18n.t("CfilesModule.base", "Select all"), onClick: () => this.selectAll() },
          { id: "move", sortOrder: 20, icon: "arrows-left-right", label: vue.i18n.t("CfilesModule.base", "Move"), onClick: () => this.openMove(this.selectedItems) },
          { id: "delete", sortOrder: 30, icon: "trash", label: vue.i18n.t("CfilesModule.base", "Delete"), onClick: () => this.confirmDelete(this.selectedItems) },
          { id: "divider", sortOrder: 90, divider: true },
          { id: "clear", sortOrder: 100, icon: "x", label: vue.i18n.t("CfilesModule.base", "Clear selection"), onClick: () => this.clearSelection() }
        ];
      },
      viewOptions() {
        return [
          { value: "tiles", icon: "layout-grid", label: vue.i18n.t("CfilesModule.base", "Tiles") },
          { value: "list", icon: "list", label: vue.i18n.t("CfilesModule.base", "List") }
        ];
      },
      titleLabel() {
        return vue.i18n.t("CfilesModule.base", "Files");
      },
      rootLabel() {
        return vue.i18n.t("CfilesModule.base", "Files");
      },
      viewLabel() {
        return vue.i18n.t("CfilesModule.base", "View");
      },
      settingsLabel() {
        return vue.i18n.t("CfilesModule.base", "Settings");
      },
      dropLabel() {
        return vue.i18n.t("CfilesModule.base", "Drop files here to upload them");
      },
      refusedLabel() {
        return vue.i18n.t("CfilesModule.base", "You cannot upload files here");
      },
      emptyTitle() {
        return vue.i18n.t("CfilesModule.base", "This folder is empty.");
      },
      resultsLabel() {
        return this.folder ? vue.i18n.t("CfilesModule.base", "{count, plural, one{# result} other{# results}} in this folder and its subfolders", { count: this.total }) : vue.i18n.t("CfilesModule.base", "{count, plural, one{# result} other{# results}} in all files", { count: this.total });
      },
      noResultsTitle() {
        return this.folder ? vue.i18n.t("CfilesModule.base", "No results in this folder and its subfolders.") : vue.i18n.t("CfilesModule.base", "No results in all files.");
      },
      resetFiltersLabel() {
        return vue.i18n.t("CfilesModule.base", "Reset filters");
      },
      emptyHint() {
        return this.canWrite ? vue.i18n.t("CfilesModule.base", "Drop files here or use the buttons above.") : vue.i18n.t("CfilesModule.base", "Unfortunately you have no permission to upload/edit files.");
      },
      createTitle() {
        return vue.i18n.t("CfilesModule.base", "Add folder");
      },
      editTitle() {
        return this.editItem && this.editItem.type === "folder" ? vue.i18n.t("CfilesModule.base", "Edit folder") : vue.i18n.t("CfilesModule.base", "Edit file");
      }
    },
    created() {
      this.loadSeq = 0;
      this.requested = { folderId: this.folderId, push: false };
      this.alignUrl();
    },
    mounted() {
      window.addEventListener("popstate", this.onPopState);
      document.addEventListener("keydown", this.onKeydown);
      this.openRequestedEdit();
      const urlFolderId = this.folderIdFromUrl();
      if (urlFolderId !== null && (urlFolderId || null) !== this.folderId) {
        this.open(urlFolderId || null, { push: false });
      }
    },
    beforeUnmount() {
      window.removeEventListener("popstate", this.onPopState);
      document.removeEventListener("keydown", this.onKeydown);
    },
    methods: {
      keyOf,
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
      /**
       * The page URL of a level, with the filters and the sort the bar shows — what the bar
       * writes into the URL itself, so a folder opened (or a link copied) keeps them.
       */
      folderUrl(folderId) {
        const params = new URLSearchParams({ fid: String(folderId || 0) });
        Object.entries(this.filterValues).forEach(([key, value]) => {
          if (value !== "" && value !== null && value !== void 0) {
            params.set(key, value);
          }
        });
        return this.browseUrl + (this.browseUrl.indexOf("?") === -1 ? "?" : "&") + params.toString();
      },
      /**
       * Puts the page URL's filters and sort in line with what the first page was built with
       * (`initialFilters`, the payload's sort): a value the server trimmed or refused, or a
       * sort it did not use, would otherwise be applied by the bar on mount — a second load,
       * a `422`, or `sort=default`, which forgets the user's stored sort.
       */
      alignUrl() {
        const params = new URLSearchParams(window.location.search);
        const before = params.toString();
        Object.entries(this.query).forEach(([key, value]) => {
          if (value === "" || value === null || value === void 0) {
            params.delete(key);
          } else {
            params.set(key, value);
          }
        });
        if (params.has("sort") && params.get("sort") !== this.filterValues.sort) {
          params.delete("sort");
        }
        if (params.toString() === before) {
          return;
        }
        const query = params.toString();
        const path = window.location.pathname + (query ? "?" + query : "") + window.location.hash;
        const state = window.history.state;
        const next = state && typeof state === "object" && typeof state.url === "string" ? { ...state, url: window.location.origin + path } : state;
        window.history.replaceState(next, "", path);
      },
      /** The values of the list's filters the page URL carries (`''` = not set). */
      queryFromUrl() {
        const params = new URLSearchParams(window.location.search);
        return Object.fromEntries(Object.keys(this.query).map((key) => [key, params.get(key) ?? ""]));
      },
      folderIdFromUrl() {
        const value = new URLSearchParams(window.location.search).get("fid");
        return value === null ? null : parseInt(value, 10) || 0;
      },
      applyPayload(payload) {
        this.direction = (payload.path || []).length >= (this.path || []).length ? "forward" : "back";
        this.level = payload.folder ? payload.folder.id : 0;
        this.folder = payload.folder;
        this.path = payload.path;
        this.items = payload.results;
        this.resultsMode = payload.resultsMode === true;
        this.likeStates = payload.likeStates || {};
        this.sort = payload.sort || SORT_DEFAULT;
        this.total = payload.total;
        this.page = payload.page;
        this.pages = payload.pages;
        this.selection = [];
        this.anchorKey = null;
      },
      /**
       * Loads a level and shows it. Returns a promise that settles once it is shown (or
       * overtaken, or failed); a later call overtakes an earlier one still on its way.
       */
      open(folderId, { push = true } = {}) {
        const seq = ++this.loadSeq;
        this.requested = { folderId, push };
        this.loading = true;
        this.loadingMore = false;
        return loadItems(this.contentContainerId, folderId, {
          sort: this.sort,
          pageSize: this.pageSize(),
          filters: this.query
        }).then((payload) => {
          if (seq !== this.loadSeq) {
            return;
          }
          this.applyPayload(payload);
          this.loading = false;
          if (push && this.folderIdFromUrl() !== (folderId || 0)) {
            window.history.pushState({ cfiles: { folderId } }, "", this.folderUrl(folderId));
          }
        }).catch((e) => {
          if (seq !== this.loadSeq) {
            return;
          }
          this.loading = false;
          vue.log.error(e, true);
        });
      },
      /** The level shown — or, while one is loading, the level on its way. */
      targetFolderId() {
        return this.loading ? this.requested.folderId : this.folderId;
      },
      onPopState() {
        const target = this.folderIdFromUrl() || null;
        const query = this.queryFromUrl();
        const filtersChanged = JSON.stringify(query) !== JSON.stringify(this.query);
        const urlSort = new URLSearchParams(window.location.search).get("sort");
        const select = this.filters.find((filter) => filter.key === "sort");
        const sort = ((select == null ? void 0 : select.options) || []).some((option) => option.value === urlSort) ? urlSort : this.sort;
        const sortChanged = sort !== this.sort;
        this.query = query;
        this.sort = sort;
        if (filtersChanged || sortChanged || target !== this.targetFolderId()) {
          this.open(target, { push: false });
        }
      },
      /** Reloads the level shown — or the one still opening, keeping its history entry. */
      reload() {
        return this.loading ? this.open(this.requested.folderId, { push: this.requested.push }) : this.open(this.folderId, { push: false });
      },
      /** The page the current view asks for. */
      pageSize() {
        return PAGE_SIZES[this.view] || PAGE_SIZES.list;
      },
      /**
       * Applies what the FilterBar emits: the filters and the sort select. Nothing changed
       * (the bar's first look at a URL the page was built from) is no reload.
       *
       * A cleared select (`''`) asks for `default` explicitly: sending no sort would have the
       * server re-apply the stored one, and the clear would do nothing. `default` is the
       * module's order, and the server forgets the stored one.
       */
      onFilters(values) {
        const { sort: chosen, ...filters } = values;
        const sort = chosen || SORT_DEFAULT;
        const query = { ...this.query, ...filters };
        if (sort === this.sort && JSON.stringify(query) === JSON.stringify(this.query)) {
          return;
        }
        this.sort = sort;
        this.query = query;
        this.reload();
      },
      /**
       * Clears the filters, keeps the sort — the empty result list's way out. The bar takes
       * the new values as they are (a `modelValue` from outside), so this is one load.
       */
      resetFilters() {
        this.query = Object.fromEntries(Object.keys(this.query).map((key) => [key, ""]));
        this.reload();
      },
      /**
       * Switches display, remembers it and reloads with the new view's page size.
       *
       * The preference is stored on its own (`PATCH preferences`), fire-and-forget: failing
       * to remember it is logged, the switch happens all the same.
       */
      setView(view) {
        if (view === this.view) {
          return;
        }
        this.view = view;
        savePreferences({ view }).catch((e) => vue.log.error(e, true));
        this.reload();
      },
      loadMore() {
        if (this.loading || this.loadingMore || this.page >= this.pages) {
          return;
        }
        this.loadingMore = true;
        const seq = this.loadSeq;
        const folderId = this.folderId;
        const stale = () => seq !== this.loadSeq || folderId !== this.folderId;
        loadItems(this.contentContainerId, folderId, {
          sort: this.sort,
          page: this.page + 1,
          pageSize: this.pageSize(),
          filters: this.query
        }).then((payload) => {
          if (stale()) {
            return;
          }
          this.items = this.items.concat(payload.results);
          this.likeStates = { ...this.likeStates, ...payload.likeStates || {} };
          this.page = payload.page;
          this.pages = payload.pages;
          this.total = payload.total;
          this.loadingMore = false;
        }).catch((e) => {
          if (stale()) {
            return;
          }
          this.loadingMore = false;
          vue.log.error(e, true);
        });
      },
      /**
       * Flips one item, or — with `range` (Shift) — adds everything between the last item
       * clicked and this one, in the order both views show. A Shift-click without an
       * earlier click (or after the anchor left the page) is a plain toggle.
       */
      toggleSelect(item, { range } = {}) {
        const key = keyOf(item);
        const keys = this.items.map(keyOf);
        if (range && this.anchorKey !== null && keys.includes(this.anchorKey) && keys.includes(key)) {
          const [from, to] = [keys.indexOf(this.anchorKey), keys.indexOf(key)].sort((a, b) => a - b);
          const span = keys.slice(from, to + 1);
          this.selection = keys.filter((k) => this.selection.includes(k) || span.includes(k));
        } else if (this.selection.includes(key)) {
          this.selection = this.selection.filter((k) => k !== key);
        } else {
          this.selection = this.selection.concat(key);
        }
        this.anchorKey = key;
      },
      /**
       * Selects every LOADED item.
       *
       * Deliberately not "everything in this folder": with paging that would arm the delete
       * action with rows the reader has never seen.
       */
      selectAll() {
        this.selection = this.items.map(keyOf);
      },
      clearSelection() {
        this.selection = [];
        this.anchorKey = null;
      },
      onKeydown(event) {
        if (event.key !== "Escape" || !this.selection.length || event.defaultPrevented || this.showMove || this.showCreate || this.showEdit) {
          return;
        }
        this.clearSelection();
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
            icon: isFolder ? "folder-open-filled" : "download",
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
            icon: "arrows-left-right",
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
      moveTo(targetFolderId, items) {
        if (!items.length || items.every((item) => (item.parentFolderId ?? null) === targetFolderId)) {
          this.showMove = false;
          return;
        }
        this.moveBusy = true;
        moveItems(this.contentContainerId, items, targetFolderId).then((response) => {
          this.moveBusy = false;
          this.showMove = false;
          this.moveItemsList = [];
          this.reportErrors(response.errors);
          this.reload();
        }).catch((response) => {
          this.moveBusy = false;
          const first = response && response.errors && response.errors[0];
          this.moveError = first ? first.message : null;
          if (!this.showMove) {
            vue.log.error(response, true);
            this.reload();
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
      /**
       * Uploads a batch into `parentId` (the open level by default).
       *
       * Into the open level each file gets a placeholder after the items (kept with that
       * level: `parent`); into a folder shown here (`targetKey`) that folder shows the
       * progress instead. A batch into a level not open says where it went (`title`) once
       * it is in. The level reloads only when the upload shows on it, and the placeholders
       * stay until the reloaded rows are there.
       */
      upload(files, parentId = this.folderId, { targetKey = null, title = null } = {}) {
        var _a;
        if (!files || !files.length || !this.canWrite) {
          return;
        }
        const batch = ++uploadSeq;
        const intoLevel = parentId === this.folderId;
        const folderTitle = title ?? (intoLevel ? (_a = this.folder) == null ? void 0 : _a.title : null);
        const placeholders = intoLevel ? Array.from(files).map((file) => ({
          type: "upload",
          id: ++uploadSeq,
          parent: parentId,
          title: file.name,
          uploading: true,
          progress: 0,
          icon: "file"
        })) : [];
        const ids = placeholders.map((upload) => upload.id);
        const mine = (upload) => ids.includes(upload.id);
        const progress = (percent) => {
          if (intoLevel) {
            this.uploads = this.uploads.map((upload) => mine(upload) ? { ...upload, progress: percent } : upload);
          } else if (targetKey) {
            this.uploadTargets = {
              ...this.uploadTargets,
              [targetKey]: { ...this.uploadTargets[targetKey], [batch]: percent }
            };
          }
        };
        const done = () => {
          this.uploads = this.uploads.filter((upload) => !mine(upload));
          if (targetKey && this.uploadTargets[targetKey]) {
            const { [batch]: gone, ...others } = this.uploadTargets[targetKey];
            const { [targetKey]: all, ...rest } = this.uploadTargets;
            this.uploadTargets = Object.keys(others).length ? { ...rest, [targetKey]: others } : rest;
          }
        };
        this.uploads = this.uploads.concat(placeholders);
        progress(0);
        uploadFiles(this.contentContainerId, parentId, files, progress).then((response) => {
          this.reportUploadErrors(response.errors);
          const shown = parentId === this.targetFolderId() || targetKey !== null && this.items.some((item) => keyOf(item) === targetKey);
          const count = (response.results || []).length;
          if (parentId !== this.targetFolderId() && count) {
            vue.status("success", vue.i18n.t("CfilesModule.base", "{count, plural, one{# file} other{# files}} uploaded to {folder}", {
              count,
              folder: folderTitle || this.rootLabel
            }));
          }
          if (shown) {
            this.reload().then(done);
          } else {
            done();
          }
        }).catch((response) => {
          done();
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
      /** Whether a drag carries desktop files rather than one of our own items. */
      isFileDrag(event) {
        var _a;
        return Array.prototype.includes.call(((_a = event == null ? void 0 : event.dataTransfer) == null ? void 0 : _a.types) || [], "Files");
      },
      /**
       * A folder takes desktop files and the items being dragged — never itself, nor
       * anything from a reader who may not write.
       */
      canDropOnItem(item, event) {
        if (!this.canWrite || item.type !== "folder") {
          return false;
        }
        if (this.isFileDrag(event)) {
          return true;
        }
        return this.dragged.length > 0 && !this.dragged.some((dragged) => keyOf(dragged) === keyOf(item));
      },
      canDropOnCrumb(id, event) {
        return this.canWrite && (this.isFileDrag(event) || this.dragged.length > 0);
      },
      onDragStart(item) {
        this.dragged = this.selection.includes(keyOf(item)) ? this.selectedItems : [item];
      },
      /**
       * Ends a drag — on a drop (no `drag-leave` follows one, so the target is cleared here)
       * and on the `drag-end` the browser fires after it or after a cancelled drag.
       * Idempotent: whichever comes second finds nothing left to clear.
       */
      onDragEnd() {
        this.dragged = [];
        this.itemDropTargetKey = null;
        this.crumbDropTargetId = void 0;
      },
      onDropOnItem(folder, event) {
        const items = this.dragged;
        this.onDragEnd();
        if (this.isFileDrag(event)) {
          this.upload(event.dataTransfer.files, folder.id, { targetKey: keyOf(folder), title: folder.title });
          return;
        }
        this.moveTo(folder.id, items);
      },
      onDropOnCrumb(id, event) {
        const items = this.dragged;
        this.onDragEnd();
        if (this.isFileDrag(event)) {
          const crumb = this.path.find((level) => level.id === id);
          this.upload(event.dataTransfer.files, id, { title: crumb ? crumb.title : null });
          return;
        }
        this.moveTo(id, items);
      }
    }
  };
  const _hoisted_1$1 = { class: "cfiles-browser" };
  const _hoisted_2 = ["href", "aria-label", "title"];
  const _hoisted_3 = {
    key: 0,
    class: "cfiles-results text-muted",
    role: "status"
  };
  const _hoisted_4 = { class: "mb-2" };
  const _hoisted_5 = { class: "mb-0" };
  const _hoisted_6 = { class: "mb-0" };
  function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_ViewSwitch = vue$1.resolveComponent("ViewSwitch");
    const _component_AddMenu = vue$1.resolveComponent("AddMenu");
    const _component_FilterBar = vue$1.resolveComponent("FilterBar");
    const _component_PageToolbar = vue$1.resolveComponent("PageToolbar");
    const _component_SelectionMenu = vue$1.resolveComponent("SelectionMenu");
    const _component_PathBar = vue$1.resolveComponent("PathBar");
    const _component_DropZone = vue$1.resolveComponent("DropZone");
    const _component_CfilesItemForm = vue$1.resolveComponent("CfilesItemForm");
    const _component_UiModal = vue$1.resolveComponent("UiModal");
    const _component_MoveDialog = vue$1.resolveComponent("MoveDialog");
    return vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_1$1, [
      vue$1.createVNode(_component_PageToolbar, { title: $options.titleLabel }, {
        actions: vue$1.withCtx(() => [
          vue$1.createVNode(_component_ViewSwitch, {
            "model-value": $data.view,
            options: $options.viewOptions,
            label: $options.viewLabel,
            "onUpdate:modelValue": $options.setView
          }, null, 8, ["model-value", "options", "label", "onUpdate:modelValue"]),
          $props.settingsUrl ? (vue$1.openBlock(), vue$1.createElementBlock("a", {
            key: 0,
            href: $props.settingsUrl,
            class: "btn btn-light c-icon-button",
            "aria-label": $options.settingsLabel,
            title: $options.settingsLabel
          }, [..._cache[17] || (_cache[17] = [
            vue$1.createElementVNode("i", {
              class: "ti ti-settings",
              "aria-hidden": "true"
            }, null, -1)
          ])], 8, _hoisted_2)) : vue$1.createCommentVNode("", true),
          $props.canWrite ? (vue$1.openBlock(), vue$1.createBlock(_component_AddMenu, {
            key: 1,
            "handlers-html": $props.createHandlersHtml,
            onUpload: $options.pickFiles,
            onCreateFolder: _cache[0] || (_cache[0] = ($event) => $data.showCreate = true)
          }, null, 8, ["handlers-html", "onUpload"])) : vue$1.createCommentVNode("", true)
        ]),
        default: vue$1.withCtx(() => [
          $props.filters.length ? (vue$1.openBlock(), vue$1.createBlock(_component_FilterBar, {
            key: 0,
            filters: $props.filters,
            "model-value": $options.filterValues,
            "id-prefix": "cfiles-filter",
            "onUpdate:modelValue": $options.onFilters
          }, null, 8, ["filters", "model-value", "onUpdate:modelValue"])) : vue$1.createCommentVNode("", true)
        ]),
        _: 1
      }, 8, ["title"]),
      vue$1.createVNode(_component_DropZone, {
        class: "cfiles-browser__card",
        accept: $props.canWrite,
        label: $options.dropLabel,
        "refused-label": $options.refusedLabel,
        onDrop: _cache[7] || (_cache[7] = (files) => $options.upload(files, $options.folderId))
      }, {
        default: vue$1.withCtx(() => [
          vue$1.createVNode(_component_PathBar, {
            path: $options.crumbs,
            "root-label": $options.rootLabel,
            "root-url": $options.folderUrl(null),
            "can-drop": $options.canDropOnCrumb,
            "drop-target-id": $data.crumbDropTargetId,
            onNavigate: $options.open,
            onDragOver: _cache[1] || (_cache[1] = (id) => {
              $data.crumbDropTargetId = id;
            }),
            onDragLeave: _cache[2] || (_cache[2] = (id) => {
              if ($data.crumbDropTargetId === id) $data.crumbDropTargetId = void 0;
            }),
            onDropOn: $options.onDropOnCrumb
          }, {
            end: vue$1.withCtx(() => [
              vue$1.createVNode(_component_SelectionMenu, {
                count: $data.selection.length,
                "menu-id": "cfiles.selection",
                entries: $options.selectionEntries,
                context: { items: $options.selectedItems }
              }, null, 8, ["count", "entries", "context"])
            ]),
            _: 1
          }, 8, ["path", "root-label", "root-url", "can-drop", "drop-target-id", "onNavigate", "onDropOn"]),
          $data.resultsMode ? (vue$1.openBlock(), vue$1.createElementBlock("p", _hoisted_3, vue$1.toDisplayString($options.resultsLabel), 1)) : vue$1.createCommentVNode("", true),
          (vue$1.openBlock(), vue$1.createBlock(vue$1.resolveDynamicComponent($data.view === "tiles" ? "FileTiles" : "ItemList"), vue$1.mergeProps($options.viewProps, {
            onOpen: _cache[4] || (_cache[4] = ($event) => $options.open($event.id)),
            onToggleSelect: $options.toggleSelect,
            onDragStart: $options.onDragStart,
            onDragEnd: $options.onDragEnd,
            onDragOver: _cache[5] || (_cache[5] = (item) => {
              $data.itemDropTargetKey = $options.keyOf(item);
            }),
            onDragLeave: _cache[6] || (_cache[6] = (item) => {
              if ($data.itemDropTargetKey === $options.keyOf(item)) $data.itemDropTargetKey = null;
            }),
            onDropOn: $options.onDropOnItem,
            onLoadMore: $options.loadMore
          }), {
            empty: vue$1.withCtx(() => [
              $data.resultsMode ? (vue$1.openBlock(), vue$1.createElementBlock(vue$1.Fragment, { key: 0 }, [
                vue$1.createElementVNode("p", _hoisted_4, [
                  vue$1.createElementVNode("strong", null, vue$1.toDisplayString($options.noResultsTitle), 1)
                ]),
                vue$1.createElementVNode("button", {
                  type: "button",
                  class: "btn btn-light btn-sm",
                  onClick: _cache[3] || (_cache[3] = (...args) => $options.resetFilters && $options.resetFilters(...args))
                }, vue$1.toDisplayString($options.resetFiltersLabel), 1)
              ], 64)) : (vue$1.openBlock(), vue$1.createElementBlock(vue$1.Fragment, { key: 1 }, [
                vue$1.createElementVNode("p", _hoisted_5, [
                  vue$1.createElementVNode("strong", null, vue$1.toDisplayString($options.emptyTitle), 1)
                ]),
                vue$1.createElementVNode("p", _hoisted_6, vue$1.toDisplayString($options.emptyHint), 1)
              ], 64))
            ]),
            _: 1
          }, 16, ["onToggleSelect", "onDragStart", "onDragEnd", "onDropOn", "onLoadMore"]))
        ]),
        _: 1
      }, 8, ["accept", "label", "refused-label"]),
      vue$1.createElementVNode("input", {
        ref: "fileInput",
        type: "file",
        multiple: "",
        class: "d-none",
        onChange: _cache[8] || (_cache[8] = (...args) => $options.onFilesPicked && $options.onFilesPicked(...args))
      }, null, 544),
      vue$1.createVNode(_component_UiModal, {
        show: $data.showCreate,
        "onUpdate:show": _cache[10] || (_cache[10] = ($event) => $data.showCreate = $event),
        title: $options.createTitle,
        onOpened: _cache[11] || (_cache[11] = ($event) => $options.focusForm("createForm"))
      }, {
        default: vue$1.withCtx(() => [
          $data.showCreate ? (vue$1.openBlock(), vue$1.createBlock(_component_CfilesItemForm, {
            key: 0,
            ref: "createForm",
            "content-container-id": $props.contentContainerId,
            "parent-folder-id": $options.folderId,
            onSaved: $options.onCreated,
            onCancel: _cache[9] || (_cache[9] = ($event) => $data.showCreate = false)
          }, null, 8, ["content-container-id", "parent-folder-id", "onSaved"])) : vue$1.createCommentVNode("", true)
        ]),
        _: 1
      }, 8, ["show", "title"]),
      vue$1.createVNode(_component_UiModal, {
        show: $data.showEdit,
        "onUpdate:show": _cache[13] || (_cache[13] = ($event) => $data.showEdit = $event),
        title: $options.editTitle,
        onOpened: _cache[14] || (_cache[14] = ($event) => $options.focusForm("editForm"))
      }, {
        default: vue$1.withCtx(() => [
          $data.showEdit ? (vue$1.openBlock(), vue$1.createBlock(_component_CfilesItemForm, {
            key: 0,
            ref: "editForm",
            item: $data.editItem,
            onSaved: $options.onUpdated,
            onCancel: _cache[12] || (_cache[12] = ($event) => $data.showEdit = false)
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
        onClose: _cache[15] || (_cache[15] = ($event) => $data.showMove = false),
        onConfirm: _cache[16] || (_cache[16] = (id) => $options.moveTo(id, $data.moveItemsList))
      }, null, 8, ["show", "content-container-id", "items", "busy", "error"])
    ]);
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
