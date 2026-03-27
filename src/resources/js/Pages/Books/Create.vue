<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const form = useForm({
  jan_code:  '',
  title:     '',
  author:    '',
  publisher: '',
  price:     '',
})

const submit = () => form.post(route('books.store'))
</script>

<template>
  <div class="max-w-xl">
    <div class="flex items-center gap-3 mb-6">
      <Link :href="route('books.index')" class="text-blue-600 hover:underline text-sm">← 一覧に戻る</Link>
      <h1 class="text-2xl font-bold text-gray-800">書籍の新規登録</h1>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-xl shadow p-6 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">タイトル <span class="text-red-500">*</span></label>
        <input v-model="form.title" type="text"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.title }" />
        <p v-if="form.errors.title" class="text-red-500 text-sm mt-1">{{ form.errors.title }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">著者 <span class="text-red-500">*</span></label>
        <input v-model="form.author" type="text"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.author }" />
        <p v-if="form.errors.author" class="text-red-500 text-sm mt-1">{{ form.errors.author }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">出版社</label>
        <input v-model="form.publisher" type="text"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.publisher }" />
        <p v-if="form.errors.publisher" class="text-red-500 text-sm mt-1">{{ form.errors.publisher }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">価格（円）</label>
        <input v-model="form.price" type="number" min="0"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.price }" />
        <p v-if="form.errors.price" class="text-red-500 text-sm mt-1">{{ form.errors.price }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">JANコード</label>
        <input v-model="form.jan_code" type="text" maxlength="26" inputmode="numeric"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.jan_code }" />
        <p class="text-gray-400 text-xs mt-1">26桁の数字（上段13桁+下段13桁）</p>
        <p v-if="form.errors.jan_code" class="text-red-500 text-sm mt-1">{{ form.errors.jan_code }}</p>
      </div>

      <div class="pt-2">
        <button type="submit" :disabled="form.processing"
          class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
          登録する
        </button>
      </div>
    </form>
  </div>
</template>
