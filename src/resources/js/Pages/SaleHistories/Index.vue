<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

defineProps<{
  saleHistories: Array<{
    id: number
    book: { id: number; title: string; author: string }
    quantity: number
    sold_at: string
    pos_terminal_id: string | null
  }>
}>()
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">販売履歴一覧</h1>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">販売日時</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">書籍タイトル</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">販売冊数</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">POS端末ID</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="sh in saleHistories" :key="sh.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-900">{{ sh.sold_at }}</td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ sh.book?.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ sh.book?.author }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ sh.quantity }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <span v-if="sh.pos_terminal_id">{{ sh.pos_terminal_id }}</span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="px-6 py-4 text-right text-sm">
              <Link :href="route('sale-histories.show', sh.id)" class="text-blue-600 hover:underline">詳細</Link>
            </td>
          </tr>
          <tr v-if="saleHistories.length === 0">
            <td colspan="6" class="px-6 py-8 text-center text-gray-400">販売履歴が登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
