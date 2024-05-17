<template>
    <div class="container rounded-lg p-4">

        <h3 class="text-2xl font-semibold text-gray-900 mb-4"><span class="text-gray-900 gubeeIcon gubee-logo-black" />
            Pagamento</h3>
        <hr>
        <div class="lg:col-start-3 lg:row-end-1">
            <div class="ring-1 ring-gray-200 ring-opacity-50 rounded-lg" v-for="(payment, index) in payments"
                :key="index">
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <!-- icon -->
                    <h4 class="font-medium text-gray-900">Metodo de Pagamento</h4>
                    <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        <component :is="paymentMethodIcon(payment.method)" class="h-6 w-6 text-gray-900" />
                        {{ paymentMethodDefaultNaming(payment.method) }}
                    </dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <h4 class="font-medium text-gray-900">Valor</h4>
                    <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        {{ payment.parcels }}x {{ Intl.NumberFormat('pt-BR',
                { style: 'currency', currency: 'BRL' }).format(payment.value) }}
                    </dd>
                </div>
                <div class="px-4 py-6 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                    <h4 class="font-medium text-gray-900">Intermediário</h4>
                    <dd class="mt-1 leading-6 text-gray-700 sm:col-span-2 sm:mt-0">
                        {{ payment.intermediary.name }} - {{ payment.intermediary.registrationNumber }}
                    </dd>
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
            payments: {}
        }
    },
    mounted() {
        this.payments = window.gubeeOrderInfo.payments
    },
    methods: {
        paymentMethodIcon(method) {
            switch (method) {
                case 'bank_transfer':
                case 'transfer':
                    return EnvelopeIcon
                case 'credit_card':
                case 'debit_card':
                    return CreditCardIcon
                case 'boleto':
                    return DocumentIcon
                case 'pix':
                default:
                    return CurrencyDollarIcon
            }
        },
        paymentMethodDefaultNaming(method) {
            switch (method) {
                case 'bank_transfer':
                case 'transfer':
                    return 'Transferência Bancária'
                case 'credit_card':
                    return 'Cartão de Crédito'
                case 'boleto':
                    return 'Boleto'
                case 'pix':
                    return 'Pix'
                case 'debit_card':
                    return 'Cartão de Débito'
                default:
                    return method
            }
        }
    }
}
</script>