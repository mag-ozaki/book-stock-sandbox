<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">管理ダッシュボード</h1>

    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
      <Link :href="route('admin.stores.index')"
        class="block bg-white rounded-xl shadow p-6 hover:shadow-md transition">
        <div class="text-3xl mb-2">🏪</div>
        <div class="font-semibold text-gray-800">店舗管理</div>
        <div class="text-sm text-gray-500 mt-1">店舗の登録・編集・削除</div>
      </Link>

      <Link :href="route('admin.store-users.index')"
        class="block bg-white rounded-xl shadow p-6 hover:shadow-md transition">
        <div class="text-3xl mb-2">👤</div>
        <div class="font-semibold text-gray-800">ユーザー管理</div>
        <div class="text-sm text-gray-500 mt-1">全店舗のユーザー管理</div>
      </Link>
    </div>
  </div>
</template>
