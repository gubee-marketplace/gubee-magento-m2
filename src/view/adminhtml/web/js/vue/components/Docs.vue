<template>
    <div class="bg-white">
        <div class="mx-auto max-w-7xl px-6 lg:px-8">
            <p class="mx-auto mt-6 max-w-2xl text-center text-lg leading-8 text-gray-600">Conheça as principais
                funcionalidades abaixo.
            </p>
            <div
                class="isolate mx-auto mt-16 grid max-w-md grid-cols-1 gap-y-8 sm:mt-20 lg:mx-0 lg:max-w-none lg:grid-cols-3">
                <div v-for="(tier, tierIdx) in tiers" :key="tier.id" class="shadow-lg"
                    :class="[tier.mostPopular ? 'lg:z-10 lg:rounded-b-none' : 'lg:mt-8', tierIdx === 0 ? 'lg:rounded-r-none' : '', tierIdx === tiers.length - 1 ? 'lg:rounded-l-none' : '', 'flex flex-col justify-between rounded-3xl bg-white p-8 ring-1 ring-gray-200 xl:p-10']">
                    <div>
                        <div class="flex items-center justify-between gap-x-4">
                            <h3 :id="tier.id"
                                :class="[tier.mostPopular ? 'text-indigo-600' : 'text-gray-900', 'text-lg font-semibold leading-8']">
                                {{ tier.name }}</h3>
                        </div>
                        <p class="mt-4 text-sm leading-6 text-gray-600">{{ tier.description }}</p>
                        <p class="mt-6 flex items-baseline gap-x-1">
                            <span class="text-4xl font-bold tracking-tight text-gray-900">{{ tier.priceMonthly }}</span>
                        </p>
                        <ul role="list" class="mt-8 space-y-3 text-sm leading-6 text-gray-600">
                            <li v-for="feature in tier.features" :key="feature" class="flex gap-x-3">
                                <CheckIcon class="h-6 w-5 flex-none text-indigo-600" aria-hidden="true" />
                                {{ feature }}
                            </li>
                        </ul>
                    </div>
                    <a :href="tier.href" :aria-describedby="tier.id"
                        :class="[tier.mostPopular ? 'bg-indigo-600 text-white shadow-sm hover:bg-indigo-500' : 'text-indigo-600 ring-1 ring-inset ring-indigo-200 hover:ring-indigo-300', 'mt-8 block rounded-md py-2 px-3 text-center text-sm font-semibold leading-6 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600']">{{
                    tier.callToAction || 'Learn more'
                }}</a>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { CheckIcon } from '@heroicons/vue/20/solid'

export default {
    props: {
        configUrl: String,
        docsUrls: String,
        storeUrl: String,
    },
    data() {
        return {
            tiers: [
                {
                    name: 'Configurações',
                    id: 'tier-freelancer',
                    href: this.configUrl,
                    priceMonthly: '',
                    description: 'Começe pela configuração do modulo, onde você pode definir as principais características da integração.',
                    features: ['Atributos do produto', 'Categorias', 'Regras de sincronização'],
                    mostPopular: false,
                    callToAction: 'Começar'
                },
                {
                    name: 'Como funciona',
                    id: 'tier-startup',
                    href: this.docsUrls,
                    priceMonthly: '',
                    description: 'Conheça o processo de integração e como funciona a sincronização do catalogo e pedidos.',
                    features: [
                        'Fila de sincronização',
                        'Envio de produtos',
                        'Arvore de categorias',
                        'Sincronização de pedidos',
                        'Envio de notas fiscais',
                    ],
                    mostPopular: false,
                    callToAction: 'Saiba mais'
                },
                {
                    name: 'Conheça a Gubee',
                    id: 'tier-enterprise',
                    href: this.storeUrl,
                    priceMonthly: '',
                    description: 'Conheça a Gubee e suas principais funcionalidades.',
                    mostPopular: true,
                    callToAction: 'Conhecer'
                },
            ]
        }
    },
}
</script>