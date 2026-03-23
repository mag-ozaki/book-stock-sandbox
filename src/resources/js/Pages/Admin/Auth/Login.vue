<script setup>
import { useForm } from '@inertiajs/vue3'

const form = useForm({
  email: '',
  password: '',
  remember: false,
})

const submit = () => {
  form.post(route('admin.login'), {
    onFinish: () => form.reset('password'),
  })
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-indigo-50">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-8">
      <h1 class="text-2xl font-bold text-indigo-900 mb-6 text-center">管理者ログイン</h1>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
            :class="{ 'border-red-400': form.errors.email }"
            autocomplete="email"
          />
          <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
        </div>

        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">パスワード</label>
          <input
            v-model="form.password"
            type="password"
            class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-400"
            :class="{ 'border-red-400': form.errors.password }"
            autocomplete="current-password"
          />
          <p v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</p>
        </div>

        <div class="flex items-center gap-2">
          <input v-model="form.remember" type="checkbox" id="remember" class="rounded" />
          <label for="remember" class="text-sm text-gray-600">ログイン状態を保持する</label>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-indigo-700 text-white py-2 rounded hover:bg-indigo-800 disabled:opacity-50"
        >ログイン</button>
      </form>
    </div>
  </div>
</template>
