<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const props = defineProps({ books: Array })

const form = useForm({
  book_id:      '',
  quantity:     1,
  purchased_at: '',
  note:         '',
})

const submit = () => form.post(route('purchase-histories.store'))
</script>

<template>
  <div class="max-w-xl">
    <div class="flex items-center gap-3 mb-6">
      <Link :href="route('purchase-histories.index')" class="text-blue-600 hover:underline text-sm">← 一覧に戻る</Link>
      <h1 class="text-2xl font-bold text-gray-800">購入履歴の登録</h1>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-xl shadow p-6 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">書籍 <span class="text-red-500">*</span></label>
        <select v-model="form.book_id"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.book_id }">
          <option value="">選択してください</option>
          <option v-for="book in books" :key="book.id" :value="book.id">
            {{ book.title }}（{{ book.author }}）
          </option>
        </select>
        <p v-if="form.errors.book_id" class="text-red-500 text-sm mt-1">{{ form.errors.book_id }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">購入冊数 <span class="text-red-500">*</span></label>
        <input v-model="form.quantity" type="number" min="1"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.quantity }" />
        <p v-if="form.errors.quantity" class="text-red-500 text-sm mt-1">{{ form.errors.quantity }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">購入日 <span class="text-red-500">*</span></label>
        <input v-model="form.purchased_at" type="date"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.purchased_at }" />
        <p v-if="form.errors.purchased_at" class="text-red-500 text-sm mt-1">{{ form.errors.purchased_at }}</p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">備考</label>
        <textarea v-model="form.note" rows="3"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.note }"></textarea>
        <p v-if="form.errors.note" class="text-red-500 text-sm mt-1">{{ form.errors.note }}</p>
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
