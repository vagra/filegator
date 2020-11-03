<template>
  <div class="modal-card">
    <header class="modal-card-head">
      <p class="modal-card-title">
        {{ lang('Profile') }}
      </p>
    </header>
    <section class="modal-card-body">
      <form @submit="save">
        <b-field :label="lang('New name')">
          <b-input v-model="newname" :placeholder="this.$store.state.user.name" minlength="2" maxlength="16" @keydown.native="formErrors.newname = ''" />
        </b-field>

        <b-field :label="lang('Old password')">
          <b-input v-model="oldpassword" type="password" password-reveal required @keydown.native="formErrors.oldpassword = ''" />
        </b-field>

        <b-field :label="lang('New password')">
          <b-input v-model="newpassword" type="password" :placeholder="lang('Leave blank for no change')" minlength="6" maxlength="16" password-reveal @keydown.native="formErrors.newpassword = ''" />
        </b-field>

        <div v-if="formErrors.oldpassword">
          <code>{{ formErrors.oldpassword }}</code>
        </div>
      </form>
    </section>
    <footer class="modal-card-foot">
      <button class="button" type="button" @click="$parent.close()">
        {{ lang('Close') }}
      </button>
      <button class="button is-primary" type="button" @click="save">
        {{ lang('Save') }}
      </button>
    </footer>
  </div>
</template>

<script>
import api from '../../api/api'
import _ from 'lodash'

export default {
  name: 'Profile',
  data() {
    return {
      newname: '',
      oldpassword: '',
      newpassword: '',
      formErrors: {},
    }
  },
  methods: {
    save() {
      api.changePassword({
        newname: this.newname,
        oldpassword: this.oldpassword,
        newpassword: this.newpassword,
      })
        .then(user => {
          this.$store.commit('setUser', user)
          this.$toast.open({
            message: this.lang('Updated'),
            type: 'is-success',
          })
          this.$parent.close()
        })
        .catch(errors => {
          if (typeof errors.response.data.data != 'object') {
            this.handleError(errors)
          }
          _.forEach(errors.response.data, err => {
            _.forEach(err, (val, key) => {
              this.formErrors[key] = this.lang(val)
              this.$forceUpdate()
            })
          })
        })
    },
  },
}
</script>
