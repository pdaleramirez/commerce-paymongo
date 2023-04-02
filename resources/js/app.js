import PaymentForm from "./components/PaymentForm.vue";
window.axios = require('axios');
import { createApp } from 'vue'

const app = createApp()
app.component('payment-form', PaymentForm)
app.mount('#paymongo-payment-form')