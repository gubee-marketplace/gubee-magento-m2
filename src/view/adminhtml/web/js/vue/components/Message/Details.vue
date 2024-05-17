s<template>
    <div class="px-4 sm:px-6 lg:px-8">
        <div class="sm:flex sm:items-center">
            <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
                <button type="button"
                    class="block rounded-md bg-indigo-600 px-3 py-2 text-center text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Add
                    user</button>
            </div>
        </div>
        <div class="mt-8 flow-root rounded-lg shadow-md">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300 bg-white ">
                        <tbody class="divide-y divide-gray-200">
                            <tr v-for="detail in details" :key="detail.detail_id">
                                <td style="padding:initial;background-color:white">
                                    <Accordion :title="detail.message" :content="detail.context" />
                                </td>
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


export default {
    components: {
        Accordion
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
            this.details = response.data;
        }).catch(error => {
            console.log(error);
        })
    }
}
</script>