<template>
  <div class="gateway-payment-form max-w-3/4">
    <fieldset class="card-holder">
      <h1>Paymongo Form</h1>
      <legend>Card Holder</legend>
{{ orderData.gatewayId }}
      <div class="md:flex md:-mx-4">
        <!-- Card Holder Name -->
        <div class="md:w-1/2 md:mx-4 my-2">
          <input type="text" class="card-holder-first-name text fullwidth"
                 v-model="firstName"
                 maxlength="70"
                 autocomplete="off" placeholder="First Name" dir="ltr">
        </div>

        <div class="md:w-1/2 md:mx-4 my-2">
          <input type="text" class="card-holder-last-name text fullwidth"
                 v-model="lastName"
                 maxlength="70" autocomplete="off"
                 placeholder="Last Name" dir="ltr">
        </div>
      </div>



    </fieldset>

    <form ref="mainForm" id="main-form">
      <div>
        <div class='form-row'>
          <div class='col-xs-12 form-group required'>
            <label class='control-label'>Card Number</label>
            <input v-model="cardDetails.number" autocomplete='off' class='form-control card-number' size='40' type='text'>
          </div>
        </div>
        <div class='form-row'>
          <div class='col-xs-4 form-group expiration required'>
            <label class='control-label'>Expiration</label>
            <input v-model="cardDetails.month" class='form-control card-expiry-month' placeholder='MM' size='2' type='text'>
          </div>
          <div class='col-xs-4 form-group expiration required'>
            <label class='control-label'>&nbsp;</label>
            <input  v-model="cardDetails.year" class='form-control card-expiry-year' placeholder='YYYY' size='4' type='text'>
          </div>
          <div class='col-xs-4 form-group cvc required'>
            <label class='control-label'>CVC</label>
            <input v-model="cardDetails.cvc" autocomplete='off' class='form-control card-cvc' placeholder='ex. 311' size='3' type='text'>
          </div>
        </div>

        <input type="button" class="btn btn-lg" @click.prevent="submitForm()" value="Make Payment"/>
      </div>
    </form>

  </div>
</template>

<script>
export default {
  name: "PaymentForm",
  props: {
    orderData: Object
  },
  data() {
    return {
      firstName: null,
      lastName: null,
      cardDetails: {
        number: null,
        month: null,
        year: null,
        cvc: null
      }
    }
  },
  methods: {
    submitForm() {
      let self = this;
      axios.post("/actions/commerce/payments/pay", this.cardDetails, {
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        }
      }).then((response) => {
        console.log(response)
      }).catch(function (error) {

      });
    }
  }
}
</script>

<style scoped>

</style>