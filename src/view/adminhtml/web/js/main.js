import { createApp } from 'vue'
import './assets/css/style.css'
import App from './vue/components/App.vue'
import Validation from './vue/components/Validation.vue'
import Details2 from './vue/components/Message/Details2.vue'
import PaymentInfo from './vue/components/PaymentInfo.vue'
import OrderInfo from './vue/components/OrderInfo.vue'
import ShippingInfo from './vue/components/ShippingInfo.vue'

if (!window.gubeeIntegration) {
    window.gubeeIntegration = {}
}

document.addEventListener('DOMContentLoaded', function () {
    if (!document.querySelector('body.sales-order-view')) {
        return
    }
    var container;
    if (container = document.querySelector('.gubee-payment-placeholder')) {
        createApp(
            PaymentInfo
        ).mount(
            container.parentNode.parentNode.parentNode
        )
    }

    var placeholder = document.createElement('div');
    placeholder.setAttribute('id', 'gubee_shipping_info_placeholder');
    var target = document.querySelector('.order-shipping-method')
    createApp(
        ShippingInfo
    ).mount(target)
    var placeholder = document.createElement('div');
    placeholder.setAttribute('id', 'gubee_order_info_placeholder');
    var target = document.querySelector('.order-view-account-information')
    // add after target
    target.parentNode.insertBefore(placeholder, target)
    createApp(
        OrderInfo
    ).mount(placeholder)


})

document.addEventListener('DOMContentLoaded', function () {
    var doc = document.querySelector('body.gubee-install-index .page-wrapper')
    if (!doc) {
        return
    }

    // replace element with a empty div
    doc.innerHTML = '<div id="app"></div>'
    createApp(App, {
        props: {
            configUrl: window.gubeeIntegration.settingsUrl,
            docsUrls: window.gubeeIntegration.docsUrls,
            storeUrl: "https://gubee.com.br/",
        }
    }).mount('#app')
})


document.addEventListener('DOMContentLoaded', () => {
    var errors = document.querySelector('body.catalog-product-edit #gubee_validation span').innerText;
    if (errors) {
        errors = JSON.parse(errors);
        document.querySelector('#gubee_validation').setAttribute('data-errors', errors.errors.length);
        document.querySelector('#gubee_validation span').innerText = '';
    }
    var divPlaceholder = document.createElement('div');
    document.body.appendChild(divPlaceholder);
    createApp(
        Validation,
        {
            errors: errors.errors,
            payload: errors.product
        }
    ).mount(divPlaceholder);

});

document.addEventListener('DOMContentLoaded', () => {
    if (!document.querySelector('body.gubee-message-index')) {
        return;
    }
    window.messageDetails = (() => {
        function showDetails(element, messageId, ajaxUrl) {
            element.innerText = 'Hide Details';
            var tr = element.closest('tr');
            if (tr.parentNode.querySelector('tr[data-message-id="' + messageId + '"]')) {
                return;
            }

            let newTr = document.createElement('tr');
            newTr.setAttribute('data-message-id', messageId);
            let newTd = document.createElement('td');
            newTd.setAttribute('colspan', tr.children.length);
            var placeholderElement = document.createElement('div');
            placeholderElement.setAttribute('id', 'gubee_message_details');
            placeholderElement.setAttribute('data-message-id', messageId);
            newTd.append(placeholderElement)
            newTr.appendChild(newTd);
            tr.parentNode.insertBefore(newTr, tr.nextSibling);

            createApp(
                Details2,
                {
                    messageId: messageId,
                    ajaxUrl: ajaxUrl
                }
            ).mount(placeholderElement);

            // add a event listener to hide or show the details
            element.addEventListener('click', function () {
                if (newTr.style.display === 'none') {
                    element.innerText = 'Hide Details';
                    newTr.style.display = 'table-row';
                } else {
                    element.innerText = 'Show Details';
                    newTr.style.display = 'none';
                }
            })
        }

        return {
            showDetails: showDetails
        }
    })()

    console.log(messageDetails)

});