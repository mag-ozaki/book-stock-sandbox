<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

defineProps({ stores: Array })

const destroy = (id) => {
  if (confirm('この店舗を削除しますか？')) {
    router.delete(route('admin.stores.destroy', id))
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">店舗一覧</h1>
      <Link :href="route('admin.stores.create')"
        class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm">
        + 新規作成
      </Link>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">店舗名</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">住所</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">電話番号</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="store in stores" :key="store.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm text-gray-500">{{ store.id }}</td>
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ store.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ store.address ?? '—' }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ store.phone ?? '—' }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <Link :href="route('admin.stores.api-keys.index', store.id)" class="text-indigo-600 hover:underline">APIキー</Link>
              <Link :href="route('admin.stores.edit', store.id)" class="text-indigo-600 hover:underline">編集</Link>
              <button @click="destroy(store.id)" class="text-red-500 hover:underline">削除</button>
            </td>
          </tr>
          <tr v-if="stores.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-gray-400">店舗が登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
