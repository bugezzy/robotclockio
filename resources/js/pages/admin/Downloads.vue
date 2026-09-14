<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Download, History, MonitorDown, UploadCloud } from '@lucide/vue';
import DownloadController from '@/actions/App/Http/Controllers/Admin/DownloadController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardAction,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import admin from '@/routes/admin';

type PlatformStatus = {
    platform: string;
    label: string;
    available: boolean;
    version: string | null;
    url: string | null;
};

type ReleaseRow = {
    id: string;
    platform: string;
    version: string;
    is_latest: boolean;
    created_at: string;
    uploaded_by: string | null;
};

defineProps<{
    platforms: PlatformStatus[];
    canManage: boolean;
    releases: ReleaseRow[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Admin', href: admin.dashboard() },
            { title: 'Downloads', href: admin.downloads.index() },
        ],
    },
});

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    });
}
</script>

<template>
    <Head title="Downloads" />

    <div class="flex flex-col gap-6 p-4">
        <Heading
            title="Downloads"
            description="The RobotClock kiosk desktop app. Install it on the machine you want to use as a clock-in station, then activate it with a licence issued from an organization's page."
        />

        <div class="grid gap-4 sm:grid-cols-2">
            <Card v-for="platform in platforms" :key="platform.platform">
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <MonitorDown class="text-muted-foreground size-4" />
                        {{ platform.label }}
                    </CardTitle>
                    <CardDescription>
                        <span v-if="platform.available">
                            Version {{ platform.version }}
                        </span>
                        <span v-else>No build has been uploaded yet.</span>
                    </CardDescription>
                    <CardAction>
                        <Badge v-if="!platform.available" variant="secondary">
                            Coming soon
                        </Badge>
                    </CardAction>
                </CardHeader>
                <CardContent>
                    <Button v-if="platform.available" as-child>
                        <a :href="platform.url!">
                            <Download />
                            Download for {{ platform.label }}
                        </a>
                    </Button>
                    <Button v-else disabled>
                        <Download />
                        Download for {{ platform.label }}
                    </Button>
                </CardContent>
            </Card>
        </div>

        <template v-if="canManage">
            <Card>
                <CardHeader>
                    <CardTitle class="flex items-center gap-2">
                        <UploadCloud class="text-muted-foreground size-4" />
                        Publish a new build
                    </CardTitle>
                    <CardDescription>
                        Uploading a build immediately makes it that
                        platform's latest — the one everyone downloads and
                        the one kiosks are told to update to.
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DownloadController.store.form()"
                        reset-on-success
                        class="grid gap-4 sm:grid-cols-[1fr_1fr_2fr_auto] sm:items-end"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="release-platform">Platform</Label>
                            <Select name="platform" default-value="windows">
                                <SelectTrigger
                                    id="release-platform"
                                    class="w-full"
                                >
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="windows"
                                        >Windows</SelectItem
                                    >
                                    <SelectItem value="linux"
                                        >Linux</SelectItem
                                    >
                                </SelectContent>
                            </Select>
                            <InputError :message="errors.platform" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="release-version">Version</Label>
                            <Input
                                id="release-version"
                                name="version"
                                required
                                placeholder="1.4.0"
                            />
                            <InputError :message="errors.version" />
                        </div>

                        <div class="grid gap-2">
                            <Label for="release-file">Installer file</Label>
                            <input
                                id="release-file"
                                type="file"
                                name="file"
                                required
                                class="border-input file:text-foreground h-9 w-full rounded-md border bg-transparent px-3 py-1 text-sm file:mr-3 file:h-7 file:border-0 file:bg-transparent file:text-sm file:font-medium"
                            />
                            <InputError :message="errors.file" />
                        </div>

                        <Button type="submit" :disabled="processing"
                            >Publish</Button
                        >
                    </Form>
                </CardContent>
            </Card>

            <div
                class="border-sidebar-border/70 dark:border-sidebar-border rounded-xl border"
            >
                <div
                    class="border-sidebar-border/70 dark:border-sidebar-border flex items-center gap-2 border-b p-4"
                >
                    <History class="text-muted-foreground size-4" />
                    <h3 class="font-medium">Version history</h3>
                </div>

                <p
                    v-if="releases.length === 0"
                    class="text-muted-foreground p-4 text-sm"
                >
                    No builds published yet.
                </p>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-sidebar-border/70 dark:border-sidebar-border text-muted-foreground border-b text-left"
                        >
                            <th class="p-3 font-medium">Platform</th>
                            <th class="p-3 font-medium">Version</th>
                            <th class="p-3 font-medium">Published by</th>
                            <th class="p-3 font-medium">Published at</th>
                            <th class="p-3 font-medium"></th>
                        </tr>
                    </thead>
                    <tbody
                        class="divide-sidebar-border/70 dark:divide-sidebar-border divide-y"
                    >
                        <tr v-for="release in releases" :key="release.id">
                            <td class="p-3 capitalize">
                                {{ release.platform }}
                            </td>
                            <td class="p-3">{{ release.version }}</td>
                            <td class="p-3">
                                {{ release.uploaded_by ?? '—' }}
                            </td>
                            <td class="p-3 whitespace-nowrap">
                                {{ formatDateTime(release.created_at) }}
                            </td>
                            <td class="p-3">
                                <Badge v-if="release.is_latest">
                                    Latest
                                </Badge>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </template>
    </div>
</template>
