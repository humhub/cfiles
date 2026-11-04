<template>
    <aside class="card content-card cfiles-details" :aria-labelledby="headingId">
        <div class="card-header">
            <div class="card-heading">
                <h2 :id="headingId" ref="heading" class="card-title" tabindex="-1">{{ titleLabel }}</h2>
            </div>
            <div class="card-header-actions">
                <button
                    type="button"
                    class="btn btn-ghost btn-icon-only"
                    :aria-label="closeLabel"
                    :title="closeLabel"
                    @click="$emit('close')"
                ><i class="ti ti-x" aria-hidden="true"></i></button>
            </div>
        </div>

        <div class="card-tabs">
            <div class="nav nav-pills nav-pills-secondary" role="group" :aria-label="titleLabel">
                <button
                    v-for="entry in tabs"
                    :key="entry.id"
                    type="button"
                    class="nav-link"
                    :class="{ active: tab === entry.id }"
                    :aria-pressed="tab === entry.id ? 'true' : 'false'"
                    :data-tab="entry.id"
                    @click="tab = entry.id"
                ><i :class="'ti ti-' + entry.icon" aria-hidden="true"></i> {{ entry.label }}</button>
            </div>
        </div>

        <div class="card-body">
            <div class="cfiles-details-item">
                <span class="cfiles-row-icon">
                    <img v-if="item.previewUrl" :src="item.previewUrl" alt="" class="cfiles-thumb" />
                    <i v-else :class="iconClass" aria-hidden="true"></i>
                </span>
                <div class="cfiles-row-body">
                    <div class="cfiles-row-name text-truncate">{{ item.title }}</div>
                    <div class="cfiles-row-meta">{{ summary }}</div>
                </div>
            </div>

            <template v-if="tab === 'info'">
                <section>
                    <h3 class="h6">{{ generalLabel }}</h3>
                    <dl class="row cfiles-details-facts">
                        <dt class="col-4">{{ nameLabel }}</dt>
                        <dd class="col-8">{{ item.title }}</dd>
                        <template v-if="!isFolder">
                            <dt class="col-4">{{ typeLabel }}</dt>
                            <dd class="col-8"><code>{{ item.mimeType || '–' }}</code></dd>
                            <dt class="col-4">{{ sizeLabel }}</dt>
                            <dd class="col-8">{{ size }}</dd>
                        </template>
                        <template v-else-if="typeof item.itemCount === 'number'">
                            <dt class="col-4">{{ contentsLabel }}</dt>
                            <dd class="col-8">{{ summary }}</dd>
                        </template>
                        <template v-if="location">
                            <dt class="col-4">{{ locationLabel }}</dt>
                            <dd class="col-8">{{ location }}</dd>
                        </template>
                    </dl>
                </section>

                <hr />

                <section>
                    <h3 class="h6">{{ peopleLabel }}</h3>
                    <dl class="row cfiles-details-facts">
                        <template v-if="item.creator">
                            <dt class="col-4">{{ createdByLabel }}</dt>
                            <dd class="col-8 d-flex align-items-center gap-1">
                                <UserImage v-bind="item.creator" :size="21" />
                                <a :href="item.creator.url">{{ item.creator.displayName }}</a>
                            </dd>
                        </template>
                        <dt class="col-4">{{ createdLabel }}</dt>
                        <dd class="col-8">{{ formatDate(item.createdAt) }}</dd>
                        <dt class="col-4">{{ updatedLabel }}</dt>
                        <dd class="col-8">{{ formatDate(item.updatedAt || item.createdAt) }}</dd>
                    </dl>
                </section>

                <hr />

                <section>
                    <h3 class="h6">{{ accessLabel }}</h3>
                    <dl class="row cfiles-details-facts">
                        <dt class="col-4">{{ visibilityLabel }}</dt>
                        <dd class="col-8">{{ visibility }}</dd>
                        <template v-if="!isFolder">
                            <dt class="col-4">{{ downloadsLabel }}</dt>
                            <dd class="col-8">{{ item.downloadCount || 0 }}</dd>
                        </template>
                    </dl>
                </section>

                <template v-if="item.topics && item.topics.length">
                    <hr />

                    <section>
                        <h3 class="h6">{{ topicsLabel }}</h3>
                        <div class="d-flex flex-wrap gap-1">
                            <span v-for="topic in item.topics" :key="topic.id" class="badge cfiles-topic">
                                <span class="cfiles-topic-dot" :style="topic.color ? { backgroundColor: topic.color } : null" aria-hidden="true"></span>{{ topic.name }}
                            </span>
                        </div>
                    </section>
            </template>

            <!-- Where other modules add their own facts about an item (versions, approval, …). -->
            <ExtensionSlot name="cfiles.details" :context="{ item }" />
            </template>

            <!-- A file's versions first, then the rest of its history; loaded once the tab is
                 opened. -->
            <template v-else-if="tab === 'activity'">
                <section v-if="item.fileId">
                    <h3 class="h6">{{ versionsLabel }}</h3>
                    <FileVersions :file-id="item.fileId" />
                </section>
                <hr v-if="item.fileId" />
                <section>
                    <h3 class="h6">{{ activityLabel }}</h3>
                    <ContentActivity :content-id="item.contentId" />
                </section>
            </template>

            <template v-else-if="tab === 'discussion'">
                <p v-if="item.description" class="cfiles-details-description">{{ item.description }}</p>
                <div v-if="likeState" class="cfiles-details-social mb-3">
                    <LikeButton
                        :record-id="item.recordId"
                        :like-count="likeState.total"
                        :current-user-liked="likeState.liked"
                    />
                </div>
                <CommentSection :content-id="item.contentId" />
            </template>
        </div>

        <!-- Info's main action; the other tabs have their own (a version's download, the
             comment form). -->
        <div v-if="downloadUrl && tab === 'info'" class="card-footer">
            <a :href="downloadUrl" class="btn btn-primary w-100">
                <i class="ti ti-download" aria-hidden="true"></i> {{ downloadLabel }}
            </a>
        </div>
    </aside>
