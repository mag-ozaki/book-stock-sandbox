<script setup>
import { Link, usePage } from '@inertiajs/vue3'

const page = usePage()
</script>

<template>
  <div class="min-h-screen bg-gray-100">
    <nav class="bg-indigo-900 text-white shadow">
      <div class="max-w-7xl mx-auto px-4 flex justify-between items-center h-16">
        <div class="flex items-center gap-6">
          <span class="font-bold text-lg tracking-wide">Book Stock Admin</span>
          <Link :href="route('admin.dashboard')" class="text-indigo-200 hover:text-white text-sm">ダッシュボード</Link>
          <Link :href="route('admin.stores.index')" class="text-indigo-200 hover:text-white text-sm">店舗管理</Link>
          <Link :href="route('admin.store-users.index')" class="text-indigo-200 hover:text-white text-sm">ユーザー管理</Link>
        </div>
        <div class="flex items-center gap-4">
          <span class="text-indigo-300 text-sm">{{ page.props.auth.admin?.name }}</span>
          <Link
            :href="route('admin.logout')"
            method="post"
            as="button"
            class="text-sm text-indigo-200 hover:text-white"
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
