<template>
    <div class="container rounded-lg p-4">

        <h3 class="text-2xl font-semibold text-gray-900 mb-4"><span class="text-gray-900 gubeeIcon gubee-logo-black" />
            Entrega</h3>
        <hr>
        <div class="lg:col-start-3 lg:row-end-1">
            <div class=" rounded-lg p-4">
                <h3>Entrega no {{ plataform }}</h3>
                <div v-for="(shipping, index) in shippings" :key="index">
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <!-- icon -->
                        <h4 class="font-medium text-gray-900">Codigo da entrega</h4>
                        <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            {{ shipping.code }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <!-- icon -->
                        <h4 class="font-medium text-gray-900">Metodo de Entrega</h4>
                        <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            {{ shipping.transport.carrier }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <!-- icon -->
                        <h4 class="font-medium text-gray-900">Codigo de Rastreio</h4>
                        <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            {{ shipping.transport.trackingCode }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <!-- icon -->
                        <h4 class="font-medium text-gray-900">Data de entrega estimada</h4>
                        <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            {{ formatDate(shipping.estimatedDeliveryDt) }}
                        </dd>
                    </div>
                </div>
            </div>
            <!-- add a spacer -->
            <div class="h-4"></div>
            <div class=" rounded-lg p-4">
                <h3>Entrega na Gubee</h3>
                <div v-for=" (shipping, index) in shipments" :key="index">
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <!-- icon -->
                        <h4 class="font-medium text-gray-900">Codigo da entrega</h4>
                        <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            {{ shipping.code }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <!-- icon -->
                        <h4 class="font-medium text-gray-900">Metodo de Entrega</h4>
                        <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            {{ shipping.transport.carrier }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <!-- icon -->
                        <h4 class="font-medium text-gray-900">Codigo de Rastreio</h4>
                        <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            {{ shipping.transport.trackingCode }}
                        </dd>
                    </div>
                    <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                        <!-- icon -->
                        <h4 class="font-medium text-gray-900">Data de entrega estimada</h4>
                        <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                            {{ formatDate(shipping.estimatedDeliveryDt) }}
                        </dd>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
<script>
import { EnvelopeIcon } from '@heroicons/vue/24/solid'
import { CreditCardIcon, DocumentIcon, CurrencyDollarIcon } from '@heroicons/vue/24/outline'

export default {
    components: {
        EnvelopeIcon,
        CreditCardIcon,
        DocumentIcon,
        CurrencyDollarIcon
    },
    data() {
        return {
            shippings: {},
            shipments: {},
            plataform: 'Marketplace'
        }
    },
    mounted() {
        this.shippings = window.gubeeOrderInfo.marketplaceShipments
        this.shipments = window.gubeeOrderInfo.shipments
        this.plataform = window.gubeeOrderInfo.plataform
    },
    methods: {
        formatDate(date) {
            var data = new Date(date);
            var day = data.getDate().toString().padStart(2, '0');
            var month = (data.getMonth() + 1).toString().padStart(2, '0');
            var year = data.getFullYear();
            var hour = data.getHours().toString().padStart(2, '0');
            var minutes = data.getMinutes().toString().padStart(2, '0');
            return `${day}/${month}/${year}`;
        }
    }
}
</script>