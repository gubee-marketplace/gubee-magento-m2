<template>
    <div class="md:flex md:items-center md:justify-between">
        <div class="min-w-0 inline-flex flex-1 align-middle">
            <img src="../../assets/img/logo.svg" alt="Gubee" class="h-16 w-auto" />
            <h2 class="text-2xl pl-4 font-bold leading-7 text-gray-900 sm:truncate sm:text-3xl sm:tracking-tight ">
                Pedido Integrado pela Gubee
            </h2>
        </div>
    </div>
    <dl class="mx-auto grid grid-cols-1 gap-px bg-gray-900/5 sm:grid-cols-2 lg:grid-cols-3">
        <div v-for="(stat, index) in stats" :key="index"
            class="flex flex-wrap items-baseline justify-between gap-x-4 gap-y-2 bg-white px-4 py-10 sm:px-6 xl:px-8">
            <dt class="text-sm font-medium leading-6 text-gray-500">{{ index }}</dt>
            <dd class="w-full flex-none text-3xl font-medium leading-10 tracking-tight text-gray-900">{{ stat
                }}</dd>
        </div>
    </dl>
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
            order: {},
            stats: {
                "Canal": window.gubeeOrderInfo.plataform,
                "Código do Canal": window.gubeeOrderInfo.externalId,
                "Conta vinculada": window.gubeeOrderInfo.accountId,
            }
        }
    },
    mounted() {
        this.order = window.gubeeOrderInfo
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