<template>
  <div v-if="!$store.state.config.guest_redirection">
    <a v-if="can('read')" id="back-arrow" @click="$router.push('/')">
      <b-icon icon="times" />
    </a>

    <div v-if="registered" id="registered" class="columns is-centered">
      <div class="column is-narrow">
        <div class="box">
          <div class="has-text-centered">
            <img :src="$store.state.config.logo" class="logo">
          </div>
          <div class="has-text-centered">
            {{ lang('Successfully registered! Click to') }}
            <a @click="login">
              {{ lang('Login') }}
            </a>
          </div>
        </div>
      </div>
    </div>

    <div v-if="!registered" id="register" class="columns is-centered">
      <div class="column is-narrow">
        <form @submit.prevent="register">
          <div class="box">
            <div class="has-text-centered">
              <img :src="$store.state.config.logo" class="logo">
            </div>
            <h1 class="box-title">
              {{ lang('User Register') }}
            </h1>
            <b-field :label="lang('Username')">
              <b-input v-model="username" name="username" required :validation-message="lang('Only use letters, numbers and underscores', 4, 16)" pattern="[0-9a-zA-Z_]*" minlength="4" maxlength="16" @input="error = ''" ref="username" />
            </b-field>
            <b-field :label="lang('Password')">
              <b-input v-model="password" type="password" name="password" required minlength="6" maxlength="16" @input="error = ''" password-reveal />
            </b-field>

            <div class="is-flex is-justify-end">
              <button class="button is-primary">
                {{ lang('Register') }}
              </button>
            </div>

            <div v-if="error">
              <code>{{ error }}</code>
            </div>
          </div>
        </form>
        <div class="has-text-centered">
          {{ lang('Already have an account?') }}
          <a @click="login">
            {{ lang('Login') }}
          </a>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../api/api'

export default {
  name: 'Register',
  data() {
    return {
      username: '',
      password: '',
      error: '',
      registered: false,
    }
  },
  mounted() {
    if (this.$store.state.config.guest_redirection) {
      window.location.href = this.$store.state.config.guest_redirection
      return
    }
    this.$refs.username.focus()
  },
  methods: {
    register() {
      api.register({
        username: this.username,
        password: this.password,
      })
        .then(() => {
          this.registered = true
        })
        .catch(error => {
          if (error.response && error.response.data) {
            this.error = this.lang(error.response.data.data)
          } else {
            this.handleError(error)
          }
          this.password = ''
        })
    },
    login() {
      this.$store.commit('setAction', 'login')
      this.$router.push('/login')
    },
  }
}
</script>

<style scoped>
.logo {
  width: 300px;
  display: inline-block;
}

.box {
  padding: 30px;
}

.box-title {
  font-size: 28px;
  color: #34B891;
  text-align: center;
}

#register,
#registered {
  padding: 120px 20px;
}
#back-arrow {
  position: fixed;
  top: 0;
  right: 0;
  margin: 20px;
}
</style>
