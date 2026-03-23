<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { useForm, Link } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const props = defineProps({ stock: Object })

const form = useForm({
  book_id:  props.stock.book_id,
  quantity: props.stock.quantity,
})

const submit = () => form.put(route('stocks.update', props.stock.id))
</script>

<template>
  <div class="max-w-xl">
    <div class="flex items-center gap-3 mb-6">
      <Link :href="route('stocks.index')" class="text-blue-600 hover:underline text-sm">← 一覧に戻る</Link>
      <h1 class="text-2xl font-bold text-gray-800">在庫の編集</h1>
    </div>

    <form @submit.prevent="submit" class="bg-white rounded-xl shadow p-6 space-y-4">
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">書籍</label>
        <p class="px-3 py-2 bg-gray-50 border rounded text-sm text-gray-700">
          {{ stock.book?.title }}（{{ stock.book?.author }}）
        </p>
      </div>

      <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">在庫数 <span class="text-red-500">*</span></label>
        <input v-model="form.quantity" type="number" min="0"
          class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-400"
          :class="{ 'border-red-400': form.errors.quantity }" />
        <p v-if="form.errors.quantity" class="text-red-500 text-sm mt-1">{{ form.errors.quantity }}</p>
      </div>

      <div class="pt-2">
        <button type="submit" :disabled="form.processing"
          class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700 disabled:opacity-50">
          更新する
        </button>
      </div>
    </form>
  </div>
</template>
