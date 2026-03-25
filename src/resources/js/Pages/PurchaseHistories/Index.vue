<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

defineProps({ purchaseHistories: Array })

const page = usePage()
const isOwner = page.props.auth.user?.role === 'owner'

const destroy = (id) => {
  if (confirm('この購入履歴を削除しますか？')) {
    router.delete(route('purchase-histories.destroy', id))
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">購入履歴一覧</h1>
      <Link :href="route('purchase-histories.create')"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
        + 新規登録
      </Link>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">購入日</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">書籍タイトル</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">著者</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">購入冊数</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">担当者</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">備考</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="ph in purchaseHistories" :key="ph.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-900">{{ ph.purchased_at }}</td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ ph.book?.title }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ ph.book?.author }}</td>
            <td class="px-6 py-4 text-sm text-gray-900">{{ ph.quantity }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ ph.store_user?.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">
              <span v-if="ph.note">{{ ph.note.slice(0, 50) }}{{ ph.note.length > 50 ? '…' : '' }}</span>
              <span v-else class="text-gray-300">—</span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <Link :href="route('purchase-histories.show', ph.id)" class="text-blue-600 hover:underline">詳細</Link>
              <button v-if="isOwner" @click="destroy(ph.id)" class="text-red-500 hover:underline">削除</button>
            </td>
          </tr>
          <tr v-if="purchaseHistories.length === 0">
            <td colspan="7" class="px-6 py-8 text-center text-gray-400">購入履歴が登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
