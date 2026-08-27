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
  const _sfc_main$5 = {
    props: {
      path: { type: Array, default: () => [] },
      sort: { type: String, default: "name" },
      order: { type: String, default: "asc" },
      canWrite: { type: Boolean, default: false },
      selectionCount: { type: Number, default: 0 },
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
      "create-folder",
      "pick-files",
      "move-selection",
      "delete-selection",
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
      moveLabel() {
        return vue.i18n.t("CfilesModule.base", "Move");
      },
      deleteLabel() {
        return vue.i18n.t("CfilesModule.base", "Delete");
      },
      addFolderLabel() {
        return vue.i18n.t("CfilesModule.base", "Add folder");
      },
      addFilesLabel() {
        return vue.i18n.t("CfilesModule.base", "Add files");
      },
      rootLabel() {
        return vue.i18n.t("CfilesModule.base", "Files");
      },
      selectionLabel() {
        return vue.i18n.t("CfilesModule.base", "{count, plural, one{# selected} other{# selected}}", {
          count: this.selectionCount
        });
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
  const _hoisted_1$5 = { class: "cfiles-toolbar d-flex flex-wrap align-items-center gap-2" };
  const _hoisted_2$4 = ["aria-label"];
  const _hoisted_3$4 = { class: "breadcrumb mb-0" };
  const _hoisted_4$3 = ["onDragover", "onDrop"];
  const _hoisted_5$3 = { key: 0 };
  const _hoisted_6$3 = ["href", "onClick"];
  const _hoisted_7$1 = {
    key: 0,
    class: "cfiles-selection d-flex align-items-center gap-2"
  };
  const _hoisted_8$1 = { class: "text-muted small" };
  const _hoisted_9$1 = { class: "d-none d-sm-inline ms-1" };
  const _hoisted_10$1 = { class: "d-none d-sm-inline ms-1" };
  function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_DropdownMenu = vue$1.resolveComponent("DropdownMenu");
    return vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_1$5, [
      vue$1.createElementVNode("nav", {
        class: "cfiles-breadcrumb flex-grow-1 min-width-0",
        "aria-label": $options.breadcrumbLabel
      }, [
        vue$1.createElementVNode("ol", _hoisted_3$4, [
          (vue$1.openBlock(true), vue$1.createElementBlock(
            vue$1.Fragment,
            null,
            vue$1.renderList($options.crumbs, (crumb, index) => {
              return vue$1.openBlock(), vue$1.createElementBlock("li", {
                key: crumb.id ?? "top",
                class: vue$1.normalizeClass(["breadcrumb-item", { active: index === $options.crumbs.length - 1, "cfiles-crumb-drop": $props.dropTargetId === crumb.id }]),
                onDragover: ($event) => $options.onCrumbDragOver($event, crumb, index),
                onDragleave: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("crumb-drag-leave")),
                onDrop: ($event) => $options.onCrumbDrop($event, crumb, index)
              }, [
                index === $options.crumbs.length - 1 ? (vue$1.openBlock(), vue$1.createElementBlock(
                  "span",
                  _hoisted_5$3,
                  vue$1.toDisplayString($options.crumbTitle(crumb)),
                  1
                  /* TEXT */
                )) : (vue$1.openBlock(), vue$1.createElementBlock("a", {
                  key: 1,
                  href: $props.folderUrl(crumb.id),
                  onClick: ($event) => $options.onCrumbClick($event, crumb)
                }, vue$1.toDisplayString($options.crumbTitle(crumb)), 9, _hoisted_6$3))
              ], 42, _hoisted_4$3);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ])
      ], 8, _hoisted_2$4),
      $props.selectionCount ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_7$1, [
        vue$1.createElementVNode(
          "span",
          _hoisted_8$1,
          vue$1.toDisplayString($options.selectionLabel),
          1
          /* TEXT */
        ),
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-light btn-sm",
          onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("move-selection"))
        }, [
          _cache[5] || (_cache[5] = vue$1.createElementVNode(
            "i",
            {
              class: "fa fa-arrows",
              "aria-hidden": "true"
            },
            null,
            -1
            /* CACHED */
          )),
          vue$1.createTextVNode(
            " " + vue$1.toDisplayString($options.moveLabel),
            1
            /* TEXT */
          )
        ]),
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-danger btn-sm",
          onClick: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("delete-selection"))
        }, [
          _cache[6] || (_cache[6] = vue$1.createElementVNode(
            "i",
            {
              class: "fa fa-trash",
              "aria-hidden": "true"
            },
            null,
            -1
            /* CACHED */
          )),
          vue$1.createTextVNode(
            " " + vue$1.toDisplayString($options.deleteLabel),
            1
            /* TEXT */
          )
        ])
      ])) : vue$1.createCommentVNode("v-if", true),
      vue$1.createVNode(_component_DropdownMenu, {
        "menu-id": "cfiles.sort",
        entries: $options.sortEntries,
        "toggle-aria-label": $options.sortLabel,
        "toggle-class": "btn btn-light btn-sm dropdown-toggle",
        "root-class": "cfiles-sort-menu",
        "align-end": true
      }, {
        toggle: vue$1.withCtx(() => [
          vue$1.createTextVNode(
            vue$1.toDisplayString($options.activeSortLabel),
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["entries", "toggle-aria-label"]),
      $props.canWrite ? (vue$1.openBlock(), vue$1.createElementBlock(
        vue$1.Fragment,
        { key: 1 },
        [
          vue$1.createElementVNode("button", {
            type: "button",
            class: "btn btn-light btn-sm",
            onClick: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("create-folder"))
          }, [
            _cache[7] || (_cache[7] = vue$1.createElementVNode(
              "i",
              {
                class: "fa fa-folder",
                "aria-hidden": "true"
              },
              null,
              -1
              /* CACHED */
            )),
            vue$1.createElementVNode(
              "span",
              _hoisted_9$1,
              vue$1.toDisplayString($options.addFolderLabel),
              1
              /* TEXT */
            )
          ]),
          vue$1.createElementVNode("button", {
            type: "button",
            class: "btn btn-accent btn-sm",
            onClick: _cache[4] || (_cache[4] = ($event) => _ctx.$emit("pick-files"))
          }, [
            _cache[8] || (_cache[8] = vue$1.createElementVNode(
              "i",
              {
                class: "fa fa-upload",
                "aria-hidden": "true"
              },
              null,
              -1
              /* CACHED */
            )),
            vue$1.createElementVNode(
              "span",
              _hoisted_10$1,
              vue$1.toDisplayString($options.addFilesLabel),
              1
              /* TEXT */
            )
          ])
        ],
        64
        /* STABLE_FRAGMENT */
      )) : vue$1.createCommentVNode("v-if", true)
    ]);
  }
  const BrowserToolbar = /* @__PURE__ */ _export_sfc(_sfc_main$5, [["render", _sfc_render$5]]);
  const SUPPRESSED_CORE_ENTRIES = ["edit", "delete", "permalink", "pin", "move", "archive"];
  const WEEK_IN_SECONDS = 7 * 24 * 60 * 60;
  const RELATIVE_UNITS = [
    ["day", 24 * 60 * 60],
    ["hour", 60 * 60],
    ["minute", 60],
    ["second", 1]
  ];
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
      displayTitle() {
        return this.item.title;
      },
      linkUrl() {
        return this.isFolder ? this.folderUrl(this.item.id) : this.item.url || "#";
      },
      iconClass() {
        if (this.isFolder) {
          return "fa fa-folder cfiles-icon-folder";
        }
        return "fa " + (MIME_ICONS[this.item.mimeIcon] || "fa-file-o") + " cfiles-icon-file";
      },
      meta() {
        const parts = [];
        if (this.isFolder) {
          if (typeof this.item.itemCount === "number") {
            parts.push(vue.i18n.t("CfilesModule.base", "{count, plural, =0{empty} one{# item} other{# items}}", {
              count: this.item.itemCount
            }));
          }
        } else {
          parts.push(this.formattedSize);
        }
        parts.push(this.relativeTime);
        if (this.item.description) {
          parts.push(this.item.description);
        }
        return parts.filter(Boolean).join(" · ");
      },
      formattedSize() {
        const size = this.item.size || 0;
        const units = ["B", "KB", "MB", "GB", "TB"];
        let value = size;
        let unit = 0;
        while (value >= 1024 && unit < units.length - 1) {
          value /= 1024;
          unit++;
        }
        return (unit === 0 ? value : value.toFixed(1)) + " " + units[unit];
      },
      /**
       * Recent changes read as "3 days ago", older ones as a date — the same split the
       * platform's own `TimeAgo` widget makes, and what the server-rendered list showed
       * before. Formatted in the HumHub language rather than the browser's, which is what
       * `toLocaleDateString()` with no locale would have used.
       */
      relativeTime() {
        const stamp = this.item.updatedAt || this.item.createdAt;
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
  const _hoisted_2$3 = {
    key: 0,
    class: "cfiles-row-select"
  };
  const _hoisted_3$3 = ["checked", "aria-label"];
  const _hoisted_4$2 = { class: "cfiles-row-icon" };
  const _hoisted_5$2 = ["src"];
  const _hoisted_6$2 = { class: "flex-grow-1 min-width-0" };
  const _hoisted_7 = { class: "mb-0 text-truncate" };
  const _hoisted_8 = ["href"];
  const _hoisted_9 = ["title", "aria-label"];
  const _hoisted_10 = { class: "mb-0 text-truncate cfiles-row-meta" };
  const _hoisted_11 = { class: "cfiles-row-creator" };
  const _hoisted_12 = { class: "cfiles-row-controls" };
  function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_UserImage = vue$1.resolveComponent("UserImage");
    const _component_ContentControls = vue$1.resolveComponent("ContentControls");
    return vue$1.openBlock(), vue$1.createElementBlock("div", {
      class: vue$1.normalizeClass(["cfiles-row d-flex align-items-center gap-2", { "cfiles-row-drop": $props.dropTarget, selected: $props.selected }]),
      draggable: $props.draggable,
      onDragstart: _cache[2] || (_cache[2] = (...args) => $options.onDragStart && $options.onDragStart(...args)),
      onDragend: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("drag-end")),
      onDragover: _cache[4] || (_cache[4] = (...args) => $options.onDragOver && $options.onDragOver(...args)),
      onDragleave: _cache[5] || (_cache[5] = (...args) => $options.onDragLeave && $options.onDragLeave(...args)),
      onDrop: _cache[6] || (_cache[6] = (...args) => $options.onDrop && $options.onDrop(...args))
    }, [
      $props.selectable ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_2$3, [
        vue$1.createElementVNode("input", {
          type: "checkbox",
          class: "form-check-input",
          checked: $props.selected,
          "aria-label": $options.selectLabel,
          onChange: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("toggle-select", $props.item))
        }, null, 40, _hoisted_3$3)
      ])) : vue$1.createCommentVNode("v-if", true),
      vue$1.createElementVNode("div", _hoisted_4$2, [
        $props.item.previewUrl ? (vue$1.openBlock(), vue$1.createElementBlock("img", {
          key: 0,
          src: $props.item.previewUrl,
          alt: "",
          class: "cfiles-thumb"
        }, null, 8, _hoisted_5$2)) : (vue$1.openBlock(), vue$1.createElementBlock(
          "i",
          {
            key: 1,
            class: vue$1.normalizeClass($options.iconClass),
            "aria-hidden": "true"
          },
          null,
          2
          /* CLASS */
        ))
      ]),
      vue$1.createElementVNode("div", _hoisted_6$2, [
        vue$1.createElementVNode("h4", _hoisted_7, [
          vue$1.createElementVNode("a", {
            href: $options.linkUrl,
            onClick: _cache[1] || (_cache[1] = (...args) => $options.onOpen && $options.onOpen(...args))
          }, vue$1.toDisplayString($options.displayTitle), 9, _hoisted_8),
          $options.isPrivate ? (vue$1.openBlock(), vue$1.createElementBlock("i", {
            key: 0,
            class: "fa fa-lock text-muted ms-1",
            title: $options.privateLabel,
            "aria-label": $options.privateLabel
          }, null, 8, _hoisted_9)) : vue$1.createCommentVNode("v-if", true)
        ]),
        vue$1.createElementVNode(
          "h5",
          _hoisted_10,
          vue$1.toDisplayString($options.meta),
          1
          /* TEXT */
        )
      ]),
      vue$1.createElementVNode("div", _hoisted_11, [
        $props.item.creator ? (vue$1.openBlock(), vue$1.createBlock(
          _component_UserImage,
          vue$1.mergeProps({ key: 0 }, $props.item.creator, { size: 21 }),
          null,
          16
          /* FULL_PROPS */
        )) : vue$1.createCommentVNode("v-if", true)
      ]),
      vue$1.createElementVNode("div", _hoisted_12, [
        vue$1.createVNode(_component_ContentControls, {
          "content-id": $props.item.contentId,
          "view-context": "browser",
          entries: $props.entries,
          suppress: $data.SUPPRESSED_CORE_ENTRIES,
          context: { item: $props.item },
          "toggle-class": "nav-link dropdown-toggle cfiles-row-toggle",
          "toggle-aria-label": $options.actionsLabel
        }, null, 8, ["content-id", "entries", "suppress", "context", "toggle-aria-label"])
      ])
    ], 42, _hoisted_1$4);
  }
  const ItemRow = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4]]);
  const loadItems = (containerId, parent, { sort, order, page, pageSize } = {}) => {
    const params = {};
    if (parent) {
      params.parent = parent;
    }
    if (sort) {
      params.sort = sort;
      params.order = order || "asc";
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
  const uploadFiles = (containerId, parent, files, onProgress) => new Promise((resolve, reject) => {
    const form = new FormData();
    Array.prototype.forEach.call(files, (file) => form.append("files[]", file));
    if (parent) {
      form.append("parent", parent);
    }
    const request = new XMLHttpRequest();
    request.open("POST", vue.apiUrl("cfiles/" + containerId + "/files"));
    request.setRequestHeader("X-CSRF-Token", csrfToken());
    request.setRequestHeader("Accept", "application/json");
    request.upload.addEventListener("progress", (event) => {
      if (event.lengthComputable && typeof onProgress === "function") {
        onProgress(Math.round(event.loaded / event.total * 100));
      }
    });
    request.addEventListener("load", () => {
      let body = {};
      try {
        body = JSON.parse(request.responseText || "{}");
      } catch (e) {
        reject(new Error("Malformed upload response"));
        return;
      }
      if (request.status >= 200 && request.status < 300) {
        resolve(body);
      } else if (request.status === 422 && Array.isArray(body.results)) {
        resolve(body);
      } else {
        reject(new Error(request.statusText || "Upload failed"));
      }
    });
    request.addEventListener("error", () => reject(new Error("Upload failed")));
    request.send(form);
  });
  const descriptor = (item) => ({ type: item.type, id: item.id });
  const csrfToken = () => {
    const meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute("content") : "";
  };
  const keyOf = (item) => item.type + ":" + item.id;
  const _sfc_main$3 = {
    components: { ItemRow },
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
      entriesFor: { type: Function, required: true },
      folderUrl: { type: Function, required: true }
    },
    emits: ["open", "toggle-select", "load-more", "drag-start", "drag-end", "drop-on"],
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
  const _hoisted_4$1 = { class: "mb-0" };
  const _hoisted_5$1 = {
    key: 2,
    class: "text-center p-2"
  };
  const _hoisted_6$1 = ["disabled"];
  function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
    const _component_ItemRow = vue$1.resolveComponent("ItemRow");
    return vue$1.openBlock(), vue$1.createElementBlock("div", null, [
      $props.items.length ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_1$3, [
        (vue$1.openBlock(true), vue$1.createElementBlock(
          vue$1.Fragment,
          null,
          vue$1.renderList($props.items, (item) => {
            return vue$1.openBlock(), vue$1.createBlock(_component_ItemRow, {
              key: $options.keyOf(item),
              item,
              selected: $options.isSelected(item),
              selectable: $props.selectable,
              draggable: $props.draggable,
              "drop-target": $props.dropTargetKey === $options.keyOf(item),
              entries: $props.entriesFor(item),
              "folder-url": $props.folderUrl,
              onOpen: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("open", $event)),
              onToggleSelect: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("toggle-select", $event)),
              onDragStart: _cache[2] || (_cache[2] = ($event) => _ctx.$emit("drag-start", $event)),
              onDragEnd: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("drag-end")),
              onDropOn: _cache[4] || (_cache[4] = ($event) => _ctx.$emit("drop-on", $event))
            }, null, 8, ["item", "selected", "selectable", "draggable", "drop-target", "entries", "folder-url"]);
          }),
          128
          /* KEYED_FRAGMENT */
        ))
      ])) : !$props.loading ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_2$2, [
        vue$1.createElementVNode("p", _hoisted_3$2, [
          vue$1.createElementVNode(
            "strong",
            null,
            vue$1.toDisplayString($options.emptyTitle),
            1
            /* TEXT */
          )
        ]),
        vue$1.createElementVNode(
          "p",
          _hoisted_4$1,
          vue$1.toDisplayString($options.emptyHint),
          1
          /* TEXT */
        )
      ])) : vue$1.createCommentVNode("v-if", true),
      $props.hasMore ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_5$1, [
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-light btn-sm",
          disabled: $props.loadingMore,
          onClick: _cache[5] || (_cache[5] = ($event) => _ctx.$emit("load-more"))
        }, vue$1.toDisplayString($props.loadingMore ? $options.loadingLabel : $options.moreLabel), 9, _hoisted_6$1)
      ])) : vue$1.createCommentVNode("v-if", true)
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
        vue$1.createElementVNode(
          "button",
          {
            type: "button",
            class: "btn btn-light",
            onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("close"))
          },
          vue$1.toDisplayString($options.cancelLabel),
          1
          /* TEXT */
        ),
        vue$1.createElementVNode("button", {
          type: "button",
          class: "btn btn-primary",
          disabled: $data.selectedId === void 0 || $props.busy,
          onClick: _cache[1] || (_cache[1] = ($event) => _ctx.$emit("confirm", $data.selectedId))
        }, vue$1.toDisplayString($options.moveLabel), 9, _hoisted_6)
      ]),
      default: vue$1.withCtx(() => [
        vue$1.createElementVNode(
          "p",
          _hoisted_1$2,
          vue$1.toDisplayString($options.intro),
          1
          /* TEXT */
        ),
        vue$1.createElementVNode("div", _hoisted_2$1, [
          (vue$1.openBlock(true), vue$1.createElementBlock(
            vue$1.Fragment,
            null,
            vue$1.renderList($options.flatTree, (node) => {
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
                _cache[3] || (_cache[3] = vue$1.createElementVNode(
                  "i",
                  {
                    class: "fa fa-folder text-muted",
                    "aria-hidden": "true"
                  },
                  null,
                  -1
                  /* CACHED */
                )),
                vue$1.createTextVNode(
                  " " + vue$1.toDisplayString(node.isTop ? $options.rootLabel : node.title),
                  1
                  /* TEXT */
                )
              ], 46, _hoisted_3$1);
            }),
            128
            /* KEYED_FRAGMENT */
          ))
        ]),
        $props.error ? (vue$1.openBlock(), vue$1.createElementBlock(
          "p",
          _hoisted_5,
          vue$1.toDisplayString($props.error),
          1
          /* TEXT */
        )) : vue$1.createCommentVNode("v-if", true)
      ]),
      _: 1
      /* STABLE */
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
      editKey: { type: String, default: null }
    },
    data() {
      return {
        folder: this.listing.folder,
        path: this.listing.path,
        items: this.listing.results,
        sort: this.listing.sort,
        order: this.listing.order,
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
        this.sort = payload.sort;
        this.order = payload.order;
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
        loadItems(this.contentContainerId, folderId, { sort: this.sort, order: this.order }).then((payload) => {
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
      loadMore() {
        if (this.loadingMore || this.page >= this.pages) {
          return;
        }
        this.loadingMore = true;
        loadItems(this.contentContainerId, this.folderId, { sort: this.sort, order: this.order, page: this.page + 1 }).then((payload) => {
          this.items = this.items.concat(payload.results);
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
          (response.errors || []).forEach((error) => {
            vue.status("error", error.fileName + ": " + (error.messages || []).join(" "));
          });
          this.reload();
        }).catch((e) => {
          this.uploadProgress = null;
          vue.log.error(e, true);
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
    return vue$1.openBlock(), vue$1.createElementBlock(
      "div",
      {
        class: vue$1.normalizeClass(["cfiles-browser", { "cfiles-dropping": $options.fileDragActive }]),
        onDragenter: _cache[15] || (_cache[15] = (...args) => $options.onFileDragEnter && $options.onFileDragEnter(...args)),
        onDragover: _cache[16] || (_cache[16] = (...args) => $options.onFileDragOver && $options.onFileDragOver(...args)),
        onDragleave: _cache[17] || (_cache[17] = (...args) => $options.onFileDragLeave && $options.onFileDragLeave(...args)),
        onDrop: _cache[18] || (_cache[18] = (...args) => $options.onFileDrop && $options.onFileDrop(...args))
      },
      [
        vue$1.createElementVNode("div", _hoisted_1$1, [
          vue$1.createVNode(_component_BrowserToolbar, {
            path: $data.path,
            sort: $data.sort,
            order: $data.order,
            "can-write": $props.canWrite,
            "selection-count": $data.selection.length,
            "drop-target-id": $data.crumbDropTargetId,
            "folder-url": $options.folderUrl,
            onOpen: $options.open,
            onSort: $options.setSort,
            onCreateFolder: _cache[0] || (_cache[0] = ($event) => $data.showCreate = true),
            onPickFiles: $options.pickFiles,
            onMoveSelection: _cache[1] || (_cache[1] = ($event) => $options.openMove($options.selectedItems)),
            onDeleteSelection: _cache[2] || (_cache[2] = ($event) => $options.confirmDelete($options.selectedItems)),
            onCrumbDragOver: _cache[3] || (_cache[3] = ($event) => $data.crumbDropTargetId = $event),
            onCrumbDragLeave: _cache[4] || (_cache[4] = ($event) => $data.crumbDropTargetId = void 0),
            onCrumbDrop: _cache[5] || (_cache[5] = ($event) => $options.moveTo($event))
          }, null, 8, ["path", "sort", "order", "can-write", "selection-count", "drop-target-id", "folder-url", "onOpen", "onSort", "onPickFiles"]),
          $data.uploadProgress !== null ? (vue$1.openBlock(), vue$1.createElementBlock("div", _hoisted_2, [
            vue$1.createElementVNode("div", {
              class: "progress-bar",
              role: "progressbar",
              style: vue$1.normalizeStyle({ width: $data.uploadProgress + "%" }),
              "aria-valuenow": $data.uploadProgress,
              "aria-valuemin": "0",
              "aria-valuemax": "100"
            }, vue$1.toDisplayString($data.uploadProgress) + "%", 13, _hoisted_3)
          ])) : vue$1.createCommentVNode("v-if", true),
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
            "entries-for": $options.entriesFor,
            "folder-url": $options.folderUrl,
            onOpen: _cache[6] || (_cache[6] = ($event) => $options.open($event.id)),
            onToggleSelect: $options.toggleSelect,
            onLoadMore: $options.loadMore,
            onDragStart: _cache[7] || (_cache[7] = ($event) => $data.dragged = [$event]),
            onDragEnd: _cache[8] || (_cache[8] = ($event) => $data.itemDropTargetKey = null),
            onDropOn: $options.onDropOnFolder
          }, null, 8, ["items", "selection", "selectable", "draggable", "drop-target-key", "has-more", "loading", "loading-more", "can-write", "entries-for", "folder-url", "onToggleSelect", "onLoadMore", "onDropOn"])
        ]),
        vue$1.createElementVNode(
          "input",
          {
            ref: "fileInput",
            type: "file",
            multiple: "",
            class: "d-none",
            onChange: _cache[9] || (_cache[9] = (...args) => $options.onFilesPicked && $options.onFilesPicked(...args))
          },
          null,
          544
          /* NEED_HYDRATION, NEED_PATCH */
        ),
        vue$1.createVNode(_component_UiModal, {
          show: $data.showCreate,
          "onUpdate:show": _cache[11] || (_cache[11] = ($event) => $data.showCreate = $event),
          title: $options.createTitle
        }, {
          default: vue$1.withCtx(() => [
            $data.showCreate ? (vue$1.openBlock(), vue$1.createBlock(_component_CfilesItemForm, {
              key: 0,
              "content-container-id": $props.contentContainerId,
              "parent-folder-id": $options.folderId,
              onSaved: $options.onCreated,
              onCancel: _cache[10] || (_cache[10] = ($event) => $data.showCreate = false)
            }, null, 8, ["content-container-id", "parent-folder-id", "onSaved"])) : vue$1.createCommentVNode("v-if", true)
          ]),
          _: 1
          /* STABLE */
        }, 8, ["show", "title"]),
        vue$1.createVNode(_component_UiModal, {
          show: $data.showEdit,
          "onUpdate:show": _cache[13] || (_cache[13] = ($event) => $data.showEdit = $event),
          title: $options.editTitle
        }, {
          default: vue$1.withCtx(() => [
            $data.showEdit ? (vue$1.openBlock(), vue$1.createBlock(_component_CfilesItemForm, {
              key: 0,
              item: $data.editItem,
              onSaved: $options.onUpdated,
              onCancel: _cache[12] || (_cache[12] = ($event) => $data.showEdit = false)
            }, null, 8, ["item", "onSaved"])) : vue$1.createCommentVNode("v-if", true)
          ]),
          _: 1
          /* STABLE */
        }, 8, ["show", "title"]),
        vue$1.createVNode(_component_MoveDialog, {
          show: $data.showMove,
          "content-container-id": $props.contentContainerId,
          items: $data.moveItemsList,
          busy: $data.moveBusy,
          error: $data.moveError,
          onClose: _cache[14] || (_cache[14] = ($event) => $data.showMove = false),
          onConfirm: $options.moveTo
        }, null, 8, ["show", "content-container-id", "items", "busy", "error", "onConfirm"])
      ],
      34
      /* CLASS, NEED_HYDRATION */
    );
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
          vue$1.createElementVNode(
            "button",
            {
              type: "button",
              class: "btn btn-light",
              onClick: _cache[3] || (_cache[3] = ($event) => _ctx.$emit("cancel"))
            },
            vue$1.toDisplayString($options.cancelLabel),
            1
            /* TEXT */
          ),
          vue$1.createVNode(_component_SubmitButton, { class: "btn btn-primary" }, {
            default: vue$1.withCtx(() => [
              vue$1.createTextVNode(
                vue$1.toDisplayString($options.saveLabel),
                1
                /* TEXT */
              )
            ]),
            _: 1
            /* STABLE */
          })
        ])
      ]),
      _: 1
      /* STABLE */
    }, 8, ["busy", "onSubmit"]);
  }
  const C1 = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
  vue.register("CfilesFileBrowser", C0);
  vue.register("CfilesItemForm", C1);
})(humhub.modules.vue, Vue);
//# sourceMappingURL=humhub.cfiles.vue.js.map
