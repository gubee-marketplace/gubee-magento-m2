<template>
    <div class="accordion p-4">
        <div class="accordion-header p-4" @click="toggleAccordion">
            {{ title }}
            <span style="text-align: right;" class="items-end" v-if="content != null">
                <ChevronDownIcon v-if="!isOpen" class="accordion-icon" />
                <ChevronUpIcon v-else class="accordion-icon" />
            </span>
        </div>
        <textarea class="accordion-content p-4 bg-gray-100 w-full border-smooth rounded-md
        " v-show="isOpen" :value="JSON.stringify(JSON.parse(content), null, 4)" readonly></textarea>
    </div>
</template>

<script>
import { ChevronDownIcon, ChevronUpIcon } from '@heroicons/vue/20/solid'

export default {
    components: {
        ChevronDownIcon,
        ChevronUpIcon,
    },
    props: {
        title: {
            type: String,
            required: true
        },
        content: {
            type: String
        }
    },
    data() {
        return {
            isOpen: false
        };
    },
    methods: {
        toggleAccordion() {
            this.isOpen = !this.isOpen;
        }
    }
};
</script>

<style>
.accordion {
    margin: initial;
}

.accordion-header {
    cursor: pointer;
    display: flex;
    align-items: center;
}

.accordion-icon {
    margin-left: auto;
    height: 25px;
}
</style>