<template>
  <table class="table table-hover">
    <thead>
    <tr><th>Имя</th><th>Отчество</th><th>Фамилия</th><th>Телефон</th></tr>
    </thead>
    <tbody>
    <tr v-for="user in users">
      <td v-for="key in columns">
        {{ user[key] }}
      </td>
    </tr>
    </tbody>
  </table>
</template>

<script>
export default {
  data() {
    return {
      users: [],
      columns: ['firstName', 'middleName', 'lastName', 'phone']
    };
  },
  mounted() {
    let data = document.querySelector("div[data-users]");
    let userList = JSON.parse(data.dataset.users);

    this.users.push.apply(this.users, userList);
  }
};
</script>
