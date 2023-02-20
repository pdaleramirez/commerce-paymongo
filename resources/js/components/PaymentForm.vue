<template>
  <div class="gateway-payment-form max-w-3/4">
    <fieldset class="card-holder">
      <h1>Paymongo Form</h1>
      <legend>Card Holder</legend>

      <div class="md:flex md:-mx-4">
        <!-- Card Holder Name -->
        <div class="md:w-1/2 md:mx-4 my-2">
          <input type="text" class="card-holder-first-name text fullwidth"
                 name="firstName"
                 value="" maxlength="70"
                 autocomplete="off" placeholder="First Name" dir="ltr">
        </div>

        <div class="md:w-1/2 md:mx-4 my-2">
          <input type="text" class="card-holder-last-name text fullwidth"
                 name="lastName" value=""
                 maxlength="70" autocomplete="off"
                 placeholder="Last Name" dir="ltr">
        </div>
      </div>



    </fieldset>

    <!-- Card Number -->
    <fieldset class="card-data">
      <legend>Card</legend>

      <div>
        <div>
          <input type="text" class="card-number text fullwidth" name="number" value="4343434343434345" maxlength="19" autocomplete="off" placeholder="Card Number" dir="ltr">

        </div>

        <div>
          <input type="text" class="card-expiry text fullwidth" name="expiry" value="01/2025" autocomplete="off" placeholder="MM / YYYY" dir="ltr">

          <input type="tel" class="card-cvc text fullwidth" name="cvv" value="123" autocomplete="off" placeholder="CVV" dir="ltr">
        </div>
      </div>




    </fieldset>

  </div>
</template>

<script>
export default {
  name: "PaymentForm"
  data() {
    return {
      firstName:
    }
  }
}
</script>

<style scoped>

</style>