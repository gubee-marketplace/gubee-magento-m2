<template>
    <div class="relative bg-white h-screen" style="zoom: 1.3;">
        <div class="mx-auto lg:grid lg:grid-cols-12 lg:gap-x-8 lg:px-8 h-screen">
            <div class="px-6 pb-24 pt-10 sm:pb-32 lg:col-span-7 lg:px-0 lg:pb-56 lg:pt-48 xl:col-span-6 align-middle">
                <div class="mx-auto w-full lg:mx-0">
                    <img class="h-11" src="https://gubee.com.br/wp-content/uploads/2021/04/logotipo.png" alt="Gubee" />

                    <h2 class="mt-2 font-bold tracking-tight text-gray-900 sm:mt-10">Seja bem vindo a Integração
                        Gubee
                        com o Magento 2</h2>
                    <p class="mt-6 leading-8 text-gray-600">Nosso modulo de integração com a Gubee foi desenvolvido para
                        facilitar a integração entre o Magento 2 e a Gubee. Com ele você pode integrar seus produtos com
                        a Gubee de forma simples e rápida.</p>


                    <div class="flex items-center">
                        <Docs :configUrl="configUrl" docsUrls:="docsUrls" storeUrl:="storeUrl">
                        </Docs>
                    </div>
                </div>
            </div>
            <div class="relative lg:col-span-5 lg:-mr-8 xl:absolute xl:inset-0 xl:left-1/2 xl:mr-0">
                <img class="aspect-[3/2] w-full bg-gray-50 object-cover lg:absolute lg:inset-0 lg:aspect-auto lg:h-full"
                    src="https://images.unsplash.com/photo-1498758536662-35b82cd15e29?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=2102&q=80"
                    alt="" />
            </div>
        </div>
    </div>
</template>

<script>
import Docs from './Docs.vue'
export default {
    components: {
        Docs
    },
    props: {
        configUrl: String,
        docsUrls: String,
        storeUrl: String,
    }
}
</script>