</template>

<script>
/**
 * The details of one item (file or folder), next to the browser's card.
 *
 * Three tabs, as the design system's item browser has them (docs/ui/item-browser.md):
 *
 * - **Info** shows what the listing payload already carries about the item — no request of its
 *   own. What cfiles does not know itself, other modules add through the `cfiles.details`
 *   extension slot (context `{ item }`).
 * - **Activity** is, for a file, its versions — the core's `FileVersions` island over the
 *   platform file's history (`GET api/v2/file/<id>/versions`); cfiles has no versions of its
 *   own, but enables the file history for its files —, followed by the content's history, the
 *   core's `ContentActivity` island (`GET api/v2/activity?contentId=`).
 * - **Discussion** is the description, the like link and the comments: the core's
 *   `CommentSection`, mounted by `contentId` alone — it loads the thread and the comment form
 *   itself.
 *
 * Activity and Discussion load once their tab is opened; the panel is remounted for another
 * item, so nothing of one item's tabs is shown for the next.
 */
import { getConfig, i18n } from '@humhub/vue';
import { fileIcon, formatSize, tileMeta } from './itemPresentation';

/** The tabs, in their order. */
const TABS = [
    { id: 'info', icon: 'info-circle' },
    { id: 'activity', icon: 'activity' },
    { id: 'discussion', icon: 'message-circle' },
];

export default {
    props: {
        /** The item shown, as the listing has it. */
        item: { type: Object, required: true },
        /** Where it lies ("Brand › Logos"), if the browser knows. */
        location: { type: String, default: '' },
        /**
         * The caller's like state of the item (`{total, liked, canLike}`, see the listing's
         * `likeStates`), null where the like module is off or there is nothing to show.
         */
        likeState: { type: Object, default: null },
        /** The tab shown first. */
        initialTab: { type: String, default: 'info' },
    },
    emits: ['close', 'tab'],
    data() {
        return {
            tab: TABS.some((entry) => entry.id === this.initialTab) ? this.initialTab : 'info',
        };
    },
    computed: {
        tabs() {
            const labels = {
                info: i18n.t('CfilesModule.base', 'Info'),
                activity: i18n.t('CfilesModule.base', 'Activity'),
                discussion: i18n.t('CfilesModule.base', 'Discussion'),
            };
            return TABS.map((entry) => ({ ...entry, label: labels[entry.id] }));
        },
        headingId() {
            return 'cfiles-details-' + this.item.type + '-' + this.item.id;
        },
        isFolder() {
            return this.item.type === 'folder';
        },
        iconClass() {
            return this.isFolder
                ? 'ti ti-folder-filled cfiles-icon-folder'
                : 'ti ' + fileIcon(this.item) + ' cfiles-icon-file';
        },
        summary() {
            return tileMeta(this.item);
        },
        size() {
            return formatSize(this.item.size);
        },
        visibility() {
            return this.item.visibility === 0
                ? i18n.t('CfilesModule.base', 'Private')
                : i18n.t('CfilesModule.base', 'Public');
        },
        downloadUrl() {
            return this.isFolder ? null : (this.item.downloadUrl || this.item.url || null);
        },
        titleLabel: () => i18n.t('CfilesModule.base', 'Details'),
        closeLabel: () => i18n.t('CfilesModule.base', 'Close details'),
        generalLabel: () => i18n.t('CfilesModule.base', 'General'),
        nameLabel: () => i18n.t('CfilesModule.base', 'Name'),
        typeLabel: () => i18n.t('CfilesModule.base', 'Type'),
        sizeLabel: () => i18n.t('CfilesModule.base', 'Size'),
        contentsLabel: () => i18n.t('CfilesModule.base', 'Contents'),
        locationLabel: () => i18n.t('CfilesModule.base', 'Location'),
        peopleLabel: () => i18n.t('CfilesModule.base', 'People'),
        createdByLabel: () => i18n.t('CfilesModule.base', 'Created by'),
        createdLabel: () => i18n.t('CfilesModule.base', 'Created'),
        updatedLabel: () => i18n.t('CfilesModule.base', 'Modified'),
        accessLabel: () => i18n.t('CfilesModule.base', 'Access'),
        visibilityLabel: () => i18n.t('CfilesModule.base', 'Visibility'),
        downloadsLabel: () => i18n.t('CfilesModule.base', 'Downloads'),
        topicsLabel: () => i18n.t('CfilesModule.base', 'Topics'),
        downloadLabel: () => i18n.t('CfilesModule.base', 'Download'),
        versionsLabel: () => i18n.t('CfilesModule.base', 'Versions'),
        activityLabel: () => i18n.t('CfilesModule.base', 'Activity'),
    },
    watch: {
        tab: {
            handler(tab) {
                this.$emit('tab', tab);
            },
            immediate: true,
        },
    },
    methods: {
        /** A full date and time — the list's "3 days ago" is not what a details view is for. */
        formatDate(stamp) {
            if (!stamp) {
                return '–';
            }
            const locale = getConfig('i18n').language || undefined;
            return new Intl.DateTimeFormat(locale, { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(stamp));
        },
        /** Moves the focus into the panel, for whoever opened it from a menu. */
        focus() {
            this.$refs.heading?.focus();
        },
    },
};
</script>
