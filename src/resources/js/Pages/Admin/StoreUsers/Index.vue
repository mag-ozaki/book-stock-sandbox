<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AdminLayout })

defineProps({ storeUsers: Array })

const roleLabel = (role) => role === 'owner' ? 'オーナー' : '従業員'
const roleClass = (role) => role === 'owner'
  ? 'bg-blue-100 text-blue-700'
  : 'bg-gray-100 text-gray-600'

const destroy = (id) => {
  if (confirm('このユーザーを削除しますか？')) {
    router.delete(route('admin.store-users.destroy', id))
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">ユーザー一覧</h1>
      <Link :href="route('admin.store-users.create')"
        class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700 text-sm">
        + 新規作成
      </Link>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">名前</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">メール</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">店舗</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ロール</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="u in storeUsers" :key="u.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ u.name }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ u.email }}</td>
            <td class="px-6 py-4 text-sm text-gray-500">{{ u.store?.name ?? '—' }}</td>
            <td class="px-6 py-4">
              <span class="px-2 py-0.5 text-xs rounded-full font-medium" :class="roleClass(u.role)">
                {{ roleLabel(u.role) }}
              </span>
            </td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <Link :href="route('admin.store-users.edit', u.id)" class="text-indigo-600 hover:underline">編集</Link>
              <button @click="destroy(u.id)" class="text-red-500 hover:underline">削除</button>
            </td>
          </tr>
          <tr v-if="storeUsers.length === 0">
            <td colspan="5" class="px-6 py-8 text-center text-gray-400">ユーザーが登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
