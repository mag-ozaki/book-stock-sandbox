<script setup>
import AppLayout from '@/Layouts/AppLayout.vue'
import { Link, router } from '@inertiajs/vue3'

defineOptions({ layout: AppLayout })

defineProps({ genres: Array })

const destroy = (id) => {
  if (confirm('このジャンルを削除しますか？')) {
    router.delete(route('genres.destroy', id))
  }
}
</script>

<template>
  <div>
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold text-gray-800">ジャンル一覧</h1>
      <Link :href="route('genres.create')"
        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 text-sm">
        + 新規登録
      </Link>
    </div>

    <div class="bg-white rounded-xl shadow overflow-hidden">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ジャンル名</th>
            <th class="px-6 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="genre in genres" :key="genre.id" class="hover:bg-gray-50">
            <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ genre.name }}</td>
            <td class="px-6 py-4 text-right text-sm space-x-3">
              <Link :href="route('genres.edit', genre.id)" class="text-blue-600 hover:underline">編集</Link>
              <button @click="destroy(genre.id)" class="text-red-500 hover:underline">削除</button>
            </td>
          </tr>
          <tr v-if="genres.length === 0">
            <td colspan="2" class="px-6 py-8 text-center text-gray-400">ジャンルが登録されていません</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
