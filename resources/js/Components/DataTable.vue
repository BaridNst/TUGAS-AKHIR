<script setup>
import { computed, ref } from 'vue';
import { FlexRender } from '@tanstack/vue-table';

const props = defineProps({
    table: {
        type: Object,
        required: true,
    },
    data: {
        type: Array,
        default: () => [],
    },
    compact: {
        type: Boolean,
        default: false,
    },
    limit: {
        type: Number,
        default: 0,
    },
});

const sortTick = ref(0);

const toggleSort = (header, event) => {
    if (!header.column.getCanSort()) {
        return;
    }

    header.column.getToggleSortingHandler()?.(event);
    sortTick.value += 1;
};

const headerGroup = computed(() => {
    void props.data;
    void sortTick.value;

    return props.table.getHeaderGroups()[0];
});

const visibleRows = computed(() => {
    void props.data;
    void sortTick.value;

    const rows = props.table.getRowModel().rows;

    return props.limit > 0 ? rows.slice(0, props.limit) : rows;
});
</script>

<template>
    <div class="overflow-x-auto overscroll-x-contain">
        <table class="w-full min-w-[760px] border-separate [border-spacing:0_4px] text-left">
            <thead>
                <tr>
                    <th
                        v-for="header in headerGroup.headers"
                        :key="header.id"
                        scope="col"
                        class="group whitespace-nowrap rounded-xl bg-slate-100/90 text-[11px] font-extrabold uppercase tracking-wider text-slate-700 transition-colors duration-150 border border-slate-200/50"
                        :class="[
                            compact ? 'px-2.5 py-3 sm:px-3 sm:py-2' : 'px-3 py-3.5 sm:px-4 sm:py-3',
                            header.column.columnDef.meta?.className,
                            header.column.getCanSort()
                                ? 'cursor-pointer select-none hover:bg-slate-200 hover:text-slate-900'
                                : '',
                        ]"
                        :aria-sort="
                            header.column.getIsSorted() === 'asc'
                                ? 'ascending'
                                : header.column.getIsSorted() === 'desc'
                                  ? 'descending'
                                  : 'none'
                        "
                        @click="toggleSort(header, $event)"
                    >
                        <span class="inline-flex items-center gap-1.5">
                            <FlexRender v-if="!header.isPlaceholder" :header="header" />

                            <span
                                v-if="header.column.getCanSort()"
                                class="inline-flex h-3.5 w-3.5 items-center justify-center text-gray-400"
                            >
                                <svg
                                    v-if="header.column.getIsSorted() === 'asc'"
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 15.75l7.5-7.5 7.5 7.5" />
                                </svg>

                                <svg
                                    v-else-if="header.column.getIsSorted() === 'desc'"
                                    class="h-3.5 w-3.5"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                </svg>

                                <svg
                                    v-else
                                    class="h-3.5 w-3.5 opacity-40 transition-opacity duration-150 group-hover:opacity-100"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.5"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75L12 3l3.75 3.75" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 17.25L12 21l3.75-3.75" />
                                </svg>
                            </span>
                        </span>
                    </th>
                </tr>
            </thead>

            <tbody>
                <tr v-for="row in visibleRows" :key="row.id" class="group">
                    <td
                        v-for="cell in row.getAllCells()"
                        :key="cell.id"
                        class="border-r border-gray-100 bg-white align-middle transition-colors duration-150 first:rounded-l-xl first:group-hover:shadow-[inset_4px_0_0_#D1D5DB] last:rounded-r-xl last:border-r-0 group-hover:bg-gray-50"
                        :class="[
                            compact ? 'px-2.5 py-2.5 sm:px-3' : 'px-3 py-3 sm:px-4',
                            cell.column.id === 'no' ? 'w-10 text-center sm:w-12' : '',
                            cell.column.columnDef.meta?.className,
                        ]"
                    >
                        <slot
                            :name="`cell-${cell.column.id}`"
                            :row="row.original"
                            :value="cell.getValue()"
                            :index="row.index"
                        >
                            <FlexRender :cell="cell" />
                        </slot>
                    </td>
                </tr>

                <tr v-if="visibleRows.length === 0">
                    <td
                        :colspan="headerGroup.headers.length"
                        class="rounded-xl bg-white px-4 py-8 text-center text-sm text-gray-500 sm:py-10"
                    >
                        <slot name="empty">Belum ada data barang.</slot>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
