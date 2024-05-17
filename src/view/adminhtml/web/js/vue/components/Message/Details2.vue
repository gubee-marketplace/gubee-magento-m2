s<template>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="mt-8 flow-root rounded-lg shadow-md">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300 bg-white ">
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="(detail, detailId) in details" :key="detail.detail_id">
                                <div class="relative pb-8">
                                    <span v-if="detailId !== details.length - 1"
                                        class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200"
                                        aria-hidden="true" />
                                    <div class="relative flex space-x-3">
                                        <div>
                                            <span :id="detail.level"
                                                class="h-8 w-8 rounded-full flex items-center justify-center ring-8 ring-white relative"
                                                style="top:30px" :class="getStyleForIcon(detail.level)">
                                                <component :is="getIcon(detail.level)" class="h-5 w-5 text-white"
                                                    aria-hidden="true" />
                                            </span>
                                        </div>
                                        <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                            <div class="w-full">
                                                <Accordion :title="detail.message" :content="detail.context" />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import axios from 'axios';
import Accordion from './Detail/Accordion.vue';
import {
    ExclamationCircleIcon,
    SparklesIcon,
    CheckCircleIcon,
    ExclamationTriangleIcon,
    ArrowDownIcon
} from '@heroicons/vue/20/solid'



export default {
    components: {
        Accordion,
        ExclamationCircleIcon,
        ExclamationTriangleIcon,
        SparklesIcon,
        ArrowDownIcon,
        CheckCircleIcon
    },
    props: {
        messageId: {
            type: String,
            required: true
        },
        ajaxUrl: {
            type: String,
            required: true
        }
    },
    data() {
        return {
            details: []
        }
    },
    mounted() {
        axios.post(this.ajaxUrl, {
            form_key: window.FORM_KEY
        }).then(response => {
            this.details = [
                {
                    "detail_id": "00",
                    "level": -1,
                    "message": "New queue item was queued to be executed",
                    "context": null,
                }
            ];
            // add response objects to details
            this.details.push(...response.data);
        }).catch(error => {
            console.log(error);
        })
    },
    methods: {
        getIcon(level) {
            switch (parseInt(level)) {
                case -1:
                    return SparklesIcon
                case 1:
                case 2:
                case 4:
                case 5:
                    return ExclamationCircleIcon
                case 3:
                case 0:
                    return ExclamationTriangleIcon
                case 6:
                    return CheckCircleIcon
                case 7:
                default:
                    return ArrowDownIcon
            }
        },
        getStyleForIcon(level) {
            switch (parseInt(level)) {
                case -1:
                    return 'bg-green-500';
                case 1:
                case 2:
                case 4:
                case 5:
                    return 'bg-yellow-500';
                case 3:
                case 0:
                    return 'bg-red-500';
                case 6:
                    return 'bg-green-300';
                case 7:
                default:
                    return 'bg-gray-500';
            }
        }
    }
}
</script>