<script setup>
import { useForm, Link } from '@inertiajs/vue3'

const form = useForm({})
const resend = () => form.post(route('verification.send'))
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50">
    <div class="w-full max-w-md bg-white rounded-xl shadow p-8 text-center">
      <h1 class="text-2xl font-bold text-gray-800 mb-4">メールアドレスの確認</h1>
      <p class="text-gray-600 mb-6">
        アカウントを有効化するには、メールアドレスの確認が必要です。<br>
        下のボタンを押すと確認メールを送信します。
      </p>

      <button
        @click="resend"
        :disabled="form.processing"
        class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
      >確認メールを送信する</button>

      <p class="text-center text-sm text-gray-500 mt-4">
        <Link :href="route('logout')" method="post" as="button" class="text-blue-600 hover:underline">
          ログアウト
        </Link>
      </p>
    </div>
  </div>
</template>
