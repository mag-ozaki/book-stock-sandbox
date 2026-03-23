<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

defineProps({ books: Array })

const destroy = (id) => {
  if (confirm('この書籍を削除しますか？')) {
    router.delete(route('books.destroy', id))
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">書籍一覧</h1>
      <Link :href="route('books.create')"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
        + 新規登録
      </Link>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">出版社</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">価格</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ISBN</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="book in books" :key="book.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ book.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ book.author }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ book.publisher ?? '—' }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ book.price != null ? `¥${book.price.toLocaleString()}` : '—' }}
            </td>
            <td class="px-6 py-4 text-sm text-gray-400">{{ book.isbn ?? '—' }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <Link :href="route('books.edit', book.id)" class="text-blue-600 hover:underline">編集</Link>
              <button @click="destroy(book.id)" class="text-red-500 hover:underline">削除</button>
            </td>
          </tr>
          <tr v-if="books.length === 0">
            <td colspan="6" class="px-6 py-8 text-center text-gray-400">書籍が登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
