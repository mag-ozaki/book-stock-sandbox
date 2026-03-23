<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({ email: '' })

const submit = () => form.post(route('password.email'))
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-8">
      <h1 class="text-2xl font-bold text-gray-800 mb-2 text-center">パスワードリセット</h1>
      <p class="text-sm text-gray-500 text-center mb-6">
        登録済みのメールアドレスを入力してください。リセット用のリンクをお送りします。
      </p>

      <form @submit.prevent="submit" class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-1">メールアドレス</label>
          <input
            v-model="form.email"
            type="email"
            class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
            :class="{ 'border-red-400': form.errors.email }"
          />
          <p v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</p>
        </div>

        <button
          type="submit"
          :disabled="form.processing"
          class="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-50"
        >リセットリンクを送信</button>
      </form>

      <p class="text-center text-sm text-gray-500 mt-4">
        <Link :href="route('login')" class="text-blue-600 hover:underline">ログインに戻る</Link>
      </p>
    </div>
  </div>
</template>
