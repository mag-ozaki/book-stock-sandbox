<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, usePage } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

const page = usePage()
</script>

<template>
  <div>
    <h1 class="text-2xl font-bold text-gray-800 mb-6">ダッシュボード</h1>
    <p class="text-gray-600 mb-8">
      ようこそ、{{ page.props.auth.user?.name }} さん。
      <span class="ml-2 px-2 py-0.5 text-xs rounded-full font-medium"
        :class="page.props.auth.user?.role === 'owner' ? 'bg-blue-100 text-blue-700' : 'bg-gray-100 text-gray-600'"
      >{{ page.props.auth.user?.role }}</span>
    </p>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
      <Link :href="route('books.index')"
        class="block bg-white rounded-xl shadow p-6 hover:shadow-md transition">
        <div class="text-3xl mb-2">📚</div>
        <div class="font-semibold text-gray-800">書籍管理</div>
        <div class="text-sm text-gray-500 mt-1">書籍の登録・編集</div>
      </Link>

      <Link :href="route('stocks.index')"
        class="block bg-white rounded-xl shadow p-6 hover:shadow-md transition">
        <div class="text-3xl mb-2">📦</div>
        <div class="font-semibold text-gray-800">在庫管理</div>
        <div class="text-sm text-gray-500 mt-1">在庫数の確認・更新</div>
      </Link>

      <Link :href="route('store-users.index')"
        class="block bg-white rounded-xl shadow p-6 hover:shadow-md transition">
        <div class="text-3xl mb-2">👥</div>
        <div class="font-semibold text-gray-800">スタッフ管理</div>
        <div class="text-sm text-gray-500 mt-1">スタッフの確認・管理</div>
      </Link>
    </div>
  </div>
</template>
