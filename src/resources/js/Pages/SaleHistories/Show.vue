<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

defineProps({ saleHistory: Object })
</script>

<template>
  <div class="max-w-xl">
    <div class="flex items-center gap-3 mb-6">
      <Link :href="route('sale-histories.index')" class="text-blue-600 hover:underline text-sm">← 一覧に戻る</Link>
      <h1 class="text-2xl font-bold text-gray-800">販売履歴詳細</h1>
    </div>

    <div class="bg-white rounded-xl shadow p-6 space-y-4">
      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">販売日時</p>
        <p class="text-sm text-gray-900">{{ saleHistory.sold_at }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">書籍タイトル</p>
        <p class="text-sm text-gray-900">{{ saleHistory.book?.title }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">著者</p>
        <p class="text-sm text-gray-900">{{ saleHistory.book?.author }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">単価</p>
        <p class="text-sm text-gray-900">
          {{ saleHistory.book?.price != null ? `¥${saleHistory.book.price.toLocaleString()}` : '—' }}
        </p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">販売冊数</p>
        <p class="text-sm text-gray-900">{{ saleHistory.quantity }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">POS端末ID</p>
        <p class="text-sm text-gray-900">{{ saleHistory.pos_terminal_id ?? '—' }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">登録日時</p>
        <p class="text-sm text-gray-500">{{ saleHistory.created_at }}</p>
      </div>
    </div>
  </div>
</template>
