import MyForm from "./components/PaymentForm.vue";
window.axios = require('axios');
import { createApp } from 'vue'

const app = createApp()
app.component('payment-form', MyForm)
app.mount('#paymongo-payment-form')