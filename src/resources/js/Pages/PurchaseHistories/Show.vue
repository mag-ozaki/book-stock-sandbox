<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

defineProps({ purchaseHistory: Object })
</script>

<template>
  <div class="max-w-xl">
    <div class="flex items-center gap-3 mb-6">
      <Link :href="route('purchase-histories.index')" class="text-blue-600 hover:underline text-sm">← 一覧に戻る</Link>
      <h1 class="text-2xl font-bold text-gray-800">購入履歴詳細</h1>
    </div>

    <div class="bg-white rounded-xl shadow p-6 space-y-4">
      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">購入日</p>
        <p class="text-sm text-gray-900">{{ purchaseHistory.purchased_at }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">書籍タイトル</p>
        <p class="text-sm text-gray-900">{{ purchaseHistory.book?.title }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">著者</p>
        <p class="text-sm text-gray-900">{{ purchaseHistory.book?.author }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">価格</p>
        <p class="text-sm text-gray-900">
          {{ purchaseHistory.book?.price != null ? `¥${purchaseHistory.book.price.toLocaleString()}` : '—' }}
        </p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">購入冊数</p>
        <p class="text-sm text-gray-900">{{ purchaseHistory.quantity }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">担当者</p>
        <p class="text-sm text-gray-900">{{ purchaseHistory.store_user?.name }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">備考</p>
        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ purchaseHistory.note ?? '—' }}</p>
      </div>

      <div>
        <p class="text-xs font-medium text-gray-500 uppercase mb-1">登録日時</p>
        <p class="text-sm text-gray-500">{{ purchaseHistory.created_at }}</p>
      </div>
    </div>
  </div>
</template>
