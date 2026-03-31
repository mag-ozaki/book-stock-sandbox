<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-white border-b shadow-sm">
      <div class="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
        <div class="flex items-center gap-6">
          <span class="font-bold text-gray-800">Book Stock</span>
          <Link :href="route('dashboard')" class="text-gray-600 hover:text-gray-900 text-sm">ダッシュボード</Link>
          <Link :href="route('books.index')" class="text-gray-600 hover:text-gray-900 text-sm">書籍</Link>
          <Link :href="route('genres.index')" class="text-gray-600 hover:text-gray-900 text-sm">ジャンル</Link>
          <Link :href="route('stocks.index')" class="text-gray-600 hover:text-gray-900 text-sm">在庫</Link>
          <Link :href="route('store-users.index')" class="text-gray-600 hover:text-gray-900 text-sm">スタッフ</Link>
          <Link :href="route('purchase-histories.index')" class="text-gray-600 hover:text-gray-900 text-sm">仕入れ履歴</Link>
          <Link :href="route('sale-histories.index')" class="text-gray-600 hover:text-gray-900 text-sm">販売履歴</Link>
        </div>
        <div class="flex items-center gap-4">
          <span class="text-gray-500 text-sm">{{ page.props.auth.user?.name }}</span>
          <Link
            :href="route('logout')"
            method="post"
            as="button"
            class="text-sm text-gray-500 hover:text-gray-800"
          >ログアウト</Link>
        </div>
      </div>
    </nav>

    <div v-if="page.props.flash?.success" class="max-w-7xl mx-auto px-4 mt-4">
      <div class="bg-green-50 border border-green-300 text-green-800 px-4 py-3 rounded">
        {{ page.props.flash.success }}
      </div>
    </div>
    <div v-if="page.props.flash?.error" class="max-w-7xl mx-auto px-4 mt-4">
      <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded">
        {{ page.props.flash.error }}
      </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 py-8">
      <slot />
    </main>
  </div>
</template>
