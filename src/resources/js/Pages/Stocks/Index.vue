<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

defineProps({ stocks: Array })

const destroy = (id) => {
  if (confirm('この在庫を削除しますか？')) {
    router.delete(route('stocks.destroy', id))
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">在庫一覧</h1>
      <Link :href="route('stocks.create')"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
        + 在庫を追加
      </Link>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">タイトル</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">価格</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">在庫数</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="stock in stocks" :key="stock.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ stock.book?.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ stock.book?.author }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              {{ stock.book?.price != null ? `¥${stock.book.price.toLocaleString()}` : '—' }}
            </td>
            <td class="px-6 py-4 text-sm">
              <span :class="stock.quantity === 0 ? 'text-red-500 font-semibold' : 'text-gray-900'">
                {{ stock.quantity }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <Link :href="route('stocks.edit', stock.id)" class="text-blue-600 hover:underline">編集</Link>
              <button @click="destroy(stock.id)" class="text-red-500 hover:underline">削除</button>
            </td>
          </tr>
          <tr v-if="stocks.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-gray-400">在庫が登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
